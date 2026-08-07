<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Get widgets for a specific role.
     */
    public function getWidgetsForRole(string $role): array
    {
        return match ($role) {
            'super-admin' => $this->getSuperAdminWidgets(),
            'teacher' => $this->getTeacherWidgets(),
            'student' => $this->getStudentWidgets(),
            'parent' => $this->getParentWidgets(),
            default => $this->getDefaultWidgets(),
        };
    }

    /**
     * Get statistics for a specific role.
     */
    public function getStatisticsForRole(string $role, ?int $userId = null): array
    {
        return match ($role) {
            'super-admin' => $this->getSuperAdminStatistics(),
            'teacher' => $this->getTeacherStatistics($userId),
            'student' => $this->getStudentStatistics($userId),
            'parent' => $this->getParentStatistics($userId),
            default => [],
        };
    }

    /**
     * Save user dashboard preferences.
     */
    public function saveUserPreferences(int $userId, array $preferences): bool
    {
        $user = User::find($userId);
        if (!$user) return false;
        
        $user->dashboard_preferences = $preferences;
        return $user->save();
    }

    /**
     * Get dashboard configuration for Super Admin.
     */
    public function getDashboardConfig(): array
    {
        return Cache::remember('dashboard_config', 3600, function () {
            return [
                'student_widgets' => [
                    'attendance_summary' => true,
                    'upcoming_exams' => true,
                    'recent_results' => true,
                    'payment_status' => true,
                    'class_schedule' => true,
                ],
                'teacher_widgets' => [
                    'my_batches' => true,
                    'today_schedule' => true,
                    'pending_results' => true,
                    'attendance_entry' => true,
                ],
                'visible_courses' => Course::where('status', 'active')->pluck('id')->toArray(),
            ];
        });
    }

    /**
     * Update dashboard configuration.
     */
    public function updateDashboardConfig(array $config): bool
    {
        Cache::put('dashboard_config', $config, 3600);
        return true;
    }

    protected function getSuperAdminWidgets(): array
    {
        return [
            'total_students',
            'total_teachers',
            'total_courses',
            'total_batches',
            'revenue_chart',
            'attendance_chart',
            'recent_admissions',
            'pending_payments',
            'recent_activities',
        ];
    }

    protected function getTeacherWidgets(): array
    {
        return [
            'my_batches',
            'today_schedule',
            'pending_results',
            'attendance_entry',
            'recent_activities',
        ];
    }

    protected function getStudentWidgets(): array
    {
        return [
            'attendance_summary',
            'upcoming_exams',
            'recent_results',
            'payment_status',
            'class_schedule',
            'course_materials',
        ];
    }

    protected function getParentWidgets(): array
    {
        return [
            'child_attendance',
            'child_results',
            'payment_history',
            'upcoming_exams',
        ];
    }

    protected function getDefaultWidgets(): array
    {
        return ['welcome_message'];
    }

    protected function getSuperAdminStatistics(): array
    {
        return Cache::remember('admin_dashboard_stats', 300, function () {
            $today = now()->toDateString();
            $thisMonth = now()->startOfMonth();
            
            return [
                'total_students' => Student::count(),
                'total_teachers' => Teacher::count(),
                'total_courses' => Course::count(),
                'total_batches' => Batch::count(),
                'active_students' => Student::where('status', 'active')->count(),
                'new_admissions_this_month' => Student::where('created_at', '>=', $thisMonth)->count(),
                'today_attendance_rate' => $this->calculateTodayAttendanceRate(),
                'monthly_revenue' => Payment::where('created_at', '>=', $thisMonth)->sum('amount'),
                'pending_payments' => Student::where('balance', '>', 0)->count(),
                'total_due' => Student::where('balance', '>', 0)->sum('balance'),
            ];
        });
    }

    protected function getTeacherStatistics(?int $userId): array
    {
        if (!$userId) return [];
        
        $teacher = Teacher::where('user_id', $userId)->first();
        if (!$teacher) return [];
        
        // Real counts from the course-business data
        $batchIds = $teacher->batches()->pluck('batches.id');
        $studentCount = \App\Models\Batch::whereIn('id', $batchIds)->withCount('students')->get()->sum('students_count');
        $todayDay = now()->dayOfWeek; // 0=Sunday ... 6=Saturday
        $todayClasses = \App\Models\ClassSchedule::where('teacher_id', $teacher->id)
            ->where('day_of_week', $todayDay)
            ->count();

        return [
            'my_batches' => $batchIds->count(),
            'total_students' => $studentCount,
            'today_classes' => $todayClasses,
            'pending_results' => \App\Models\ExamResult::where('grade', 'Pending')->count(),
        ];
    }

    protected function getStudentStatistics(?int $userId): array
    {
        if (!$userId) return [];
        
        $student = Student::where('user_id', $userId)->first();
        if (!$student) return [];
        
        $attendanceRate = Attendance::where('student_id', $student->id)
            ->where('status', 'present')
            ->count();
        $totalAttendance = Attendance::where('student_id', $student->id)->count();
        
        return [
            'attendance_rate' => $totalAttendance > 0 ? round(($attendanceRate / $totalAttendance) * 100, 1) : 0,
            'balance' => $student->balance ?? 0,
            'upcoming_exams' => \App\Models\Exam::where('start_time', '>=', now())
                ->where(function ($q) use ($student) {
                    $q->whereNull('batch_id')
                      ->orWhere('batch_id', $student->batch_id);
                })
                ->count(),
            'recent_results_count' => $student->results()->count(),
        ];
    }

    protected function getParentStatistics(?int $userId): array
    {
        // Parent statistics would be based on their children
        return [];
    }

    protected function calculateTodayAttendanceRate(): float
    {
        $today = now()->toDateString();
        $total = Attendance::where('date', $today)->count();
        $present = Attendance::where('date', $today)->where('status', 'present')->count();
        
        return $total > 0 ? round(($present / $total) * 100, 1) : 0;
    }

    /**
     * Get recent activities for dashboard.
     */
    public function getRecentActivities(int $limit = 10): Collection
    {
        $activities = collect();
        
        // Recent students
        Student::latest()->limit(3)->get()->each(function ($student) use ($activities) {
            $activities->push([
                'type' => 'student_admission',
                'message' => "New student admitted: {$student->name}",
                'time' => $student->created_at,
            ]);
        });
        
        // Recent payments
        Payment::latest()->limit(3)->get()->each(function ($payment) use ($activities) {
            $activities->push([
                'type' => 'payment',
                'message' => "Payment received: Tk.{$payment->amount}",
                'time' => $payment->created_at,
            ]);
        });
        
        return $activities->sortByDesc('time')->take($limit)->values();
    }

    /**
     * Get chart data for dashboard.
     */
    public function getChartData(string $type): array
    {
        return match ($type) {
            'revenue' => $this->getRevenueChartData(),
            'attendance' => $this->getAttendanceChartData(),
            'admissions' => $this->getAdmissionsChartData(),
            default => [],
        };
    }

    protected function getRevenueChartData(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();
            $data[] = [
                'month' => $month->format('M Y'),
                'amount' => Payment::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->sum('amount'),
            ];
        }
        return $data;
    }

    protected function getAttendanceChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $total = Attendance::where('date', $date)->count();
            $present = Attendance::where('date', $date)->where('status', 'present')->count();
            $data[] = [
                'date' => now()->subDays($i)->format('D'),
                'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            ];
        }
        return $data;
    }

    protected function getAdmissionsChartData(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();
            $data[] = [
                'month' => $month->format('M'),
                'count' => Student::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->count(),
            ];
        }
        return $data;
    }
}
