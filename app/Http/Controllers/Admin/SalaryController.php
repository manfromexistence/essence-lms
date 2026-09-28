<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeacherSalary;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        $query = TeacherSalary::with('teacher.user')
            ->orderBy('payment_date', 'desc');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('teacher.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('notes', 'like', "%{$search}%")
              ->orWhere('payment_method', 'like', "%{$search}%");
        }

        if ($request->has('teacher_id') && $request->teacher_id) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('month') && $request->month) {
            $date = Carbon::parse($request->month);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            $query->whereBetween('payment_date', [$startOfMonth, $endOfMonth]);
        }

        $salaries = $query->paginate(15);
        $teachers = Teacher::with('user')->get();

        return view('dashboard.salaries.index', compact('salaries', 'teachers'));
    }

    public function create()
    {
        $teachers = Teacher::with('user')->get();
        return view('dashboard.salaries.create', compact('teachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        // Check for duplicate payment (same teacher, same month)
        $paymentMonth = Carbon::parse($validated['payment_date']);
        $startOfMonth = $paymentMonth->copy()->startOfMonth();
        $endOfMonth = $paymentMonth->copy()->endOfMonth();
        $duplicate = TeacherSalary::where('teacher_id', $validated['teacher_id'])
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'teacher_id' => 'Salary for this teacher has already been paid for this month.'
            ])->withInput();
        }

        TeacherSalary::create($validated);

        return redirect()->route('dashboard.salaries.index')
            ->with('success', 'Salary payment recorded successfully.');
    }

    /**
     * Show a single salary payment.
     *
     * Route::resource('salaries') registers GET salaries/{salary}, so this method
     * must exist or every such request dies with "Call to undefined method".
     */
    public function show(TeacherSalary $salary)
    {
        $salary->load('teacher.user');

        return view('dashboard.salaries.show', compact('salary'));
    }

    public function edit(TeacherSalary $salary)
    {
        $teachers = Teacher::with('user')->get();
        return view('dashboard.salaries.edit', compact('salary', 'teachers'));
    }

    public function update(Request $request, TeacherSalary $salary)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $salary->update($validated);

        return redirect()->route('dashboard.salaries.index')
            ->with('success', 'Salary payment updated successfully.');
    }

    public function destroy(TeacherSalary $salary)
    {
        $salary->delete();

        return redirect()->route('dashboard.salaries.index')
            ->with('success', 'Salary payment deleted successfully.');
    }

    public function history(Teacher $teacher)
    {
        $salaries = TeacherSalary::where('teacher_id', $teacher->id)
            ->orderBy('payment_date', 'desc')
            ->paginate(15);

        $totalPaid = TeacherSalary::where('teacher_id', $teacher->id)->sum('amount');

        return view('dashboard.salaries.history', compact('teacher', 'salaries', 'totalPaid'));
    }

    public function report(Request $request)
    {
        $year = $request->get('year', now()->year);
        
        $monthlySummary = [];
        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            $total = TeacherSalary::whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount');
            $monthlySummary[$month] = $total;
        }

        $startOfYear = Carbon::create($year, 1, 1)->startOfYear();
        $endOfYear = Carbon::create($year, 12, 31)->endOfYear();
        $teacherSummary = Teacher::withSum(['salaries' => function ($query) use ($startOfYear, $endOfYear) {
            $query->whereBetween('payment_date', [$startOfYear, $endOfYear]);
        }], 'amount')->get();

        return view('dashboard.salaries.report', compact('monthlySummary', 'teacherSummary', 'year'));
    }
}
