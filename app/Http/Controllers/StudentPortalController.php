<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseMaterial;
use App\Models\CourseVideo;
use App\Models\CqSubmission;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\Payment;
use App\Models\Question;
use App\Models\Student;
use App\Models\VideoView;
use App\Rules\SafeUpload;
use App\Services\CertificateService;
use App\Services\ExamTakingService;
use App\Services\ExamTimeValidator;
use App\Services\MarkSheetService;
use App\Services\StudentPortalService;
use App\Storage\CatboxStorage;
use App\Storage\CatboxUploadFailed;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     * Enforce the exam's scheduled time window before a student starts or submits.
     */
    private function assertExamAvailable(Exam $exam): void
    {
        $validator = app(ExamTimeValidator::class);

        if (! $validator->canStartExam($exam)) {
            abort(403, $validator->getTimeStatusMessage($exam) ?: 'This exam is not currently available.');
        }
    }

    /**
     * Ensure the student's batch has access to this exam.
     */
    private function assertExamAccess(Student $student, Exam $exam): void
    {
        if (! $exam->batch_id) {
            return;
        }

        if ($exam->batch_id === $student->batch_id || $exam->batch_id === $student->batch?->id) {
            return;
        }

        abort(403, 'You do not have access to this exam. Please contact your administrator.');
    }

    /**
     * Student dashboard.
     */
    public function dashboard()
    {
        $student = $this->getStudent();

        if (! $student) {
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

        if (! $student) {
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

        $hasCourseAccess = $student && CourseEnrollment::where('student_id', $student->id)
            ->where('course_id', $material->course_id)
            ->exists();
        $hasBatchAccess = $student && $student->batch?->course_id === $material->course_id;

        if (! $hasCourseAccess && ! $hasBatchAccess) {
            abort(403, 'Unauthorized access to this material.');
        }

        if ($material->type === 'link') {
            $url = $material->file_path;
            if (! preg_match('#^https?://#i', $url)) {
                abort(404, 'Invalid material link.');
            }

            return redirect()->away($url);
        }

        // A material can legitimately have no file (e.g. a placeholder row).
        $url = app(CatboxStorage::class)->url($material->file_path);

        if ($url === null) {
            return back()->with('error', 'File not found.');
        }

        return redirect()->away($url);
    }

    public function streamVideo(Course $course, CourseVideo $video)
    {
        abort_unless($video->course_id === $course->id, 404);
        $student = $this->getStudent();
        $authorized = Auth::user()->isAdmin() || ($student && CourseEnrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)->exists());
        abort_unless($authorized, 403);

        $url = app(CatboxStorage::class)->url($video->video_path);
        abort_unless($url, 404);

        // Handed to the media host directly so that Range requests, and therefore
        // seeking, behave normally instead of depending on PHP streaming.
        return redirect()->away($url);
    }

    /**
     * Class schedule.
     */
    public function schedule()
    {
        $student = $this->getStudent();

        if (! $student) {
            // For admin users without student profile, show all schedules
            if (Auth::user()->isAdmin()) {
                $schedules = ClassSchedule::with(['batch', 'teacher'])
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

        if (! $student) {
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

        if (! $student || $payment->student_id !== $student->id) {
            abort(403, 'Unauthorized access to this receipt.');
        }

        $payment->load(['student.batch.course']);

        $pdf = Pdf::loadView('pdf.receipt', [
            'payment' => $payment,
            'student' => $student,
        ]);

        return $pdf->download('receipt-'.$payment->id.'.pdf');
    }

    /**
     * List exams.
     */
    public function exams()
    {
        $student = $this->getStudent();

        if (! $student) {
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

        if (! $student) {
            abort(403, 'Student profile not found.');
        }

        $this->assertExamAccess($student, $exam);

        $this->assertExamAvailable($exam);

        $attempt = $this->examService->startAttempt($student, $exam);
        $examData = $this->examService->getExamWithTimer($attempt);

        return view('student.exam-take', $examData);
    }

    /**
     * Save answer via AJAX.
     */
    public function saveAnswer(Request $request, ExamAttempt $attempt)
    {
        $student = $this->getStudent();

        if (! $student || $attempt->student_id !== $student->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            // Bounded, and must be a question of THIS exam.
            //
            // Previously 'required|integer' with no length cap and no ownership
            // check, so a student could post a multi-megabyte string under an
            // arbitrary key — including the id of a question belonging to a
            // different exam — growing the answers column without limit. Two
            // 3 MB posts produced a 6.29 MB value in a single attempt.
            'question_id' => 'required|integer|min:1|exists:questions,id',
            'answer' => 'required|string|max:2000',
        ]);

        $belongsToThisExam = Question::where('id', $request->question_id)
            ->where('exam_id', $attempt->exam_id)
            ->exists();

        if (! $belongsToThisExam) {
            return response()->json(['error' => 'Unknown question for this exam'], 422);
        }

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

        if (! $student || $attempt->student_id !== $student->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'event_type' => 'required|string|in:tab_switch,fullscreen_exit',
            // Bounded: the value is stored verbatim in an unbounded json column,
            // so an uncapped string let a student append ~post_max_size per POST.
            'timestamp' => 'required|string|max:64',
        ]);

        // The attempt must still be live. This endpoint previously had neither a
        // status nor a deadline check, so events could be appended to an already
        // submitted or long-expired attempt indefinitely.
        if (! $attempt->isInProgress()) {
            return response()->json(['error' => 'This attempt is no longer active'], 422);
        }

        // Get existing cheating events or initialize empty array
        $cheatingEvents = $attempt->cheating_events ?? [];

        // Cap the retained history. The column is unbounded json, so without this
        // a long exam plus a determined student grows it without limit.
        if (count($cheatingEvents) >= 200) {
            $cheatingEvents = array_slice($cheatingEvents, -200);
        }

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

        if (! $student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        $this->assertExamAccess($student, $exam);

        $this->assertExamAvailable($exam);

        // Validate exam attempt ownership
        // First try to find an in-progress attempt
        $attempt = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('status', 'in_progress')
            ->first();

        // If no in-progress attempt, check if already submitted
        if (! $attempt) {
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

        if ($exam->type === 'cq') {
            $textAnswers = [];
            foreach ((array) $request->input('answers', []) as $questionId => $answer) {
                if (! is_numeric($questionId)) {
                    continue;
                }
                $text = is_array($answer) ? ($answer['text'] ?? '') : $answer;
                if (trim(strip_tags((string) $text)) !== '') {
                    $textAnswers[(int) $questionId] = (string) $text;
                }
            }

            if ($textAnswers !== []) {
                $this->examService->saveCqTextAnswers($attempt, $textAnswers);
            }
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

        if (! $student || $result->student_id !== $student->id) {
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

        if (! $student) {
            return response()->json(['error' => 'Student profile not found'], 403);
        }

        // Validate request
        $request->validate([
            'attempt_id' => 'required|exists:exam_attempts,id',
            'question_id' => 'required|integer|min:1',
            'screenshot' => [
                'required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120',
                new SafeUpload(['jpg', 'jpeg', 'png', 'pdf']),
            ],
        ]);

        // Get the exam attempt
        $attempt = ExamAttempt::findOrFail($request->attempt_id);

        // Verify ownership
        if ($attempt->student_id !== $student->id) {
            return response()->json(['error' => 'Unauthorized access to this exam attempt'], 403);
        }

        // The screenshot array is keyed by question_id, so an arbitrary id adds
        // a NEW entry rather than replacing one, and each entry costs a
        // permanent object on the keyless shared host. Require the id to be a
        // question of this attempt's exam.
        $questionBelongsToExam = Question::where('id', $request->question_id)
            ->where('exam_id', $attempt->exam_id)
            ->exists();

        if (! $questionBelongsToExam) {
            return response()->json(['error' => 'Unknown question for this exam'], 422);
        }

        // Verify attempt is still in progress and still within its time.
        if ($attempt->status !== 'in_progress') {
            return response()->json(['error' => 'This exam attempt is no longer active'], 400);
        }

        if ($attempt->isExpired()) {
            return response()->json(['error' => 'This exam attempt has ended'], 400);
        }

        try {
            $file = $request->file('screenshot');

            $url = app(CatboxStorage::class)->store(
                $file,
                'exam-screenshots',
                sprintf(
                    'exam-%d-student-%d-q%d',
                    $attempt->exam_id,
                    $student->id,
                    $request->question_id,
                )
            );

            $screenshots = $attempt->screenshots ?? [];

            $screenshots[$request->question_id] = [
                'path' => $url,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_at' => now()->toISOString(),
                'size' => $file->getSize(),
            ];

            $attempt->update([
                'screenshots' => $screenshots,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Screenshot uploaded successfully',
                'file_path' => $url,
                'question_id' => $request->question_id,
            ]);

        } catch (CatboxUploadFailed $e) {
            \Log::warning('Screenshot rejected by the media host.', ['error' => $e->reason]);

            return response()->json(['error' => $e->getMessage()], 503);
        } catch (\Exception $e) {
            \Log::error('Screenshot upload failed: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to upload screenshot. Please try again.',
            ], 500);
        }
    }

    /**
     * Handle course enrollment/purchase logic.
     */
    public function enroll(Course $course)
    {
        $student = $this->getStudent();

        if (! $student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        // Check if course is purchased (approved payment)
        $hasPurchased = Payment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->whereIn('status', Payment::settledStatuses())
            ->exists();

        if ($hasPurchased) {
            if ($student->batch_id) {
                return redirect()->route('student.course.watch', $course)->with('success', 'You are already enrolled.');
            }

            $batches = Batch::where('course_id', $course->id)->active()->orderBy('name')->get();

            return view('student.batch-select', compact('course', 'student', 'batches'));
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

    /**
     * Save the student's batch after a settled course payment.
     */
    public function selectBatch(Request $request, Course $course)
    {
        $student = $this->getStudent();
        abort_unless($student, 403);

        abort_unless(Payment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->whereIn('status', Payment::settledStatuses())
            ->exists(), 403, 'Purchase approval is required.');

        // Batch assignment is the office's decision, made once. Without this
        // guard a student with one settled payment could POST here repeatedly and
        // move themselves into any other active batch of the course. Since
        // assertExamAccess compares the exam's batch against the student's, that
        // also handed them access to another batch's exams.
        // enroll() already refused a second assignment; this path did not.
        if ($student->batch_id) {
            return redirect()
                ->route('student.dashboard')
                ->with('info', 'You are already enrolled in a batch. Please contact the office to change it.');
        }

        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
        ]);

        $batch = Batch::where('id', $validated['batch_id'])
            ->where('course_id', $course->id)
            ->active()
            ->firstOrFail();

        if ($batch->max_students && $batch->students()->count() >= $batch->max_students && $student->batch_id !== $batch->id) {
            return back()->with('error', 'This batch is full. Please choose another batch.');
        }

        $student->update(['batch_id' => $batch->id]);
        CourseEnrollment::updateOrCreate(
            ['student_id' => $student->id, 'course_id' => $course->id],
            ['batch_id' => $batch->id, 'enrolled_at' => now()]
        );

        return redirect()->route('student.course.watch', $course)->with('success', 'Batch assigned. Happy learning!');
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
            $firstIncompleteId = $videos->first(fn ($item) => ! VideoView::where('student_id', $student->id)
                ->where('course_video_id', $item->id)->where('completed', true)->exists())?->id;
            $video = $videos->firstWhere('id', $firstIncompleteId) ?? $videos->first();
        }

        $progress = VideoView::where('student_id', $student->id)
            ->whereIn('course_video_id', $videos->pluck('id'))->get()->keyBy('course_video_id');
        $currentIndex = $videos->search(fn ($item) => $item->id === $video->id);
        $nextVideo = $videos->get($currentIndex + 1);

        // Opening the player starts the clock for this video. completeVideo
        // credits watch time only up to what could genuinely have elapsed since
        // this moment, so a certificate cannot be obtained by posting to the
        // completion endpoint without spending the time.
        //
        // An already-completed video keeps its row untouched, so revisiting a
        // finished lesson does not reset the recorded progress.
        $opened = VideoView::firstOrNew([
            'student_id' => $student->id,
            'course_video_id' => $video->id,
        ]);

        if (! $opened->exists || ! $opened->completed) {
            $opened->watched_seconds = $opened->watched_seconds ?? 0;
            $opened->last_watched_at = now();
            $opened->save();
        }

        return view('student.course-player', compact('course', 'video', 'videos', 'progress', 'nextVideo'));
    }

    public function completeVideo(Course $course, CourseVideo $video, Request $request)
    {
        $student = $this->getStudent();
        abort_unless($student && $video->course_id === $course->id
            && CourseEnrollment::where('student_id', $student->id)->where('course_id', $course->id)->exists(), 403);

        $data = $request->validate(['watched_seconds' => 'nullable|integer|min:0']);

        // Watch time is credited from the server's own clock, not from the
        // number the browser sent. A client-reported figure is attacker input:
        // it previously had to merely exceed the duration, so posting
        // watched_seconds=999999 once per video marked every video complete
        // and — because CertificateService treats "all videos complete" as
        // finished — issued a real, publicly verifiable certificate without any
        // playback at all.
        //
        // The player posts progress as it goes, so crediting only what could
        // genuinely have elapsed since the previous post means completing a
        // course now takes the time it actually takes.
        $view = VideoView::where('student_id', $student->id)
            ->where('course_video_id', $video->id)
            ->first();

        $previousTotal = (int) ($view->watched_seconds ?? 0);

        // Time since the player was opened. watchCourse starts this clock; if
        // the row is somehow missing, elapsed is zero, which credits nothing.
        //
        // Computed from raw timestamps rather than Carbon's diffInSeconds(),
        // whose sign convention differs between Carbon major versions.
        $elapsed = $view?->last_watched_at
            ? max(0, now()->getTimestamp() - $view->last_watched_at->getTimestamp())
            : 0;

        // A small grace covers the gap between opening the page and the first
        // playback, and ordinary clock skew. It is deliberately far smaller than
        // any real lesson, so it cannot be used to skip one.
        $graceSeconds = 15;

        $credibleNow = min((int) ($data['watched_seconds'] ?? 0), $elapsed + $graceSeconds);

        // The running total can never exceed the video length, and each attempt
        // moves the clock forward, so repeated calls cannot bank credit faster
        // than real time passes.
        $watchedSeconds = min(
            $video->duration ? (int) $video->duration : PHP_INT_MAX,
            $previousTotal + $credibleNow,
        );

        // The clock restarts on every attempt, so the next call is again
        // measured from now.
        VideoView::updateOrCreate(
            ['student_id' => $student->id, 'course_video_id' => $video->id],
            ['watched_seconds' => $watchedSeconds, 'last_watched_at' => now()],
        );

        // Require a meaningful watch duration. Where the duration is unknown,
        // fall back to the grace window rather than an arbitrary 15 seconds.
        $minRequired = $video->duration
            ? (int) floor($video->duration * 0.9)
            : $graceSeconds;

        if ($watchedSeconds < max(1, $minRequired)) {
            return response()->json([
                'completed' => false,
                'error' => 'Video was not fully watched. Progress not saved.',
            ], 422);
        }

        // Sequential enforcement: all earlier videos must already be completed.
        $previousIncomplete = $course->videos()
            ->where(function ($query) use ($video) {
                $query->where('order', '<', $video->order)
                    ->orWhere(fn ($q) => $q->where('order', $video->order)->where('id', '<', $video->id));
            })
            ->whereNotIn('id', VideoView::where('student_id', $student->id)
                ->where('completed', true)
                ->pluck('course_video_id'))
            ->exists();

        if ($previousIncomplete) {
            return response()->json([
                'completed' => false,
                'error' => 'Complete earlier videos before this one.',
            ], 422);
        }

        // Only now is the lesson genuinely finished; the progress row above is
        // deliberately written as incomplete.
        VideoView::updateOrCreate(
            ['student_id' => $student->id, 'course_video_id' => $video->id],
            ['watched_seconds' => $watchedSeconds, 'completed' => true, 'last_watched_at' => now()]
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

        if (! $student) {
            abort(403, 'Student profile not found.');
        }

        $this->assertExamAccess($student, $exam);

        $this->assertExamAvailable($exam);

        // Load CQ questions
        $questions = $exam->questions()
            ->where('type', 'cq')
            ->orderBy('order')
            ->get();

        // Create or retrieve ExamAttempt for student
        $timeValidator = app(ExamTimeValidator::class);

        // First, check if any attempt exists (regardless of status)
        $attempt = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        // If no attempt exists, create one
        if (! $attempt) {
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

        if (! $student) {
            abort(403, 'Student profile not found.');
        }

        $this->assertExamAccess($student, $exam);

        $this->assertExamAvailable($exam);

        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $errors = $this->examService->validateCqFiles($request->file('files'));

        if (! empty($errors)) {
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

        if (! $student || $submission->student_id !== $student->id) {
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

        if (! $student) {
            // For admin users without student profile, show all results
            if (Auth::user()->isAdmin()) {
                $filters = $request->only(['exam_type', 'from_date', 'to_date']);

                $query = ExamResult::with(['student', 'exam']);

                if (! empty($filters['exam_type'])) {
                    $query->whereHas('exam', function ($q) use ($filters) {
                        $q->where('type', $filters['exam_type']);
                    });
                }

                if (! empty($filters['from_date'])) {
                    $query->whereDate('created_at', '>=', $filters['from_date']);
                }

                if (! empty($filters['to_date'])) {
                    $query->whereDate('created_at', '<=', $filters['to_date']);
                }

                $results = $query->orderBy('created_at', 'desc')->paginate(20);

                // Calculate trends from all results
                $allResults = ExamResult::orderBy('created_at')->take(10)->get();
                $trends = [
                    'labels' => $allResults->map(fn ($r) => $r->created_at->format('M d'))->toArray(),
                    'scores' => $allResults->map(fn ($r) => $r->percentage)->toArray(),
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

        if (! $student || $result->student_id !== $student->id) {
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

        if (! $student) {
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

        if (! $student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        // Fetch all active courses
        $courses = Course::active()
            ->with(['batches'])
            ->where('delivery_mode', request()->session()->get('course_mode', 'online'))
            ->orderBy('name')
            ->get();

        // Get authenticated student's enrolled course IDs
        // Student is enrolled through batch, and batch belongs to a course
        $enrolledCourseIds = CourseEnrollment::where('student_id', $student->id)->pluck('course_id')->all();
        if ($student->batch_id) {
            $batch = Batch::find($student->batch_id);
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
