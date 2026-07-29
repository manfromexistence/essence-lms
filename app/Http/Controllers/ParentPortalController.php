<?php

namespace App\Http\Controllers;

use App\Models\ParentModel;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Payment;
use App\Models\ExamResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentPortalController extends Controller
{
    /**
     * Get the authenticated parent or allow admin/super-admin to view all.
     */
    private function getParent(): ?ParentModel
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        
        // Allow admin and super-admin to view first parent (or you can show all)
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            // Return first parent for demo purposes, or you could show a selector
            return ParentModel::with('students')->first();
        }
        
        // Find parent by email
        return ParentModel::where('email', $user->email)->with('students')->first();
    }

    public function index()
    {
        $parent = $this->getParent();
        
        if (!$parent) {
            return view('parent.dashboard')->with('error', 'No parent profile found.');
        }

        $children = $parent->students()->with(['batch.course', 'user'])->get();
        
        // Get summary data for each child
        $childrenData = $children->map(function ($student) {
            return [
                'student' => $student,
                'attendance_rate' => $this->getAttendanceRate($student),
                'latest_result' => $this->getLatestResult($student),
                'pending_fees' => $this->getPendingFees($student),
            ];
        });

        return view('parent.dashboard', [
            'parent' => $parent,
            'children' => $childrenData,
        ]);
    }

    public function progress()
    {
        $parent = $this->getParent();
        
        if (!$parent) {
            return view('parent.progress')->with('error', 'No parent profile found.');
        }

        $children = $parent->students()->with(['batch.course'])->get();
        
        $progressData = $children->map(function ($student) {
            $results = ExamResult::where('student_id', $student->id)
                ->with('exam')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
            
            return [
                'student' => $student,
                'results' => $results,
                'average_percentage' => $results->avg('percentage') ?? 0,
                'total_exams' => $results->count(),
            ];
        });

        return view('parent.progress', [
            'parent' => $parent,
            'progressData' => $progressData,
        ]);
    }

    public function attendance()
    {
        $parent = $this->getParent();
        
        if (!$parent) {
            return view('parent.attendance')->with('error', 'No parent profile found.');
        }

        $children = $parent->students()->with(['batch.course'])->get();
        
        $attendanceData = $children->map(function ($student) {
            $totalDays = Attendance::where('student_id', $student->id)->count();
            $presentDays = Attendance::where('student_id', $student->id)
                ->where('status', 'present')
                ->count();
            $absentDays = Attendance::where('student_id', $student->id)
                ->where('status', 'absent')
                ->count();
            $lateDays = Attendance::where('student_id', $student->id)
                ->where('status', 'late')
                ->count();
            
            $recentAttendance = Attendance::where('student_id', $student->id)
                ->orderBy('date', 'desc')
                ->take(30)
                ->get();
            
            return [
                'student' => $student,
                'total_days' => $totalDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'late_days' => $lateDays,
                'attendance_rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0,
                'recent_attendance' => $recentAttendance,
            ];
        });

        return view('parent.attendance', [
            'parent' => $parent,
            'attendanceData' => $attendanceData,
        ]);
    }

    public function fees()
    {
        $parent = $this->getParent();
        
        if (!$parent) {
            return view('parent.fees')->with('error', 'No parent profile found.');
        }

        $children = $parent->students()->with(['batch.course'])->get();
        
        $feesData = $children->map(function ($student) {
            $payments = Payment::where('student_id', $student->id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            $totalPaid = $payments->whereIn('status', Payment::settledStatuses())->sum('amount');
            $totalFee = $student->batch?->course?->price ?? 0;
            $pendingAmount = max(0, $totalFee - $totalPaid);
            
            return [
                'student' => $student,
                'payments' => $payments,
                'total_fee' => $totalFee,
                'total_paid' => $totalPaid,
                'pending_amount' => $pendingAmount,
                'payment_percentage' => $totalFee > 0 ? round(($totalPaid / $totalFee) * 100, 2) : 0,
            ];
        });

        return view('parent.fees', [
            'parent' => $parent,
            'feesData' => $feesData,
        ]);
    }

    /**
     * Helper methods
     */
    private function getAttendanceRate(Student $student): float
    {
        $totalDays = Attendance::where('student_id', $student->id)->count();
        if ($totalDays === 0) return 0;
        
        $presentDays = Attendance::where('student_id', $student->id)
            ->where('status', 'present')
            ->count();
        
        return round(($presentDays / $totalDays) * 100, 2);
    }

    private function getLatestResult(Student $student): ?ExamResult
    {
        return ExamResult::where('student_id', $student->id)
            ->with('exam')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    private function getPendingFees(Student $student): float
    {
        $totalPaid = Payment::where('student_id', $student->id)
            ->whereIn('status', Payment::settledStatuses())
            ->sum('amount');
        
        $totalFee = $student->batch?->course?->price ?? 0;
        
        return max(0, $totalFee - $totalPaid);
    }
}
