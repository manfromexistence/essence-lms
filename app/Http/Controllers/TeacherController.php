<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Batch;
use App\Models\ClassSchedule;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TeacherController extends Controller
{
    /**
     * Get the authenticated teacher.
     */
    private function getTeacher(): ?Teacher
    {
        $user = Auth::user();
        return Teacher::where('user_id', $user->id)->first();
    }

    /**
     * Teacher dashboard.
     */
    public function dashboard()
    {
        $teacher = $this->getTeacher();
        
        if (!$teacher) {
            return redirect()->route('dashboard')->with('error', 'Teacher profile not found.');
        }

        // Get teacher's batches
        $batches = $teacher->batches()->with('course')->get();
        
        // Get today's schedule
        $todaySchedule = ClassSchedule::whereHas('batch.teachers', function($query) use ($teacher) {
            $query->where('teachers.id', $teacher->id);
        })
        ->where('day_of_week', Carbon::now()->dayOfWeek)
        ->with(['batch.course'])
        ->orderBy('start_time')
        ->get();

        // Get upcoming exams
        $upcomingExams = Exam::whereIn('batch_id', $batches->pluck('id'))
            ->where('start_time', '>=', Carbon::now())
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        // Get statistics
        $totalStudents = Student::whereIn('batch_id', $batches->pluck('id'))->count();
        $totalBatches = $batches->count();
        $totalExams = Exam::whereIn('batch_id', $batches->pluck('id'))->count();
        
        // Get recent attendance
        $recentAttendance = Attendance::whereIn('batch_id', $batches->pluck('id'))
            ->with(['student', 'batch'])
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        return view('teacher.dashboard', compact(
            'teacher',
            'batches',
            'todaySchedule',
            'upcomingExams',
            'totalStudents',
            'totalBatches',
            'totalExams',
            'recentAttendance'
        ));
    }

    /**
     * View teacher's batches.
     */
    public function batches()
    {
        $teacher = $this->getTeacher();
        
        if (!$teacher) {
            return redirect()->route('dashboard')->with('error', 'Teacher profile not found.');
        }

        $batches = $teacher->batches()
            ->with(['course', 'students'])
            ->get();

        return view('teacher.batches', compact('teacher', 'batches'));
    }

    /**
     * View students in a batch.
     */
    public function students(Batch $batch)
    {
        $teacher = $this->getTeacher();
        
        if (!$teacher || !$teacher->batches->contains($batch->id)) {
            abort(403, 'Unauthorized access to this batch.');
        }

        $students = $batch->students()
            ->with(['user'])
            ->get();

        return view('teacher.students', compact('teacher', 'batch', 'students'));
    }

    /**
     * View and manage attendance.
     */
    public function attendance(Request $request)
    {
        $teacher = $this->getTeacher();
        
        if (!$teacher) {
            return redirect()->route('dashboard')->with('error', 'Teacher profile not found.');
        }

        $batches = $teacher->batches()->with('course')->get();
        $selectedBatchId = $request->input('batch_id', $batches->first()?->id);
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        $attendance = [];
        if ($selectedBatchId) {
            abort_unless($teacher->batches()->whereKey($selectedBatchId)->exists(), 403);
            $batch = Batch::with('students')->findOrFail($selectedBatchId);
            $attendance = Attendance::where('batch_id', $selectedBatchId)
                ->where('date', $date)
                ->get()
                ->keyBy('student_id');
        }

        return view('teacher.attendance', compact(
            'teacher',
            'batches',
            'selectedBatchId',
            'date',
            'attendance'
        ));
    }

    /**
     * Save attendance.
     */
    public function saveAttendance(Request $request)
    {
        $teacher = $this->getTeacher();
        
        if (!$teacher) {
            return back()->with('error', 'Teacher profile not found.');
        }

        $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*' => 'required|in:present,absent,late,excused',
        ]);

        $batch = Batch::find($request->batch_id);
        
        if (!$teacher->batches->contains($batch->id)) {
            abort(403, 'Unauthorized access to this batch.');
        }

        foreach ($request->attendance as $studentId => $status) {
            if (!$batch->students()->whereKey($studentId)->exists()) {
                abort(422, 'Attendance contains a student outside the selected batch.');
            }
            Attendance::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'batch_id' => $request->batch_id,
                    'date' => $request->date,
                ],
                [
                    'status' => $status,
                    'marked_by' => Auth::id(),
                ]
            );
        }

        return back()->with('success', 'Attendance saved successfully!');
    }

    /**
     * View exams.
     */
    public function exams()
    {
        $teacher = $this->getTeacher();
        
        if (!$teacher) {
            return redirect()->route('dashboard')->with('error', 'Teacher profile not found.');
        }

        $batches = $teacher->batches()->with('course')->get();
        $exams = Exam::whereIn('batch_id', $batches->pluck('id'))
            ->with(['batch.course'])
            ->orderBy('start_time', 'desc')
            ->paginate(20);

        return view('teacher.exams', compact('teacher', 'exams'));
    }

    /**
     * View schedule.
     */
    public function schedule()
    {
        $teacher = $this->getTeacher();
        
        if (!$teacher) {
            return redirect()->route('dashboard')->with('error', 'Teacher profile not found.');
        }

        $schedules = ClassSchedule::whereHas('batch.teachers', function($query) use ($teacher) {
            $query->where('teachers.id', $teacher->id);
        })
        ->with(['batch.course'])
        ->orderBy('day_of_week')
        ->orderBy('start_time')
        ->get()
        ->groupBy('day_of_week');

        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return view('teacher.schedule', compact('teacher', 'schedules', 'days'));
    }
}
