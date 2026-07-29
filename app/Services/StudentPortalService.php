<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Announcement;
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
            'attendance' => $this->getAttendancePercentage($student),
            'upcoming_exams' => $this->getUpcomingExams($student),
            'recent_results' => $this->getRecentResults($student),
            'announcements' => $this->getAnnouncements($student),
        ];
    }

    /**
     * Get payment summary for a student.
     */
    public function getPaymentSummary(Student $student): array
    {
        $totalFee = $student->batch?->course?->fee ?? 0;
        $paidAmount = $student->payments()->whereIn('status', Payment::settledStatuses())->sum('amount');
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
            'labels' => $results->pluck('exam.name')->toArray(),
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
        $courseId = $student->batch?->course_id;

        if (!$courseId) {
            return collect();
        }

        return \App\Models\CourseMaterial::where('course_id', $courseId)
            ->orderBy('order')
            ->get()
            ->groupBy('type');
    }
}
