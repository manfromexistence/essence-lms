<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AccountService
{
    // ============= EXPENSES =============

    public function createExpense(array $data): Expense
    {
        $data['created_by'] = Auth::id();

        return Expense::create($data);
    }

    public function updateExpense(Expense $expense, array $data): Expense
    {
        $expense->update($data);

        return $expense;
    }

    public function deleteExpense(Expense $expense): bool
    {
        return $expense->delete();
    }

    public function getExpenses(array $filters = []): LengthAwarePaginator
    {
        $query = Expense::with('creator')->orderBy('expense_date', 'desc');

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('expense_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('expense_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['search'])) {
            $query->where('description', 'like', '%'.$filters['search'].'%');
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    // ============= INCOME =============

    public function recordIncome(array $data): Income
    {
        $data['created_by'] = Auth::id();

        return Income::create($data);
    }

    public function recordPaymentIncome(Payment $payment): Income
    {
        return Income::create([
            'category' => 'tuition',
            'amount' => $payment->amount,
            'description' => 'Payment from student: '.($payment->student?->name ?? 'Unknown'),
            'income_date' => $payment->payment_date ?? now(),
            'student_id' => $payment->student_id,
            'payment_id' => $payment->id,
            'reference' => $payment->receipt_number,
            'created_by' => Auth::id(),
        ]);
    }

    public function getIncome(array $filters = []): LengthAwarePaginator
    {
        $query = Income::with(['student', 'payment', 'creator'])->orderBy('income_date', 'desc');

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('income_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('income_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    // ============= REPORTS =============

    public function getDailySummary(Carbon $date): array
    {
        $dateString = $date->toDateString();
        $income = Income::where('income_date', $dateString)->sum('amount');
        $expense = Expense::where('expense_date', $dateString)->sum('amount');

        return [
            'date' => $dateString,
            'total_income' => $income,
            'total_expense' => $expense,
            'net' => $income - $expense,
        ];
    }

    public function getMonthlySummary(int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $income = Income::whereBetween('income_date', [$startDate, $endDate])->get();
        $expense = Expense::whereBetween('expense_date', [$startDate, $endDate])->get();

        $incomeByCategory = $income->groupBy('category')->map(fn ($items) => $items->sum('amount'));
        $expenseByCategory = $expense->groupBy('category')->map(fn ($items) => $items->sum('amount'));

        return [
            'year' => $year,
            'month' => $month,
            'total_income' => $income->sum('amount'),
            'total_expense' => $expense->sum('amount'),
            'net' => $income->sum('amount') - $expense->sum('amount'),
            'income_by_category' => $incomeByCategory,
            'expense_by_category' => $expenseByCategory,
        ];
    }

    public function getFinancialReport(Carbon $startDate, Carbon $endDate): array
    {
        $income = Income::whereBetween('income_date', [$startDate, $endDate])->get();
        $expense = Expense::whereBetween('expense_date', [$startDate, $endDate])->get();

        $dailyData = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $dailyData[$dateStr] = [
                // Filtered on the formatted date rather than via
                // Collection::where(). income_date/expense_date are cast to
                // 'date', so each element holds a Carbon instance and
                // Collection::where() compares it loosely against a string --
                // an object never equals a string, so every day came back 0.00
                // and the PDF went on to print "No transactions recorded in
                // this period" directly beneath correct income/expense totals.
                'income' => $income->filter(fn ($i) => $i->income_date->format('Y-m-d') === $dateStr)->sum('amount'),
                'expense' => $expense->filter(fn ($e) => $e->expense_date->format('Y-m-d') === $dateStr)->sum('amount'),
            ];
            $current->addDay();
        }

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_income' => $income->sum('amount'),
            'total_expense' => $expense->sum('amount'),
            'profit_loss' => $income->sum('amount') - $expense->sum('amount'),
            'income_by_category' => $income->groupBy('category')->map(fn ($items) => $items->sum('amount')),
            'expense_by_category' => $expense->groupBy('category')->map(fn ($items) => $items->sum('amount')),
            'daily_data' => $dailyData,
        ];
    }

    public function getOverviewData(): array
    {
        $today = Carbon::today()->toDateString();
        $thisMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        $todayIncome = Income::where('income_date', $today)->sum('amount');
        $todayExpense = Expense::where('expense_date', $today)->sum('amount');

        $thisMonthStart = $thisMonth->copy()->startOfMonth();
        $thisMonthEnd = $thisMonth->copy()->endOfMonth();
        $thisMonthIncome = Income::whereBetween('income_date', [$thisMonthStart, $thisMonthEnd])->sum('amount');
        $thisMonthExpense = Expense::whereBetween('expense_date', [$thisMonthStart, $thisMonthEnd])->sum('amount');

        $lastMonthStart = $lastMonth->copy()->startOfMonth();
        $lastMonthEnd = $lastMonth->copy()->endOfMonth();
        $lastMonthIncome = Income::whereBetween('income_date', [$lastMonthStart, $lastMonthEnd])->sum('amount');
        $lastMonthExpense = Expense::whereBetween('expense_date', [$lastMonthStart, $lastMonthEnd])->sum('amount');

        return [
            'today' => ['income' => $todayIncome, 'expense' => $todayExpense, 'net' => $todayIncome - $todayExpense],
            'this_month' => ['income' => $thisMonthIncome, 'expense' => $thisMonthExpense, 'net' => $thisMonthIncome - $thisMonthExpense],
            'last_month' => ['income' => $lastMonthIncome, 'expense' => $lastMonthExpense, 'net' => $lastMonthIncome - $lastMonthExpense],
            'total_income' => Income::sum('amount'),
            'total_expense' => Expense::sum('amount'),
            'total_balance' => Income::sum('amount') - Expense::sum('amount'),
        ];
    }

    public function getChartData(int $months = 6): array
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $income = Income::whereBetween('income_date', [$startOfMonth, $endOfMonth])->sum('amount');
            $expense = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->sum('amount');

            $data[] = [
                'month' => $date->format('M Y'),
                'income' => $income,
                'expense' => $expense,
            ];
        }

        return $data;
    }
}
