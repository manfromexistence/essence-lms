<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Valid attendance status values.
     */
    public const VALID_STATUSES = ['present', 'absent', 'late', 'excused'];

    public function __construct(
        protected SettingsService $settingsService
    ) {}

    /**
     * Record attendance for a batch on a specific date.
     */
    public function recordAttendance(Batch $batch, Carbon $date, array $attendanceData): void
    {
        DB::transaction(function () use ($batch, $date, $attendanceData) {
            foreach ($attendanceData as $studentId => $status) {
                // Validate status
                if (! in_array($status, self::VALID_STATUSES)) {
                    throw new \InvalidArgumentException("Invalid attendance status: {$status}");
                }

                // Update or create attendance record (prevents duplicates)
                Attendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'batch_id' => $batch->id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'status' => $status,
                    ]
                );
            }
        });
    }

    /**
     * Get attendance records for a batch.
     */
    public function getAttendanceByBatch(Batch $batch, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $query = Attendance::where('batch_id', $batch->id)
            ->with(['student.user']);

        if ($startDate) {
            $query->where('date', '>=', $startDate->toDateString());
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate->toDateString());
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Get attendance records for a student.
     */
    public function getStudentAttendance(Student $student, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $query = Attendance::where('student_id', $student->id)
            ->with(['batch']);

        if ($startDate) {
            $query->where('date', '>=', $startDate->toDateString());
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate->toDateString());
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Calculate attendance percentage for a student in a batch.
     */
    public function calculateAttendancePercentage(Student $student, ?Batch $batch = null): float
    {
        $query = Attendance::where('student_id', $student->id);

        if ($batch) {
            $query->where('batch_id', $batch->id);
        }

        $total = $query->count();

        if ($total === 0) {
            return 0.0;
        }

        $present = (clone $query)->attended()->count();

        return round(($present / $total) * 100, 2);
    }

    /**
     * Get students with attendance below threshold.
     */
    public function getFlaggedStudents(?float $threshold = null): Collection
    {
        $threshold = $threshold ?? $this->settingsService->get('attendance_threshold', 75);

        return Student::with(['batch'])
            ->get()
            ->filter(function ($student) use ($threshold) {
                $percentage = $this->calculateAttendancePercentage($student);

                return $percentage < $threshold && $percentage > 0;
            })
            ->map(function ($student) {
                $student->attendance_percentage = $this->calculateAttendancePercentage($student);

                return $student;
            })
            ->sortBy('attendance_percentage');
    }

    /**
     * Get attendance summary for a batch on a specific date.
     */
    public function getAttendanceSummary(Batch $batch, Carbon $date): array
    {
        $attendances = Attendance::where('batch_id', $batch->id)
            ->where('date', $date->toDateString())
            ->get();

        return [
            'total' => $batch->students()->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
            'not_marked' => $batch->students()->count() - $attendances->count(),
        ];
    }

    /**
     * Get attendance report for a date range.
     */
    public function getAttendanceReport(array $filters = []): Collection
    {
        $query = Attendance::with(['student.user', 'batch']);

        if (! empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        if (! empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('date', '<=', $filters['end_date']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Get paginated attendance records.
     */
    public function getPaginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Attendance::with(['student.user', 'batch']);

        if (! empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        if (! empty($filters['date'])) {
            $query->where('date', $filters['date']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('date', 'desc')->paginate($perPage);
    }

    /**
     * Check if attendance exists for a student on a date.
     */
    public function attendanceExists(int $studentId, int $batchId, Carbon $date): bool
    {
        return Attendance::where('student_id', $studentId)
            ->where('batch_id', $batchId)
            ->where('date', $date->toDateString())
            ->exists();
    }

    /**
     * Get attendance statistics for a batch.
     */
    public function getBatchStatistics(Batch $batch, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Attendance::where('batch_id', $batch->id);

        if ($startDate) {
            $query->where('date', '>=', $startDate->toDateString());
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate->toDateString());
        }

        $attendances = $query->get();
        $totalDays = $attendances->pluck('date')->unique()->count();
        $totalStudents = $batch->students()->count();

        return [
            'total_days' => $totalDays,
            'total_students' => $totalStudents,
            'total_records' => $attendances->count(),
            'present_count' => $attendances->where('status', 'present')->count(),
            'absent_count' => $attendances->where('status', 'absent')->count(),
            'late_count' => $attendances->where('status', 'late')->count(),
            'excused_count' => $attendances->where('status', 'excused')->count(),
            'average_attendance' => ($totalDays > 0 && $totalStudents > 0)
                ? round(($attendances->whereIn('status', ['present', 'late'])->count() / ($totalDays * $totalStudents)) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get students for attendance marking.
     */
    public function getStudentsForAttendance(Batch $batch, Carbon $date): Collection
    {
        $students = $batch->students()->with('user')->get();

        // Get existing attendance for this date
        $existingAttendance = Attendance::where('batch_id', $batch->id)
            ->where('date', $date->toDateString())
            ->pluck('status', 'student_id');

        return $students->map(function ($student) use ($existingAttendance) {
            $student->attendance_status = $existingAttendance->get($student->id);

            return $student;
        });
    }

    /**
     * Delete attendance record.
     */
    public function deleteAttendance(int $studentId, int $batchId, Carbon $date): bool
    {
        return Attendance::where('student_id', $studentId)
            ->where('batch_id', $batchId)
            ->where('date', $date->toDateString())
            ->delete() > 0;
    }

    /**
     * Bulk delete attendance for a date.
     */
    public function bulkDeleteForDate(Batch $batch, Carbon $date): int
    {
        return Attendance::where('batch_id', $batch->id)
            ->where('date', $date->toDateString())
            ->delete();
    }
}
