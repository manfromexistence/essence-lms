<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Announcement;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseVideo;
use App\Models\VideoView;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentPortalService
{
    /**
     * Get all dashboard data for a student.
     */
    public function getDashboardData(Student $student): array
    {
        $student->load(['batch.course', 'payments']);

        return [
            'student' => $student,
            'batch' => $student->batch,
            'course' => $student->batch?->course,
            'payment_summary' => $this->getPaymentSummary($student),
            'course_progress' => $this->getCourseProgress($student),
            'certificates' => $this->getCertificates($student),
            'announcements' => $this->getAnnouncements($student),
        ];
    }

    /**
     * Get per-course learning progress for a student.
     */
    public function getCourseProgress(Student $student): Collection
    {
        $enrollments = CourseEnrollment::with(['course.videos'])
            ->where('student_id', $student->id)
            ->orderBy('enrolled_at', 'desc')
            ->get();

        $progress = [];
        foreach ($enrollments as $enrollment) {
            $course = $enrollment->course;
            if (!$course) {
                continue;
            }

            $videos = $course->videos;
            $completedVideos = VideoView::where('student_id', $student->id)
                ->whereIn('course_video_id', $videos->pluck('id'))
                ->where('completed', true)
                ->pluck('course_video_id')
                ->toArray();

            $nextVideo = $videos->first(fn ($item) => !in_array($item->id, $completedVideos));

            $progress[] = [
                'course' => $course,
                'enrollment' => $enrollment,
                'total_videos' => $videos->count(),
                'completed_videos' => count($completedVideos),
                'progress_percentage' => $videos->count() > 0
                    ? (int) round((count($completedVideos) / $videos->count()) * 100)
                    : 0,
                'next_video' => $nextVideo,
            ];
        }

        return collect($progress);
    }

    /**
     * Get the student's own certificates.
     */
    public function getCertificates(Student $student): Collection
    {
        return Certificate::with('course')
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->orderBy('issued_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get payment summary for a student.
     */
    public function getPaymentSummary(Student $student): array
    {
        $courseIds = CourseEnrollment::where('student_id', $student->id)->pluck('course_id')
            ->merge(
                Payment::where('student_id', $student->id)
                    ->whereNotNull('course_id')
                    ->pluck('course_id')
            )
            ->unique()
            ->values();

        $totalFee = Course::whereIn('id', $courseIds)->sum('price');
        $paidAmount = $courseIds->isNotEmpty()
            ? Payment::where('student_id', $student->id)
                ->whereIn('status', Payment::settledStatuses())
                ->whereIn('course_id', $courseIds)
                ->sum('amount')
            : $student->payments()->whereIn('status', Payment::settledStatuses())->sum('amount');
        $dueAmount = max(0, $totalFee - $paidAmount);

        return [
            'total_fee' => $totalFee,
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'payment_percentage' => $totalFee > 0 ? round(($paidAmount / $totalFee) * 100, 1) : 0,
            'is_fully_paid' => $dueAmount <= 0,
        ];
    }

    /**
     * Get attendance percentage for a student.
     */
    public function getAttendancePercentage(Student $student, ?Carbon $month = null): array
    {
        $query = Attendance::where('student_id', $student->id);

        if ($month) {
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();
            $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
        }

        $totalClasses = $query->count();
        $presentClasses = (clone $query)->where('status', 'present')->count();
        $percentage = $totalClasses > 0 ? round(($presentClasses / $totalClasses) * 100, 1) : 0;

        return [
            'total_classes' => $totalClasses,
            'present' => $presentClasses,
            'absent' => $totalClasses - $presentClasses,
            'percentage' => $percentage,
        ];
    }

    /**
     * Get upcoming exams for a student.
     */
    public function getUpcomingExams(Student $student): Collection
    {
        // Get all active exams that the student has access to
        // Either exams with no batch restriction OR exams for the student's batch
        $query = Exam::where('status', 'active')
            ->where(function($q) use ($student) {
                $q->whereNull('batch_id')
                  ->orWhere('batch_id', $student->batch_id);
            });

        // Get exam IDs that the student has already completed
        $completedExamIds = ExamResult::where('student_id', $student->id)
            ->pluck('exam_id')
            ->toArray();

        // Exclude completed exams
        if (!empty($completedExamIds)) {
            $query->whereNotIn('id', $completedExamIds);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get recent exam results for a student.
     */
    public function getRecentResults(Student $student): Collection
    {
        return ExamResult::where('student_id', $student->id)
            ->with('exam')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get announcements for a student.
     */
    public function getAnnouncements(Student $student): Collection
    {
        return Announcement::active()
            ->forStudent($student)
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get all exam results for a student with filtering.
     */
    public function getResults(Student $student, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = ExamResult::where('student_id', $student->id)
            ->with(['exam']);

        if (!empty($filters['exam_type'])) {
            $query->whereHas('exam', function ($q) use ($filters) {
                $q->where('type', $filters['exam_type']);
            });
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    /**
     * Get performance trends for charts.
     */
    public function getPerformanceTrends(Student $student): array
    {
        $results = ExamResult::where('student_id', $student->id)
            ->with('exam')
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        return [
            'labels' => $results->pluck('exam.title')->toArray(),
            'scores' => $results->pluck('percentage')->toArray(),
            'dates' => $results->pluck('created_at')->map(fn($d) => $d->format('M d'))->toArray(),
        ];
    }

    /**
     * Get class schedule for a student.
     */
    public function getSchedule(Student $student): Collection
    {
        if (!$student->batch_id) {
            return collect();
        }

        return $student->batch->schedules()->orderBy('day_of_week')->orderBy('start_time')->get();
    }

    /**
     * Get payment history for a student.
     */
    public function getPaymentHistory(Student $student): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Payment::where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    /**
     * Get course materials for a student.
     */
    public function getMaterials(Student $student): Collection
    {
        $courseIds = CourseEnrollment::where('student_id', $student->id)->pluck('course_id');

        if ($student->batch_id && $student->batch?->course_id) {
            $courseIds->push($student->batch->course_id);
        }

        $courseIds = $courseIds->unique();

        if ($courseIds->isEmpty()) {
            return collect();
        }

        return \App\Models\CourseMaterial::whereIn('course_id', $courseIds)
            ->orderBy('order')
            ->get()
            ->groupBy('type');
    }
}
