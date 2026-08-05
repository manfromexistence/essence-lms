<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\CqSubmission;
use App\Models\Payment;
use App\Models\CourseMaterial;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseVideo;
use App\Models\VideoView;
use App\Services\StudentPortalService;
use App\Services\ExamTakingService;
use App\Services\MarkSheetService;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentPortalController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected ExamTakingService $examService,
        protected MarkSheetService $markSheetService,
        protected CertificateService $certificateService
    ) {}

    /**
     * Get the authenticated student.
     */
    private function getStudent(): ?Student
    {
        $user = Auth::user();
        return Student::where('user_id', $user->id)->first();
    }

    /**
     * Student dashboard.
     */
    public function dashboard()
    {
        $student = $this->getStudent();
        
        if (!$student) {
            // Allow Super Admin to view placeholder
            if (Auth::user()->isSuperAdmin()) {
                return view('student.dashboard-placeholder');
            }
            return redirect()->route('dashboard')->with('error', 'Student profile not found.');
        }

        $data = $this->portalService->getDashboardData($student);
        
        return view('student.dashboard', $data);
    }

    /**
     * Course materials.
     */
    public function materials()
    {
        $student = $this->getStudent();
        
        if (!$student) {
            // For admin users without student profile, show all materials
            if (Auth::user()->isAdmin()) {
                $materials = CourseMaterial::with('course')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->groupBy('type');
                
                return view('student.materials', [
                    'student' => null,
                    'materials' => $materials,
                ]);
            }
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        $materials = $this->portalService->getMaterials($student);
        
        return view('student.materials', [
            'student' => $student,
            'materials' => $materials,
        ]);
    }

    /**
     * Download a course material.
     */
    public function downloadMaterial(CourseMaterial $material)
    {
        $student = $this->getStudent();
        
        if (!$student || $student->batch?->course_id !== $material->course_id) {
            abort(403, 'Unauthorized access to this material.');
        }

        if ($material->type === 'link') {
            return redirect()->away($material->file_path);
        }

        $disk = Storage::disk(config('filesystems.private'));
        if ($disk->exists($material->file_path)) {
            return $disk->download($material->file_path, $material->title);
        }

        return back()->with('error', 'File not found.');
    }

    public function streamVideo(Course $course, CourseVideo $video)
    {
        abort_unless($video->course_id === $course->id, 404);
        $student = $this->getStudent();
        $authorized = Auth::user()->isAdmin() || ($student && CourseEnrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)->exists());
        abort_unless($authorized, 403);
        $disk = Storage::disk(config('filesystems.private'));
        abort_unless($video->video_path && $disk->exists($video->video_path), 404);

        return $disk->response($video->video_path, null, [
            'Content-Type' => $disk->mimeType($video->video_path) ?: 'video/mp4',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Class schedule.
     */
    public function schedule()
    {
        $student = $this->getStudent();
        
        if (!$student) {
            // For admin users without student profile, show all schedules
            if (Auth::user()->isAdmin()) {
                $schedules = \App\Models\ClassSchedule::with(['batch', 'teacher'])
                    ->orderBy('day_of_week')
                    ->orderBy('start_time')
                    ->get();
                
                return view('student.schedule', [
                    'student' => null,
                    'schedules' => $schedules,
                ]);
            }
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        $schedules = $this->portalService->getSchedule($student);
        
        return view('student.schedule', [
            'student' => $student,
            'schedules' => $schedules,
        ]);
    }

    /**
     * Payment history.
     */
    public function payments()
    {
        $student = $this->getStudent();
        
        if (!$student) {
            // For admin users without student profile, show all payments
            if (Auth::user()->isAdmin()) {
                $payments = Payment::with('student')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                
                $totalFee = Payment::sum('amount');
                $paidAmount = Payment::completed()->sum('amount');
                $dueAmount = $totalFee - $paidAmount;
                
                $summary = [
                    'total_fee' => $totalFee,
                    'paid_amount' => $paidAmount,
                    'due_amount' => $dueAmount,
                    'payment_percentage' => $totalFee > 0 ? round(($paidAmount / $totalFee) * 100, 2) : 0,
                ];
                
                return view('student.payments', [
                    'student' => null,
                    'payments' => $payments,
                    'summary' => $summary,
                ]);
            }
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        $payments = $this->portalService->getPaymentHistory($student);
        $summary = $this->portalService->getPaymentSummary($student);
        
        return view('student.payments', [
            'student' => $student,
            'payments' => $payments,
            'summary' => $summary,
        ]);
    }

    /**
     * Download payment receipt.
     */
    public function downloadReceipt(Payment $payment)
    {
        $student = $this->getStudent();
        
        if (!$student || $payment->student_id !== $student->id) {
            abort(403, 'Unauthorized access to this receipt.');
        }

        $payment->load(['student.batch.course']);

        $pdf = PDF::loadView('pdf.receipt', [
            'payment' => $payment,
            'student' => $student,
        ]);

        return $pdf->download('receipt-' . $payment->id . '.pdf');
    }

    /**
     * List exams.
     */
    public function exams()
    {
        $student = $this->getStudent();
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        $upcomingExams = $this->portalService->getUpcomingExams($student);
        
        $pastExams = ExamResult::where('student_id', $student->id)
            ->with('exam')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.exams', [
            'student' => $student,
            'upcomingExams' => $upcomingExams,
            'pastExams' => $pastExams,
        ]);
    }

    /**
     * Start an MCQ exam.
     */
    public function startExam(Exam $exam)
    {
        $student = $this->getStudent();
        
        if (!$student) {
            abort(403, 'Student profile not found.');
        }
        
        // Check if student has access to this exam
        // Allow access if:
        // 1. Exam has no batch restriction (batch_id is null), OR
        // 2. Student's batch matches the exam's batch, OR
        // 3. Student is enrolled in a batch that has access to this exam
        $hasAccess = false;
        
        if (!$exam->batch_id) {
            // Exam is available to all students
            $hasAccess = true;
        } elseif ($student->batch_id && $exam->batch_id === $student->batch_id) {
            // Student's batch matches exam's batch
            $hasAccess = true;
        } elseif ($student->batches && $student->batches->contains('id', $exam->batch_id)) {
            // Student is enrolled in the exam's batch (many-to-many relationship)
            $hasAccess = true;
        }
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this exam. Please contact your administrator.');
        }

        $attempt = $this->examService->startAttempt($student, $exam);
        $examData = $this->examService->getExamWithTimer($attempt);

        return view('student.exam-take', $examData);
    }

    /**
     * Take an MCQ exam.
     * 
     * Requirements: 2.1, 5.1, 5.2
     */
    public function takeMCQ(Exam $exam)
    {
        $student = $this->getStudent();
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        // Check if student has access to this exam
        // Allow access if:
        // 1. Exam has no batch restriction (batch_id is null), OR
        // 2. Student's batch matches the exam's batch, OR
        // 3. Student is enrolled in a batch that has access to this exam
        $hasAccess = false;
        
        if (!$exam->batch_id) {
            // Exam is available to all students
            $hasAccess = true;
        } elseif ($student->batch_id && $exam->batch_id === $student->batch_id) {
            // Student's batch matches exam's batch
            $hasAccess = true;
        } elseif ($student->batches && $student->batches->contains('id', $exam->batch_id)) {
            // Student is enrolled in the exam's batch (many-to-many relationship)
            $hasAccess = true;
        }
        
        if (!$hasAccess) {
            return redirect()->route('student.exams')
                ->with('error', 'You do not have access to this exam. Please contact your administrator.');
        }

        // Validate exam time window using ExamTimeValidator
        $timeValidator = app(\App\Services\ExamTimeValidator::class);
        
        if (!$timeValidator->canStartExam($exam)) {
            $timeStatus = $timeValidator->getTimeStatus($exam);
            $message = $timeValidator->getTimeStatusMessage($exam);
            
            return redirect()->route('student.exams')
                ->with('error', $message);
        }

        // Create or retrieve ExamAttempt for student
        // First, check if any attempt exists (regardless of status)
        $attempt = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();
        
        // If no attempt exists, create one
        if (!$attempt) {
            $attempt = ExamAttempt::create([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'status' => 'in_progress',
                'started_at' => now(),
                'answers' => [],
                'cheating_events' => [],
                'ip_address' => request()->ip(),
            ]);
        }
        
        // If attempt is already submitted, redirect to results
        if ($attempt->status === 'submitted') {
            $result = ExamResult::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->first();
            
            if ($result) {
                return redirect()->route('student.exam-result', $result->id)
                    ->with('info', 'You have already submitted this exam.');
            }
        }
        
        // If attempt exists but has no started_at, set it
        if (!$attempt->started_at) {
            $attempt->update(['started_at' => now()]);
        }

        // Load exam questions with options
        $questions = $exam->questions()
            ->where('type', 'mcq')
            ->orderBy('order')
            ->get();

        // Get saved answers from the attempt
        $savedAnswers = $attempt->answers ?? [];

        // Calculate remaining time
        $remainingTime = $timeValidator->getRemainingTime($attempt);

        // Pass data to view
        return view('student.mcq-exam', [
            'student' => $student,
            'exam' => $exam,
            'attempt' => $attempt,
            'questions' => $questions,
            'timeRemaining' => $remainingTime,
            'savedAnswers' => $savedAnswers,
        ]);
    }

    /**
     * Save answer via AJAX.
     */
    public function saveAnswer(Request $request, ExamAttempt $attempt)
    {
        $student = $this->getStudent();
        
        if (!$student || $attempt->student_id !== $student->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'question_id' => 'required|integer',
            'answer' => 'required|string',
        ]);

        $success = $this->examService->saveAnswer(
            $attempt,
            $request->question_id,
            $request->answer
        );

        return response()->json([
            'success' => $success,
            'remaining_time' => $attempt->fresh()->remaining_time,
        ]);
    }

    /**
     * Record tab switch event (anti-cheating).
     * 
     * Requirements: 6.3, 6.4
     */
    public function recordTabSwitch(Request $request, ExamAttempt $attempt)
    {
        $student = $this->getStudent();
        
        if (!$student || $attempt->student_id !== $student->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'event_type' => 'required|string|in:tab_switch,fullscreen_exit',
            'timestamp' => 'required|string',
        ]);

        // Get existing cheating events or initialize empty array
        $cheatingEvents = $attempt->cheating_events ?? [];
        
        // Add new event
        $cheatingEvents[] = [
            'type' => $request->event_type,
            'timestamp' => $request->timestamp,
            'recorded_at' => now()->toISOString(),
        ];

        // Update attempt with new events
        $attempt->update([
            'cheating_events' => $cheatingEvents,
        ]);

        return response()->json([
            'success' => true,
            'event_count' => count($cheatingEvents),
        ]);
    }

    /**
     * Submit an exam.
     * 
     * Requirements: 2.2
     * 
     * Task details:
     * - Validate exam attempt ownership
     * - Save all answers to exam_attempts table (already saved via auto-save)
     * - Create ExamResult record with score calculation
     * - Mark attempt as submitted
     * - Redirect to results page
     */
    public function submitExam(Request $request, Exam $exam)
    {
        $student = $this->getStudent();
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        // Validate exam attempt ownership
        // First try to find an in-progress attempt
        $attempt = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('status', 'in_progress')
            ->first();

        // If no in-progress attempt, check if already submitted
        if (!$attempt) {
            $submittedAttempt = ExamAttempt::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->where('status', 'submitted')
                ->first();
            
            if ($submittedAttempt) {
                // Already submitted, redirect to results
                $result = ExamResult::where('student_id', $student->id)
                    ->where('exam_id', $exam->id)
                    ->first();
                
                if ($result) {
                    return redirect()->route('student.exam-result', $result->id)
                        ->with('info', 'This exam has already been submitted.');
                }
            }
            
            return redirect()->route('student.exams')->with('error', 'No active attempt found for this exam.');
        }

        // Submit exam (saves answers, creates result, marks as submitted)
        $result = $this->examService->submitExam($attempt);

        // Redirect to results page
        return redirect()->route('student.exam-result', $result->id)
            ->with('success', 'Exam submitted successfully!');
    }

    /**
     * Show exam result with explanations.
     */
    public function examResult(ExamResult $result)
    {
        $student = $this->getStudent();
        
        if (!$student || $result->student_id !== $student->id) {
            abort(403, 'Unauthorized access to this result.');
        }

        $result->load(['exam.questions']);
        
        $attempt = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $result->exam_id)
            ->first();

        return view('student.exam-result', [
            'student' => $student,
            'result' => $result,
            'attempt' => $attempt,
            'questions' => $result->exam->questions,
        ]);
    }

    /**
     * View detailed exam results with performance analysis.
     * 
     * Requirements: 7.1, 7.2, 7.4
     * 
     * Task details:
     * - Retrieve ExamResult for authenticated student
     * - Load exam, attempt, questions, and answers
     * - Calculate performance metrics (score, percentage, time taken, accuracy)
     * - Fetch student's leaderboard rank
     * - Pass data to view
     */
    public function viewResults(Exam $exam)
    {
        $student = $this->getStudent();
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        // Retrieve ExamResult for authenticated student
        $result = ExamResult::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        if (!$result) {
            return redirect()->route('student.exams')->with('error', 'No result found for this exam.');
        }

        // Load exam with questions
        $exam->load('questions');

        // Load attempt with answers
        $attempt = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        if (!$attempt) {
            return redirect()->route('student.exams')->with('error', 'No exam attempt found.');
        }

        // Get questions with student answers and correct answers
        $questions = $exam->questions->map(function ($question) use ($attempt) {
            $studentAnswer = $attempt->answers[$question->id] ?? null;
            
            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'type' => $question->type,
                'options' => $question->options,
                'correct_answer' => $question->correct_answer,
                'student_answer' => $studentAnswer,
                'is_correct' => $question->correct_answer && $studentAnswer 
                    ? $question->isCorrectAnswer($studentAnswer) 
                    : null,
                'marks' => $question->marks,
            ];
        });

        // Calculate performance metrics
        $timeTaken = 0;
        if ($attempt->started_at && $attempt->submitted_at) {
            $timeTaken = $attempt->started_at->diffInSeconds($attempt->submitted_at);
        }

        // Calculate accuracy (percentage of correct answers)
        $totalQuestions = $questions->count();
        $correctAnswers = $questions->filter(function ($q) {
            return $q['is_correct'] === true;
        })->count();
        
        $accuracy = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        // Fetch student's leaderboard rank
        $rank = ExamResult::where('exam_id', $exam->id)
            ->where(function ($query) use ($result) {
                $query->where('obtained_marks', '>', $result->obtained_marks)
                    ->orWhere(function ($q) use ($result) {
                        $q->where('obtained_marks', '=', $result->obtained_marks)
                            ->where('id', '<', $result->id);
                    });
            })
            ->count() + 1;

        // Build performance metrics array
        $performance = [
            'score' => $result->obtained_marks,
            'total_marks' => $result->total_marks,
            'percentage' => $result->percentage,
            'time_taken' => $timeTaken,
            'time_taken_formatted' => gmdate('H:i:s', $timeTaken),
            'accuracy' => $accuracy,
            'rank' => $rank,
            'total_students' => ExamResult::where('exam_id', $exam->id)->count(),
            'passed' => $result->hasPassed(),
            'grade' => $result->grade ?? $result->calculateGrade(),
        ];

        // Pass data to view
        return view('student.exam-results', [
            'student' => $student,
            'result' => $result,
            'attempt' => $attempt,
            'exam' => $exam,
            'questions' => $questions,
            'performance' => $performance,
        ]);
    }

    /**
     * Take a CQ exam.
     * 
     * Requirements: 5.1, 5.2
     * 
     * Task details:
     * - Validate exam time window
     * - Create or retrieve ExamAttempt
     * - Load CQ questions
     * - Pass data to view
     */
    public function takeCQ(Exam $exam)
    {
        $student = $this->getStudent();
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        // Check if student has access to this exam
        // Allow access if:
        // 1. Exam has no batch restriction (batch_id is null), OR
        // 2. Student's batch matches the exam's batch, OR
        // 3. Student is enrolled in a batch that has access to this exam
        $hasAccess = false;
        
        if (!$exam->batch_id) {
            // Exam is available to all students
            $hasAccess = true;
        } elseif ($student->batch_id && $exam->batch_id === $student->batch_id) {
            // Student's batch matches exam's batch
            $hasAccess = true;
        } elseif ($student->batches && $student->batches->contains('id', $exam->batch_id)) {
            // Student is enrolled in the exam's batch (many-to-many relationship)
            $hasAccess = true;
        }
        
        if (!$hasAccess) {
            return redirect()->route('student.exams')
                ->with('error', 'You do not have access to this exam. Please contact your administrator.');
        }

        // Validate exam time window using ExamTimeValidator
        $timeValidator = app(\App\Services\ExamTimeValidator::class);
        
        if (!$timeValidator->canStartExam($exam)) {
            $timeStatus = $timeValidator->getTimeStatus($exam);
            $message = $timeValidator->getTimeStatusMessage($exam);
            
            return redirect()->route('student.exams')
                ->with('error', $message);
        }

        // Create or retrieve ExamAttempt for student
        // First, check if any attempt exists (regardless of status)
        $attempt = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();
        
        // If no attempt exists, create one
        if (!$attempt) {
            $attempt = ExamAttempt::create([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'status' => 'in_progress',
                'started_at' => now(),
                'answers' => [],
                'cheating_events' => [],
                'screenshots' => [],
                'ip_address' => request()->ip(),
            ]);
        }
        
        // If attempt is already submitted, redirect to results
        if ($attempt->status === 'submitted') {
            $result = ExamResult::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->first();
            
            if ($result) {
                return redirect()->route('student.exam-result', $result->id)
                    ->with('info', 'You have already submitted this exam.');
            }
        }
        
        // If attempt exists but wasn't just created and has no started_at, set it
        if (!$attempt->started_at) {
            $attempt->update(['started_at' => now()]);
        }

        // Load CQ questions
        $questions = $exam->questions()
            ->where('type', 'cq')
            ->orderBy('order')
            ->get();

        // Calculate remaining time
        $remainingTime = $timeValidator->getRemainingTime($attempt);

        // Pass data to view
        return view('student.cq-exam', [
            'student' => $student,
            'exam' => $exam,
            'attempt' => $attempt,
            'questions' => $questions,
            'timeRemaining' => $remainingTime,
        ]);
    }

    /**
     * Upload screenshot for CQ exam answer.
     * 
     * Requirements: 3.3, 3.4
     * 
     * Task details:
     * - Validate file type (jpg/png/pdf) and size (max 5MB)
     * - Store file in storage/app/exam-screenshots
     * - Update exam_attempts screenshots JSON column
     * - Return success response with file path
     */
    public function uploadScreenshot(Request $request)
    {
        $student = $this->getStudent();
        
        if (!$student) {
            return response()->json(['error' => 'Student profile not found'], 403);
        }

        // Validate request
        $request->validate([
            'attempt_id' => 'required|exists:exam_attempts,id',
            'question_id' => 'required|integer',
            'screenshot' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB = 5120KB
        ]);

        // Get the exam attempt
        $attempt = ExamAttempt::findOrFail($request->attempt_id);

        // Verify ownership
        if ($attempt->student_id !== $student->id) {
            return response()->json(['error' => 'Unauthorized access to this exam attempt'], 403);
        }

        // Verify attempt is still in progress
        if ($attempt->status !== 'in_progress') {
            return response()->json(['error' => 'This exam attempt is no longer active'], 400);
        }

        try {
            // Store the file
            $file = $request->file('screenshot');
            $filename = 'exam_' . $attempt->exam_id . '_student_' . $student->id . '_q' . $request->question_id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('exam-screenshots', $filename);

            // Get existing screenshots or initialize empty array
            $screenshots = $attempt->screenshots ?? [];
            
            // Add new screenshot to the array
            $screenshots[$request->question_id] = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_at' => now()->toISOString(),
                'size' => $file->getSize(),
            ];

            // Update attempt with new screenshots
            $attempt->update([
                'screenshots' => $screenshots,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Screenshot uploaded successfully',
                'file_path' => $path,
                'question_id' => $request->question_id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Screenshot upload failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to upload screenshot. Please try again.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle course enrollment/purchase logic.
     */
    public function enroll(Course $course)
    {
        $student = $this->getStudent();

        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        // Check if course is purchased (approved payment)
        $hasPurchased = Payment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->whereIn('status', Payment::settledStatuses())
            ->exists();

        if ($hasPurchased) {
            // Check if added to batch
            if ($student->batch_id) {
                // Already in a batch - redirect to course content (using materials as simpler proxy for "course page")
                return redirect()->route('student.materials')->with('success', 'You are already enrolled.');
            } else {
                // Purchased but no batch - redirect to batch selection (or dashboard with warning for now)
                // Since batch selection page doesn't exist, we send to dashboard with instruction.
                return redirect()->route('student.dashboard')->with('info', 'Payment approved! Please contact admin to be assigned to a batch.');
            }
        } else {
            // Check if there is a pending payment
            $isPending = Payment::where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->where('status', Payment::STATUS_PENDING)
                ->exists();

            if ($isPending) {
                 return redirect()->route('student.payment.dashboard')->with('info', 'You already have a pending payment for this course.');
            }

            // Not purchased, redirect to payment form
            return redirect()->route('student.payment.form', $course->id);
        }
    }

    public function watchCourse(Course $course, ?CourseVideo $video = null)
    {
        $student = $this->getStudent();
        abort_unless($student && CourseEnrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)->exists(), 403, 'Purchase approval is required.');

        $videos = $course->videos()->get();
        abort_if($videos->isEmpty(), 404, 'This course has no videos yet.');

        if ($video) {
            abort_unless($video->course_id === $course->id, 404);
        } else {
            $firstIncompleteId = $videos->first(fn ($item) => !VideoView::where('student_id', $student->id)
                ->where('course_video_id', $item->id)->where('completed', true)->exists())?->id;
            $video = $videos->firstWhere('id', $firstIncompleteId) ?? $videos->first();
        }

        $progress = VideoView::where('student_id', $student->id)
            ->whereIn('course_video_id', $videos->pluck('id'))->get()->keyBy('course_video_id');
        $currentIndex = $videos->search(fn ($item) => $item->id === $video->id);
        $nextVideo = $videos->get($currentIndex + 1);

        return view('student.course-player', compact('course', 'video', 'videos', 'progress', 'nextVideo'));
    }

    public function completeVideo(Course $course, CourseVideo $video, Request $request)
    {
        $student = $this->getStudent();
        abort_unless($student && $video->course_id === $course->id
            && CourseEnrollment::where('student_id', $student->id)->where('course_id', $course->id)->exists(), 403);

        $data = $request->validate(['watched_seconds' => 'nullable|integer|min:0']);
        VideoView::updateOrCreate(
            ['student_id' => $student->id, 'course_video_id' => $video->id],
            ['watched_seconds' => $data['watched_seconds'] ?? $video->duration ?? 0, 'completed' => true, 'last_watched_at' => now()]
        );

        $next = $course->videos()->where(function ($query) use ($video) {
            $query->where('order', '>', $video->order)
                ->orWhere(fn ($q) => $q->where('order', $video->order)->where('id', '>', $video->id));
        })->orderBy('order')->orderBy('id')->first();

        $certificate = $next ? null : $this->certificateService->issueForCompletedCourse($student, $course);

        return response()->json([
            'completed' => true,
            'next_url' => $next ? route('student.course.video', [$course, $next]) : null,
            'certificate_url' => $certificate ? route('student.certificates.show', $certificate) : null,
        ]);
    }

    /**
     * Show CQ exam question paper.
     */
    public function showCqExam(Exam $exam)
    {
        $student = $this->getStudent();
        
        if (!$student) {
            abort(403, 'Student profile not found.');
        }
        
        // Check if student has access to this exam
        // Allow access if:
        // 1. Exam has no batch restriction (batch_id is null), OR
        // 2. Student's batch matches the exam's batch, OR
        // 3. Student is enrolled in a batch that has access to this exam
        $hasAccess = false;
        
        if (!$exam->batch_id) {
            // Exam is available to all students
            $hasAccess = true;
        } elseif ($student->batch_id && $exam->batch_id === $student->batch_id) {
            // Student's batch matches exam's batch
            $hasAccess = true;
        } elseif ($student->batches && $student->batches->contains('id', $exam->batch_id)) {
            // Student is enrolled in the exam's batch (many-to-many relationship)
            $hasAccess = true;
        }
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this exam. Please contact your administrator.');
        }

        // Load CQ questions
        $questions = $exam->questions()
            ->where('type', 'cq')
            ->orderBy('order')
            ->get();

        // Create or retrieve ExamAttempt for student
        $timeValidator = app(\App\Services\ExamTimeValidator::class);
        
        // First, check if any attempt exists (regardless of status)
        $attempt = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();
        
        // If no attempt exists, create one
        if (!$attempt) {
            $attempt = ExamAttempt::create([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'status' => 'in_progress',
                'started_at' => now(),
                'answers' => [],
                'cheating_events' => [],
                'screenshots' => [],
                'ip_address' => request()->ip(),
            ]);
        }
        
        // If attempt is already submitted, redirect to results
        if ($attempt->status === 'submitted') {
            $result = ExamResult::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->first();
            
            if ($result) {
                return redirect()->route('student.exam-result', $result->id)
                    ->with('info', 'You have already submitted this exam.');
            }
        }

        // Calculate remaining time
        $remainingTime = $timeValidator->getRemainingTime($attempt);

        $submission = CqSubmission::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        return view('student.cq-exam', [
            'student' => $student,
            'exam' => $exam,
            'questions' => $questions,
            'attempt' => $attempt,
            'timeRemaining' => $remainingTime,
            'submission' => $submission,
        ]);
    }

    /**
     * Upload CQ answer.
     */
    public function uploadCqAnswer(Request $request, Exam $exam)
    {
        $student = $this->getStudent();
        
        if (!$student) {
            abort(403, 'Student profile not found.');
        }
        
        // Check if student has access to this exam
        // Allow access if:
        // 1. Exam has no batch restriction (batch_id is null), OR
        // 2. Student's batch matches the exam's batch, OR
        // 3. Student is enrolled in a batch that has access to this exam
        $hasAccess = false;
        
        if (!$exam->batch_id) {
            // Exam is available to all students
            $hasAccess = true;
        } elseif ($student->batch_id && $exam->batch_id === $student->batch_id) {
            // Student's batch matches exam's batch
            $hasAccess = true;
        } elseif ($student->batches && $student->batches->contains('id', $exam->batch_id)) {
            // Student is enrolled in the exam's batch (many-to-many relationship)
            $hasAccess = true;
        }
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this exam. Please contact your administrator.');
        }

        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $errors = $this->examService->validateCqFiles($request->file('files'));
        
        if (!empty($errors)) {
            return back()->withErrors($errors);
        }

        $submission = $this->examService->submitCqAnswer($student, $exam, $request->file('files'));

        return redirect()->route('student.cq-submission', $submission->id)
            ->with('success', 'Answer uploaded successfully!');
    }

    /**
     * View CQ submission status.
     */
    public function viewCqSubmission(CqSubmission $submission)
    {
        $student = $this->getStudent();
        
        if (!$student || $submission->student_id !== $student->id) {
            abort(403, 'Unauthorized access to this submission.');
        }

        $submission->load('exam');

        return view('student.cq-submission', [
            'student' => $student,
            'submission' => $submission,
        ]);
    }

    /**
     * View all results with filtering.
     */
    public function results(Request $request)
    {
        $student = $this->getStudent();
        
        if (!$student) {
            // For admin users without student profile, show all results
            if (Auth::user()->isAdmin()) {
                $filters = $request->only(['exam_type', 'from_date', 'to_date']);
                
                $query = ExamResult::with(['student', 'exam']);
                
                if (!empty($filters['exam_type'])) {
                    $query->whereHas('exam', function($q) use ($filters) {
                        $q->where('type', $filters['exam_type']);
                    });
                }
                
                if (!empty($filters['from_date'])) {
                    $query->whereDate('created_at', '>=', $filters['from_date']);
                }
                
                if (!empty($filters['to_date'])) {
                    $query->whereDate('created_at', '<=', $filters['to_date']);
                }
                
                $results = $query->orderBy('created_at', 'desc')->paginate(20);
                
                // Calculate trends from all results
                $allResults = ExamResult::orderBy('created_at')->take(10)->get();
                $trends = [
                    'labels' => $allResults->map(fn($r) => $r->created_at->format('M d'))->toArray(),
                    'scores' => $allResults->map(fn($r) => $r->percentage)->toArray(),
                ];
                
                return view('student.results', [
                    'student' => null,
                    'results' => $results,
                    'trends' => $trends,
                    'filters' => $filters,
                ]);
            }
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        $filters = $request->only(['exam_type', 'from_date', 'to_date']);
        $results = $this->portalService->getResults($student, $filters);
        $trends = $this->portalService->getPerformanceTrends($student);

        return view('student.results', [
            'student' => $student,
            'results' => $results,
            'trends' => $trends,
            'filters' => $filters,
        ]);
    }

    /**
     * Download mark sheet.
     */
    public function downloadMarkSheet(ExamResult $result)
    {
        $student = $this->getStudent();
        
        if (!$student || $result->student_id !== $student->id) {
            abort(403, 'Unauthorized access to this mark sheet.');
        }

        return $this->markSheetService->generateMarkSheet($student, $result->exam);
    }

    /**
     * Performance trends for charts.
     */
    public function performanceTrends()
    {
        $student = $this->getStudent();
        
        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $trends = $this->portalService->getPerformanceTrends($student);

        return response()->json($trends);
    }

    /**
     * Browse all available courses.
     * 
     * Requirements: 10.1
     * 
     * Task details:
     * - Fetch all active courses
     * - Get authenticated student's enrolled course IDs
     * - Get course IDs with pending payments
     * - Pass data to view
     */
    public function browse()
    {
        $student = $this->getStudent();
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        // Fetch all active courses
        $courses = \App\Models\Course::active()
            ->with(['batches'])
            ->where('delivery_mode', request()->session()->get('course_mode', 'online'))
            ->orderBy('name')
            ->get();

        // Get authenticated student's enrolled course IDs
        // Student is enrolled through batch, and batch belongs to a course
        $enrolledCourseIds = CourseEnrollment::where('student_id', $student->id)->pluck('course_id')->all();
        if ($student->batch_id) {
            $batch = \App\Models\Batch::find($student->batch_id);
            if ($batch && $batch->course_id) {
                $enrolledCourseIds[] = $batch->course_id;
            }
        }

        // Also check if student is enrolled in multiple courses through different batches
        // by checking if there are any approved payments that led to enrollments
        $approvedPayments = Payment::where('student_id', $student->id)
            ->whereIn('status', Payment::settledStatuses())
            ->whereNotNull('course_id')
            ->pluck('course_id')
            ->toArray();
        
        $enrolledCourseIds = array_unique(array_merge($enrolledCourseIds, $approvedPayments));

        // Get course IDs with pending payments
        $pendingPaymentCourseIds = Payment::where('student_id', $student->id)
            ->where('status', Payment::STATUS_PENDING)
            ->whereNotNull('course_id')
            ->pluck('course_id')
            ->toArray();

        // Pass data to view
        return view('student.courses', [
            'student' => $student,
            'courses' => $courses,
            'enrolledCourseIds' => $enrolledCourseIds,
            'pendingPaymentCourseIds' => $pendingPaymentCourseIds,
        ]);
    }

    public function courses()
    {
        return $this->browse();
    }
}
