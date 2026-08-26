<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkSmsRequest;
use App\Http\Requests\SendSmsRequest;
use App\Http\Requests\TemplateRequest;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\MessageTemplate;
use App\Models\Payment;
use App\Models\SmsLog;
use App\Models\Student;
use App\Services\SmsService;
use App\Services\SmsTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for SMS communication operations.
 * 
 * Handles SMS sending, bulk SMS, logs, templates, and notifications.
 * 
 * Validates: Requirements 4.1, 4.2, 4.3, 4.4, 10.3, 11.1, 11.2, 11.4, 11.5, 
 *            13.1, 13.2, 13.3, 13.4, 14.2, 14.4
 */
class CommunicationController extends Controller
{
    public function __construct(
        protected SmsService $smsService,
        protected SmsTemplateService $templateService
    ) {
        //
    }

    /**
     * Display SMS dashboard with sending form and recent logs.
     */
    public function index(Request $request): View
    {
        $batches = Batch::all();
        $courses = Course::all();
        $templates = MessageTemplate::where('type', 'sms')->where('is_active', true)->get();
        
        $query = SmsLog::orderBy('created_at', 'desc');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }
        
        $recentLogs = $query->limit(20)->get();
        
        $stats = [
            'total_sent' => SmsLog::whereIn('status', ['sent', 'delivered'])->count(),
            'total_failed' => SmsLog::where('status', 'failed')->count(),
            'total_pending' => SmsLog::where('status', 'pending')->count(),
            'today_sent' => SmsLog::whereIn('status', ['sent', 'delivered'])
                ->whereDate('created_at', today())->count(),
        ];
        
        $placeholders = $this->templateService->getAvailablePlaceholders();
        
