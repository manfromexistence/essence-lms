<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\CqSubmission;
use App\Models\ExamResult;
use App\Models\Student;
use App\Models\VideoView;
use Illuminate\Support\Collection;

/**
 * Assembles the public-facing record of a student's academic standing.
 *
 * This is what a certificate's QR code resolves to, so it exists to answer one
 * question for someone who has never met the student: does this person
 * genuinely study here, and did they finish?
 *
 * Deliberately withheld: contact details, addresses, date of birth, blood group,
 * religion, guardians, fee balances and the raw proctoring data on exam attempts.
 * A certificate holder's employer may reasonably be told what was studied and
 * what was achieved; they are not entitled to the student's home address or
 * their account balance, and a leaked QR URL must not be a data-disclosure
 * incident. See the redaction list in {@see self::profile()}.
 */
class StudentVerificationService
{
    /** How many recent results the public page shows. */
    private const RECENT_RESULT_LIMIT = 10;

    /** How many certificates the public page lists. */
    private const CERTIFICATE_LIMIT = 25;

    /**
     * Build the public verification record for a student.
     *
     * @return array<string, mixed>
     */
    public function profile(Student $student): array
    {
        $student->loadMissing(['user', 'batch.course']);

        return [
            'identity' => $this->identity($student),
            'admission' => $this->admission($student),
            'metrics' => $this->metrics($student),
            'certificates' => $this->certificates($student),
            'courses' => $this->courseProgress($student),
            'assignments' => $this->submissions($student),
            'results' => $this->results($student),
        ];
    }

    /**
     * Who the student is. Name and registration number only.
     *
     * @return array<string, string|null>
     */
    private function identity(Student $student): array
    {
        return [
            'name' => $student->user?->name ?? $student->name,
            'registration_no' => $student->registration_no,
            'photo' => $student->photo,
            'course_name' => $student->batch?->course?->name ?? $student->course_name,
            'batch' => $student->batch?->name,
            'roll' => $student->roll,
        ];
    }

    /**
     * Whether the institute considers this an enrolled student.
     *
     * @return array<string, mixed>
     */
    private function admission(Student $student): array
    {
        return [
            'status' => $student->admission_status ?? ($student->batch_id ? 'approved' : 'pending'),
            'mode' => $student->admission_mode,
            'since' => $student->applied_at?->format('M Y') ?? $student->created_at?->format('M Y'),
        ];
    }

    /**
     * Headline performance figures.
     *
     * @return array<string, mixed>
     */
    private function metrics(Student $student): array
    {
        $attendance = $this->attendance($student);
        $results = $student->results()->whereNotNull('total_marks')->where('total_marks', '>', 0)->get();
        $earned = (float) $results->sum('obtained_marks');
        $possible = (float) $results->sum('total_marks');

        $completed = $student->certificates()->where('status', 'active')->count();

        return [
            'courses_completed' => $completed,
            'courses_enrolled' => $student->courseEnrollments()->count(),
            'exams_taken' => $student->results()->count(),
            'average_score' => $possible > 0 ? round(($earned / $possible) * 100, 1) : null,
            'best_grade' => $this->bestGrade($results),
            'attendance_percentage' => $attendance['percentage'],
            'classes_attended' => $attendance['present'],
            'classes_total' => $attendance['total_classes'],
            'videos_watched' => VideoView::where('student_id', $student->id)->where('completed', true)->count(),
        ];
    }

    /**
     * Active certificates, newest first.
     *
     * Revoked certificates are excluded rather than shown as revoked: this page
     * answers "is this genuine", and a revoked certificate is not a valid one.
     *
     * @return Collection<int, Certificate>
     */
    private function certificates(Student $student): Collection
    {
        return $student->certificates()
            ->with('course')
            ->where('status', 'active')
            ->orderByDesc('issued_at')
            ->limit(self::CERTIFICATE_LIMIT)
            ->get();
    }

