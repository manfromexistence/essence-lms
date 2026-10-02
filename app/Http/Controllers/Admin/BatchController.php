<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Batch::with(['course', 'students', 'teachers']);

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where(function ($subQ) use ($search) {
                $subQ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('course', function ($courseQ) use ($search) {
                        $courseQ->where('name', 'like', "%{$search}%");
                    });
            });
        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        $query->when($request->filled('course_id'), function ($q) use ($request) {
            $q->where('course_id', $request->course_id);
        });

        $batches = $query->paginate(15);
        $courses = Course::active()->get();

        return view('dashboard.batches.index', compact('batches', 'courses'));
    }

    public function create()
    {
        $courses = Course::active()->get();
        $teachers = Teacher::with('user')->get();

        return view('dashboard.batches.create', compact('courses', 'teachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:batches,code',
            'course_id' => 'required|exists:courses,id',
            'schedule' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'max_students' => 'nullable|integer|min:1',
            'status' => 'required|in:active,inactive,completed',
            'room' => 'nullable|string|max:100',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        Batch::create($validated);

        return redirect()->route('dashboard.batches.index')
            ->with('success', 'Batch created successfully.');
    }

    public function show(Batch $batch)
    {
        $batch->load(['course', 'students.user', 'teachers.user', 'attendances']);

        return view('dashboard.batches.show', compact('batch'));
    }

    public function edit(Batch $batch)
    {
        $courses = Course::active()->get();
        $teachers = Teacher::with('user')->get();

        return view('dashboard.batches.edit', compact('batch', 'courses', 'teachers'));
    }

    public function update(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:batches,code,'.$batch->id,
            'course_id' => 'required|exists:courses,id',
            'schedule' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'max_students' => 'nullable|integer|min:1',
            'status' => 'required|in:active,inactive,completed',
            'room' => 'nullable|string|max:100',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        $batch->update($validated);

        return redirect()->route('dashboard.batches.index')
            ->with('success', 'Batch updated successfully.');
    }

    public function destroy(Batch $batch)
    {
        // Refuse while anything still points at this batch.
        //
        // The FKs are set to cascade or null-on-delete, so an unguarded delete was
        // silently destructive: attendances (the whole history) and class_schedules
        // (the timetable) were destroyed, students kept an approved status with a
        // null batch_id, and exams had batch_id nulled — which made them accessible
        // to every student in the institute. BatchService::delete() already had this
        // guard but was never called from here.
        $studentCount = $batch->students()->count();

        if ($studentCount > 0) {
            return back()->withErrors([
                'batch' => "This batch still has {$studentCount} student(s). Move or remove them before deleting it.",
            ]);
        }

        if ($batch->exams()->exists()) {
            return back()->withErrors([
                'batch' => 'This batch still has exams assigned. Delete or reassign them first.',
            ]);
        }

        if ($batch->schedules()->exists()) {
            return back()->withErrors([
                'batch' => 'This batch still has a class schedule. Remove it before deleting the batch.',
            ]);
        }

        $batch->delete();

        return redirect()->route('dashboard.batches.index')
            ->with('success', 'Batch deleted successfully.');
    }
}
