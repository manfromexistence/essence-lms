<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use App\Models\EmailLog;
use App\Models\Student;
use App\Services\BrevoEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailController extends Controller
{
    public function __construct(protected BrevoEmailService $emailService)
    {
        //
    }

    /**
     * Email dashboard — send single/bulk emails and view recent logs.
     */
    public function index(): View
    {
        $batches = Batch::all();
        $courses = Course::all();
        $recentLogs = $this->emailService->recentLogs(20);
        $stats = $this->emailService->stats();

        return view('dashboard.email.index', compact('batches', 'courses', 'recentLogs', 'stats'));
    }

    /**
     * Send a single email.
     */
    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $html = $this->wrapHtml($data['message']);
        $log = $this->emailService->send($data['email'], $data['subject'], $html, ['type' => 'general']);

        if ($log->isSent()) {
            return back()->with('success', "Email sent to {$data['email']}.");
        }

        return back()->with('error', 'Failed to send email: ' . ($log->error_message ?? 'unknown error'));
    }

    /**
     * Send bulk emails to students (all / batch / course / with dues / custom).
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_type' => ['required', 'string', 'in:all,batch,course,students_with_dues,custom'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'custom_emails' => ['nullable', 'string', 'max:4000'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $recipients = $this->getRecipients($data);

        if (empty($recipients)) {
            return response()->json([
                'success' => false,
                'message' => 'No recipients found for the selected criteria.',
            ], 422);
        }

        $html = $this->wrapHtml($data['message']);
        $result = $this->emailService->sendBulk($recipients, $data['subject'], $html, ['type' => 'bulk']);

        return response()->json([
            'success' => true,
            'message' => "Emails sent to {$result['successful']} recipients. Failed: {$result['failed']}",
            'data' => $result,
        ]);
    }

    /**
     * Build recipient list from the request criteria.
     */
    protected function getRecipients(array $data): array
    {
        $recipients = [];

        if ($data['recipient_type'] === 'custom') {
            $emails = array_filter(array_map('trim', explode(',', $data['custom_emails'] ?? '')));
            foreach ($emails as $email) {
                $recipients[] = ['email' => $email, 'name' => null];
            }
            return $recipients;
        }

        $query = Student::with('user');

        switch ($data['recipient_type']) {
            case 'batch':
                $query->where('batch_id', $data['batch_id']);
                break;
            case 'course':
                $query->whereHas('batch', fn ($q) => $q->where('course_id', $data['course_id']));
                break;
            case 'students_with_dues':
                $query->where('due_amount', '>', 0);
                break;
        }

        foreach ($query->get() as $student) {
            $email = $student->user?->email;
            if ($email) {
                $recipients[] = [
                    'email' => $email,
                    'name' => $student->user?->name,
                    'related' => $student,
                ];
            }
        }

        return $recipients;
    }

    /**
     * Wrap plain text message in a minimal branded HTML email.
     */
    protected function wrapHtml(string $message): string
    {
        $escaped = e(nl2br($message));
        $institution = \App\Models\Setting::getValue('institution_name', 'Dhaka IT Institute');

        return <<<HTML
<!DOCTYPE html>
<html>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:24px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="background:#168536;padding:20px 32px;">
                            <span style="color:#ffffff;font-size:20px;font-weight:bold;">{$institution}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;color:#374151;font-size:15px;line-height:1.7;">
                            {$escaped}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;border-top:1px solid #f0f0f0;color:#9ca3af;font-size:12px;">
                            This email was sent by {$institution}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