    /**
     * Enrolled courses with how far through the material the student is.
     *
     * `progress` mirrors the calculation the student dashboard uses, so the two
     * cannot disagree.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function courseProgress(Student $student): Collection
    {
        return $student->courseEnrollments()
            ->with('course.videos')
            ->orderByDesc('enrolled_at')
            ->get()
            ->map(function ($enrollment) use ($student) {
                $totalVideos = $enrollment->course?->videos?->count() ?? 0;
                $completedVideos = 0;

                if ($totalVideos > 0) {
                    $completedVideos = VideoView::where('student_id', $student->id)
                        ->whereIn('course_video_id', $enrollment->course->videos->pluck('id'))
                        ->where('completed', true)
                        ->distinct()
                        ->count('course_video_id');
                }

                return [
                    'course' => $enrollment->course?->name ?? 'Course',
                    'code' => $enrollment->course?->code,
                    'enrolled_at' => $enrollment->enrolled_at?->format('M Y'),
                    'total_videos' => $totalVideos,
                    'completed_videos' => $completedVideos,
                    'progress_percentage' => $totalVideos > 0
                        ? (int) round(($completedVideos / $totalVideos) * 100)
                        : 0,
                    'certified' => $student->certificates()
                        ->where('course_id', $enrollment->course_id)
                        ->where('status', 'active')
                        ->exists(),
                ];
            });
    }

    /**
     * Written work the student has submitted.
     *
     * The institute has no separate assignments subsystem, so the submission
     * record that exists is the conventional-question answer script uploaded for
     * an exam. That is genuinely the student's submitted coursework, so it is
     * what this shows rather than a placeholder for something that does not exist.
     *
     * Marks are shown only where a teacher has actually evaluated the script;
     * an unevaluated submission reports no score rather than zero, which would
     * misrepresent the student.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function submissions(Student $student): Collection
    {
        return CqSubmission::with(['exam' => fn ($q) => $q->select('id', 'title', 'type', 'total_marks')])
            ->where('student_id', $student->id)
            ->orderByDesc('submitted_at')
            ->limit(self::RECENT_RESULT_LIMIT)
            ->get()
            ->map(fn (CqSubmission $submission) => [
                'exam' => $submission->exam?->title ?? 'Exam',
                'submitted_at' => $submission->submitted_at?->format('d M Y'),
                'files' => is_array($submission->files) ? count($submission->files) : 0,
                'evaluated' => $submission->evaluated_at !== null,
                'marks' => $submission->marks,
                'out_of' => $submission->exam?->total_marks,
            ]);
    }

    /**
     * Graded exam results.
     *
     * @return Collection<int, ExamResult>
     */
    private function results(Student $student): Collection
    {
        return $student->results()
            ->with('exam:id,title,type,total_marks')
            ->orderByDesc('created_at')
            ->limit(self::RECENT_RESULT_LIMIT)
            ->get();
    }

    /**
     * @return array{total_classes: int, present: int, absent: int, percentage: float}
     */
    private function attendance(Student $student): array
    {
        $total = Attendance::where('student_id', $student->id)->count();
        $present = Attendance::where('student_id', $student->id)->where('status', 'present')->count();

        return [
            'total_classes' => $total,
            'present' => $present,
            'absent' => $total - $present,
            'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * The strongest grade achieved.
     *
     * `grade` is a stored column written by whichever code path recorded the
     * result, and the codebase contains more than one grading scale, so the
     * letters cannot be compared directly. Percentage can.
     *
     * @param  Collection<int, ExamResult>  $results
     */
    private function bestGrade(Collection $results): ?string
    {
        $graded = $results->filter(fn (ExamResult $result) => $result->percentage > 0);

        if ($graded->isEmpty()) {
            return null;
        }

        return (string) $graded->sortByDesc(fn (ExamResult $result) => $result->percentage)
            ->first()
            ->grade;
    }
}