        return view('dashboard.communication.index', compact(
            'batches', 'courses', 'templates', 'recentLogs', 'stats', 'placeholders'
        ));
    }

    /**
     * Send SMS to a single recipient.
     */
    public function send(SendSmsRequest $request): RedirectResponse
    {
        $phone = $request->validated('phone');
        $message = $request->validated('message');
        $type = $request->validated('type', 'general');
        
        $log = $this->smsService->send($phone, $message, ['type' => $type]);
        
        if ($log->isSent() || $log->isDelivered()) {
            return back()->with('success', 'SMS sent successfully.');
        }
        
        return back()->with('error', 'Failed to send SMS. Please try again.');
    }

    /**
     * Send bulk SMS to multiple recipients. The campaign is queued so large
     * recipient lists never block the HTTP request; delivery results land in
     * the SMS logs.
     */
    public function bulkSend(BulkSmsRequest $request): JsonResponse
    {
        $message = $request->validated('message');
        $recipientType = $request->validated('recipient_type');
        $includeParents = $request->boolean('include_parents', false);

        // Direct recipients array takes precedence over criteria selection.
        $directRecipients = $request->validated('recipients') ?? [];
        $recipients = $directRecipients !== []
            ? array_map(fn ($phone) => ['phone' => $phone, 'name' => null, 'recipient_type' => 'custom'], $directRecipients)
            : $this->getRecipientsForBulkSend($request, $includeParents);

        if (empty($recipients)) {
            return response()->json([
                'success' => false,
                'message' => 'No recipients found for the selected criteria.',
            ], 422);
        }

        \App\Jobs\SendBulkSmsJob::dispatch($recipients, $message, [
            'type' => 'bulk',
            'recipient_type' => $recipientType,
        ]);

        return response()->json([
            'success' => true,
            'message' => count($recipients) . ' SMS messages queued for background delivery. Track progress in the SMS logs.',
            'data' => [
                'total' => count($recipients),
                'queued' => true,
            ],
        ]);
    }

    /**
     * Display SMS logs with filtering options.
     */
    public function logs(Request $request): View
    {
        $filters = $request->only(['status', 'type', 'phone', 'date_from', 'date_to']);
        
        $query = SmsLog::query()->orderBy('created_at', 'desc');
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['phone'])) {
            $query->where('phone', 'like', "%{$filters['phone']}%");
        }
        
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        $logs = $query->paginate(50);
        
        $statusOptions = ['pending', 'sent', 'delivered', 'failed'];
        $typeOptions = ['general', 'payment', 'result', 'attendance', 'reminder', 'bulk'];
        
        return view('dashboard.communication.logs', compact('logs', 'filters', 'statusOptions', 'typeOptions'));
    }

    /**
     * Retry a single failed SMS.
     */
    public function retry(SmsLog $smsLog): RedirectResponse
    {
        if (!$smsLog->isFailed()) {
            return back()->with('error', 'Only failed SMS messages can be retried.');
        }
        
        try {
            $newLog = $this->smsService->retry($smsLog);
            
            if ($newLog->isSent() || $newLog->isDelivered()) {
                return back()->with('success', 'SMS retried successfully.');
            }
            
            return back()->with('warning', 'SMS retry attempted but delivery failed.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to retry SMS: ' . $e->getMessage());
        }
    }

    /**
     * Retry multiple failed SMS messages.
     */
    public function retryBulk(Request $request): JsonResponse
    {
        $request->validate([
            'log_ids' => ['required', 'array', 'min:1'],
            'log_ids.*' => ['integer', 'exists:sms_logs,id'],
        ]);
        
        $logIds = $request->input('log_ids');
        $logs = SmsLog::whereIn('id', $logIds)->where('status', 'failed')->get();
        
        if ($logs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No failed SMS messages found to retry.',
            ], 422);
        }
        
        $successful = 0;
        $failed = 0;
        
        foreach ($logs as $log) {
            try {
                $newLog = $this->smsService->retry($log);
                if ($newLog->isSent() || $newLog->isDelivered()) {
                    $successful++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Retried {$successful} messages successfully. Failed: {$failed}",
            'data' => ['successful' => $successful, 'failed' => $failed],
        ]);
    }


    /**
     * Send payment confirmation SMS.
     */
    public function sendPaymentNotification(Payment $payment): RedirectResponse
    {
        $student = $payment->student;
        
        if (!$student) {
            return back()->with('error', 'Student not found for this payment.');
        }
        
        $phone = $student->guardian_phone ?? $student->phone;
        
        if (!$phone) {
            return back()->with('error', 'No phone number available for this student.');
        }
        
        $message = $this->buildPaymentNotificationMessage($student, $payment);
        
        $log = $this->smsService->send($phone, $message, [
            'type' => 'payment',
            'related' => $student,
        ]);
        
        if ($log->isSent() || $log->isDelivered()) {
            return back()->with('success', 'Payment notification SMS sent successfully.');
        }
        
        return back()->with('error', 'Failed to send payment notification SMS.');
    }

    /**
     * Send payment reminders to students with outstanding dues.
     */
    public function sendPaymentReminders(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'min_due_amount' => ['nullable', 'numeric', 'min:0'],
        ]);
        
        $query = Student::where('due_amount', '>', 0);
        
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }
        
        if ($request->filled('min_due_amount')) {
            $query->where('due_amount', '>=', $request->min_due_amount);
        }
        
        $students = $query->get();
        
        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No students found with outstanding dues.',
            ], 422);
        }
        
        $recipients = [];
        
        foreach ($students as $student) {
            $phone = $student->guardian_phone ?? $student->phone;
            
            if ($phone) {
                $message = $this->buildPaymentReminderMessage($student);
                
                $recipients[] = [
                    'phone' => $phone,
                    'message' => $message,
                    'name' => $student->name,
                    'related' => $student,
                ];
            }
        }
        
        if (empty($recipients)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid phone numbers found for students with dues.',
            ], 422);
        }
        
        $successful = 0;
        $failed = 0;
        
        foreach ($recipients as $recipient) {
            $log = $this->smsService->send($recipient['phone'], $recipient['message'], [
                'type' => 'reminder',
                'related' => $recipient['related'],
            ]);
            
            if ($log->isSent() || $log->isDelivered()) {
                $successful++;
            } else {
                $failed++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Payment reminders sent to {$successful} students. Failed: {$failed}",
            'data' => [
                'total' => count($recipients),
                'successful' => $successful,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Send exam result notifications to students.
     */
    public function sendResultNotification(Request $request): JsonResponse
    {
        $request->validate([
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
        ]);
        
        $exam = Exam::with('batch')->findOrFail($request->exam_id);
        
        $query = ExamResult::where('exam_id', $exam->id)->with('student');
        
        if ($request->filled('batch_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('batch_id', $request->batch_id);
            });
        }
        
        $results = $query->get();
        
        if ($results->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No exam results found for the selected criteria.',
            ], 422);
        }
        
        $successful = 0;
        $failed = 0;
        
        foreach ($results as $result) {
            $student = $result->student;
            
            if (!$student) {
                $failed++;
                continue;
            }
            
            $phone = $student->guardian_phone ?? $student->phone;
            
            if (!$phone) {
                $failed++;
                continue;
            }
            
            $message = $this->buildResultNotificationMessage($student, $exam, $result);
            
            $log = $this->smsService->send($phone, $message, [
                'type' => 'result',
                'related' => $student,
                'exam_id' => $exam->id,
                'exam_name' => $exam->title,
                'marks' => $result->obtained_marks ?? $result->marks,
                'total_marks' => $result->total_marks ?? $exam->total_marks,
                'grade' => $result->grade ?? $result->calculateGrade(),
            ]);
            
            if ($log->isSent() || $log->isDelivered()) {
                $successful++;
            } else {
                $failed++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Result notifications sent to {$successful} students. Failed: {$failed}",
            'data' => [
                'total' => $results->count(),
                'successful' => $successful,
                'failed' => $failed,
                'exam_name' => $exam->title,
            ],
        ]);
    }


    /**
     * Display SMS template management page.
     */
    public function templates(): View
    {
        $templates = MessageTemplate::where('type', 'sms')->orderBy('name')->get();
        $placeholders = $this->templateService->getAvailablePlaceholders();
        $predefinedTemplates = $this->templateService->getPredefinedTemplates();
        
        return view('dashboard.communication.templates', compact('templates', 'placeholders', 'predefinedTemplates'));
    }

    /**
     * Store a new SMS template.
     */
    public function storeTemplate(TemplateRequest $request): RedirectResponse
    {
        $validated = $request->validatedWithPlaceholders();

        MessageTemplate::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['name']),
            'content' => $validated['content'],
            'type' => $validated['type'] ?? 'sms',
            'placeholders' => $validated['placeholders'] ?? [],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Template created successfully.');
    }

    /**
     * Update an existing SMS template.
     */
    public function updateTemplate(TemplateRequest $request, MessageTemplate $template): RedirectResponse
    {
        $validated = $request->validatedWithPlaceholders();

        $template->update([
            'name' => $validated['name'],
            'content' => $validated['content'],
            'placeholders' => $validated['placeholders'] ?? [],
            'description' => $validated['description'] ?? $template->description,
            'is_active' => $validated['is_active'] ?? $template->is_active,
        ]);

        return back()->with('success', 'Template updated successfully.');
    }

    /**
     * Delete an SMS template.
     */
    public function deleteTemplate(MessageTemplate $template): RedirectResponse
    {
        $template->delete();
        return back()->with('success', 'Template deleted successfully.');
    }

    /**
     * Retry all failed SMS messages from the last 24 hours.
     */
    public function retryFailed(): RedirectResponse
    {
        $retried = $this->smsService->retryFailed();
        return back()->with('success', "Retried {$retried} failed messages.");
    }

    /**
     * Send result notification (legacy method for backward compatibility).
     */
    public function sendResult(Request $request)
    {
        $batches = Batch::with('course')->get();
        $exams = Exam::orderBy('created_at', 'desc')->get();
        
        if ($request->isMethod('post')) {
            $request->validate([
                'batch_id' => ['required', 'exists:batches,id'],
                'exam_id' => ['required', 'exists:exams,id'],
            ]);

            $batch = Batch::with('students')->find($request->batch_id);
            $exam = Exam::find($request->exam_id);
            $sent = 0;

            foreach ($batch->students as $student) {
                $result = ExamResult::where('student_id', $student->id)
                    ->where('exam_id', $request->exam_id)
                    ->first();
                    
                if ($result) {
                    $phone = $student->guardian_phone ?? $student->phone;
                    
                    if ($phone) {
                        $message = $this->buildResultNotificationMessage($student, $exam, $result);
                        
                        $log = $this->smsService->send($phone, $message, [
                            'type' => 'result',
                            'related' => $student,
                        ]);
                        
                        if ($log && $log->isSent()) {
                            $sent++;
                        }
                    }
                }
            }

            return back()->with('success', "Result SMS sent to {$sent} students.");
        }

        return view('dashboard.communication.send-result', compact('batches', 'exams'));
    }


    /**
     * Get recipients based on bulk send criteria.
     */
    protected function getRecipientsForBulkSend(BulkSmsRequest $request, bool $includeParents = false): array
    {
        $recipientType = $request->validated('recipient_type');
        $recipients = [];
        
        if ($recipientType === 'custom') {
            $numbers = array_filter(array_map('trim', explode(',', $request->validated('custom_numbers'))));
            foreach ($numbers as $number) {
                $recipients[] = [
                    'phone' => $number,
                    'name' => null,
                    'recipient_type' => 'custom',
                ];
            }
            return $recipients;
        }
        
        $query = Student::query();
        
        switch ($recipientType) {
            case 'batch':
                $query->where('batch_id', $request->validated('batch_id'));
                break;
            case 'course':
                $query->whereHas('batch', function ($q) use ($request) {
                    $q->where('course_id', $request->validated('course_id'));
                });
                break;
            case 'students_with_dues':
                $query->where('due_amount', '>', 0);
                break;
            case 'all':
            default:
                break;
        }
        
        $students = $query->get();
        
        foreach ($students as $student) {
            if ($student->phone) {
                $recipients[] = [
                    'phone' => $student->phone,
                    'name' => $student->name,
                    'recipient_type' => 'student',
                    'related' => $student,
                ];
            }
            
            if ($includeParents && $student->guardian_phone && $student->guardian_phone !== $student->phone) {
                $recipients[] = [
                    'phone' => $student->guardian_phone,
                    'name' => $student->guardian_name ?? 'Guardian of ' . $student->name,
                    'recipient_type' => 'parent',
                    'related' => $student,
                ];
            }
        }
        
        $uniqueRecipients = [];
        $seenPhones = [];
        
        foreach ($recipients as $recipient) {
            if (!in_array($recipient['phone'], $seenPhones)) {
                $seenPhones[] = $recipient['phone'];
                $uniqueRecipients[] = $recipient;
            }
        }
        
        return $uniqueRecipients;
    }

    /**
     * Build payment notification message.
     */
    protected function buildPaymentNotificationMessage(Student $student, Payment $payment): string
    {
        $amount = number_format($payment->amount, 2);
        $date = $payment->payment_date ? $payment->payment_date->format('d M Y') : now()->format('d M Y');
        $balance = number_format($student->due_amount, 2);
        $receipt = $payment->receipt_number ?? 'N/A';
        
        return "Dear Parent, payment of Tk.{$amount} received for {$student->name} on {$date}. Receipt: {$receipt}. Current balance: Tk.{$balance}. Thank you!";
    }

    /**
     * Build payment reminder message.
     */
    protected function buildPaymentReminderMessage(Student $student): string
    {
        $dueAmount = number_format($student->due_amount, 2);
        $date = now()->format('d M Y');
        
        return "Dear Parent, this is a reminder that Tk.{$dueAmount} is due for {$student->name} as of {$date}. Please pay at your earliest convenience. Thank you!";
    }

    /**
     * Build result notification message.
     */
    protected function buildResultNotificationMessage(Student $student, Exam $exam, ExamResult $result): string
    {
        $examName = $exam->title ?? 'Exam';
        $marks = $result->obtained_marks ?? $result->marks ?? 0;
        $totalMarks = $result->total_marks ?? $exam->total_marks ?? 100;
        $grade = $result->grade ?? $result->calculateGrade();
        
        return "Dear Parent, {$student->name} scored {$marks}/{$totalMarks} ({$grade}) in {$examName}. Keep up the good work!";
    }
}
