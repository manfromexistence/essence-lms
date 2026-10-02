<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
            'payment_date' => 'required|date_format:Y-m-d',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $paymentDate = Carbon::parse($validated['payment_date'])->startOfDay();

        // teacher_salaries.month is NOT NULL with no default, and nothing ever
        // supplied it — so every salary insert failed with a NOT NULL violation
        // and salary recording was entirely broken. Deriving it here also makes
        // the existing unique(teacher_id, month) index live, which is what
        // actually prevents a double payment; the read-then-insert check alone
        // races.
        $validated['payment_date'] = $paymentDate;
        $validated['month'] = $paymentDate->format('Y-m');

        // Check for duplicate payment (same teacher, same month)
        $duplicate = TeacherSalary::where('teacher_id', $validated['teacher_id'])
            ->where('month', $validated['month'])
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'teacher_id' => 'Salary for this teacher has already been paid for this month.',
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
            'payment_date' => 'required|date_format:Y-m-d',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        // Keep `month` in step with a corrected payment_date, and scope the duplicate
        // check to that column so it matches store() and the unique index. A
        // read-then-insert on a date range alone could leave one teacher with two
        // rows inside a single payroll month, which report() then summed as
        // double pay. Excludes this record so re-saving it unchanged is fine.
        $validated['payment_date'] = Carbon::parse($validated['payment_date'])->startOfDay();
        $validated['month'] = $validated['payment_date']->format('Y-m');

        $duplicate = TeacherSalary::where('teacher_id', $validated['teacher_id'])
            ->where('id', '!=', $salary->id)
            ->where('month', $validated['month'])
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'amount' => 'This teacher already has a salary recorded for that month.',
            ])->withInput();
        }

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
