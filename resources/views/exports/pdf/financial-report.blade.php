<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title ?? 'Financial Report' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .page {
            padding: 20px 30px;
        }

        .header {
            border-bottom: 3px solid {{ $primaryColor ?? '#006A4E' }};
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .header h1 {
            font-size: 20px;
            color: {{ $primaryColor ?? '#006A4E' }};
            margin-bottom: 2px;
        }

        .header p {
            font-size: 10px;
            color: #666;
        }

        .period {
            text-align: right;
            font-size: 10px;
            color: #666;
            margin-bottom: 14px;
        }

        /* Summary tiles */
        .summary {
            display: table;
            width: 100%;
            margin-bottom: 18px;
            border-spacing: 6px 0;
        }

        .summary-cell {
            display: table-cell;
            width: 33.33%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background: #f8fafc;
        }

        .summary-cell .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
        }

        .summary-cell .value {
            font-size: 15px;
            font-weight: bold;
            margin-top: 3px;
        }

        .income {
            color: #15803d;
        }

        .expense {
            color: #b91c1c;
        }

        .profit {
            color: #15803d;
        }

        .loss {
            color: #b91c1c;
        }

        h2 {
            font-size: 12px;
            margin: 16px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            border: 1px solid #e2e8f0;
            padding: 5px 8px;
            text-align: left;
        }

        th {
            background: #f1f5f9;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #475569;
        }

        td.amount,
        th.amount {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        tfoot td {
            font-weight: bold;
            background: #f8fafc;
        }

        .empty {
            padding: 10px;
            text-align: center;
            color: #94a3b8;
            border: 1px dashed #e2e8f0;
            border-radius: 4px;
        }

        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    @php
        $incomeByCategory = collect($report['income_by_category'] ?? []);
        $expenseByCategory = collect($report['expense_by_category'] ?? []);
        $dailyData = $report['daily_data'] ?? [];

        // Only list days that actually moved money — a 31-row table of zeroes
        // is noise in a printed report.
        $activeDays = collect($dailyData)->filter(
            fn ($row) => ($row['income'] ?? 0) != 0 || ($row['expense'] ?? 0) != 0
        );

        $profitLoss = $report['profit_loss'] ?? 0;
        $institution = config('app.name', 'Dhaka IT Institute');
    @endphp

    <div class="page">
        <div class="header">
            <h1>{{ $institution }}</h1>
            <p>Financial Report</p>
        </div>

        <div class="period">
            Period:
            <strong>{{ \Illuminate\Support\Carbon::parse($report['start_date'])->format('M d, Y') }}</strong>
            &ndash;
            <strong>{{ \Illuminate\Support\Carbon::parse($report['end_date'])->format('M d, Y') }}</strong>
        </div>

        <div class="summary">
            <div class="summary-cell">
                <div class="label">Total Income</div>
                <div class="value income">৳{{ number_format((float) ($report['total_income'] ?? 0), 2) }}</div>
            </div>
            <div class="summary-cell">
                <div class="label">Total Expense</div>
                <div class="value expense">৳{{ number_format((float) ($report['total_expense'] ?? 0), 2) }}</div>
            </div>
            <div class="summary-cell">
                <div class="label">{{ $profitLoss >= 0 ? 'Net Profit' : 'Net Loss' }}</div>
                <div class="value {{ $profitLoss >= 0 ? 'profit' : 'loss' }}">
                    ৳{{ number_format(abs((float) $profitLoss), 2) }}
                </div>
            </div>
        </div>

        <h2>Income by Category</h2>
        @if ($incomeByCategory->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($incomeByCategory as $category => $amount)
                        <tr>
                            <td>{{ $category ?: 'Uncategorised' }}</td>
                            <td class="amount">৳{{ number_format((float) $amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="amount">৳{{ number_format((float) $incomeByCategory->sum(), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <div class="empty">No income recorded in this period.</div>
        @endif

        <h2>Expense by Category</h2>
        @if ($expenseByCategory->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($expenseByCategory as $category => $amount)
                        <tr>
                            <td>{{ $category ?: 'Uncategorised' }}</td>
                            <td class="amount">৳{{ number_format((float) $amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="amount">৳{{ number_format((float) $expenseByCategory->sum(), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <div class="empty">No expenses recorded in this period.</div>
        @endif

        <h2>Daily Breakdown</h2>
        @if ($activeDays->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="amount">Income</th>
                        <th class="amount">Expense</th>
                        <th class="amount">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activeDays as $date => $row)
                        @php
                            $dayIncome = (float) ($row['income'] ?? 0);
                            $dayExpense = (float) ($row['expense'] ?? 0);
                        @endphp
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($date)->format('M d, Y') }}</td>
                            <td class="amount">৳{{ number_format($dayIncome, 2) }}</td>
                            <td class="amount">৳{{ number_format($dayExpense, 2) }}</td>
                            <td class="amount {{ $dayIncome - $dayExpense >= 0 ? 'profit' : 'loss' }}">
                                ৳{{ number_format($dayIncome - $dayExpense, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="amount">৳{{ number_format((float) ($report['total_income'] ?? 0), 2) }}</td>
                        <td class="amount">৳{{ number_format((float) ($report['total_expense'] ?? 0), 2) }}</td>
                        <td class="amount">৳{{ number_format(abs((float) $profitLoss), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <div class="empty">No transactions recorded in this period.</div>
        @endif

        <div class="footer">
            Generated by {{ $institution }} on {{ now()->format('M d, Y g:i A') }}.
            This is a computer-generated report.
        </div>
    </div>
</body>

</html>
