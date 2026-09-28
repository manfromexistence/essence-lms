<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Batch;
use App\Models\Teacher;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = ClassSchedule::with(['batch', 'teacher'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $batches = Batch::active()->get();

        return view('dashboard.schedules.index', compact('schedules', 'batches'));
    }

    public function create()
    {
        $batches = Batch::active()->get();
        $teachers = Teacher::all();
        return view('dashboard.schedules.create', compact('batches', 'teachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'subject' => 'nullable|string|max:255',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
        ]);

        // Check for conflicts
        $conflict = ClassSchedule::where('day_of_week', $validated['day_of_week'])
            ->where('room', $validated['room'])
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']]);
            })
            ->exists();

        if ($conflict && $validated['room']) {
            return back()->withErrors(['room' => 'Room conflict detected. Please choose a different room or time.'])->withInput();
        }

        ClassSchedule::create($validated);

        return redirect()->route('dashboard.schedules.index')
            ->with('success', 'Schedule created successfully.');
    }

    /**
     * Show a single class schedule.
     *
     * Route::resource registers GET schedules/{schedule}; without this method
     * every such request failed with "Call to undefined method".
     */
    public function show(ClassSchedule $schedule)
    {
        $schedule->load(['batch', 'teacher.user']);

        return view('dashboard.schedules.show', compact('schedule'));
    }

    public function edit(ClassSchedule $schedule)
    {
        $batches = Batch::active()->get();
        $teachers = Teacher::all();
        return view('dashboard.schedules.edit', compact('schedule', 'batches', 'teachers'));
    }

    public function update(Request $request, ClassSchedule $schedule)
    {
        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'subject' => 'nullable|string|max:255',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
        ]);

        // Check for conflicts (excluding current schedule)
        $conflict = ClassSchedule::where('id', '!=', $schedule->id)
            ->where('day_of_week', $validated['day_of_week'])
            ->where('room', $validated['room'])
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']]);
            })
            ->exists();

        if ($conflict && $validated['room']) {
            return back()->withErrors(['room' => 'Room conflict detected.'])->withInput();
        }

        $schedule->update($validated);

        return redirect()->route('dashboard.schedules.index')
            ->with('success', 'Schedule updated successfully.');
    }

    public function destroy(ClassSchedule $schedule)
    {
        $schedule->delete();

        return redirect()->route('dashboard.schedules.index')
            ->with('success', 'Schedule deleted successfully.');
    }

    public function checkConflict(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer',
            'start_time' => 'required',
            'end_time' => 'required',
            'room' => 'nullable|string',
            'exclude_id' => 'nullable|integer',
        ]);

        $query = ClassSchedule::where('day_of_week', $validated['day_of_week'])
            ->where('room', $validated['room'])
            ->where(function ($q) use ($validated) {
                $q->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                  ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']]);
            });

        if (!empty($validated['exclude_id'])) {
            $query->where('id', '!=', $validated['exclude_id']);
        }

        return response()->json(['conflict' => $query->exists()]);
    }
}
