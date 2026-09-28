@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold tracking-tight">Financial Reports</h2>
    </div>

    <!-- Report Generation Form -->
    <x-ui.card>
        <x-ui.card-content class="pt-6">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <form action="{{ route('dashboard.accounts.reports') }}" method="GET" class="md:col-span-10 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-2">
                        <x-ui.date-picker name="start_date" label="Start Date" value="{{ $startDate->format('Y-m-d') }}" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.date-picker name="end_date" label="End Date" value="{{ $endDate->format('Y-m-d') }}" />
                    </div>
                    <div class="flex items-end">
                        <x-ui.button type="submit" class="w-full">Generate Report</x-ui.button>
                    </div>
                </form>
                <div class="md:col-span-2">
                    <form id="pdfForm" action="{{ route('dashboard.accounts.export-pdf') }}" method="POST">
                        @csrf
                        <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                        <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                        <x-ui.button type="submit" variant="destructive" class="w-full">
                            <i class="fas fa-file-pdf mr-2"></i> Export PDF
                        </x-ui.button>
                    </form>
                </div>
            </div>
        </x-ui.card-content>
    </x-ui.card>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-ui.card class="border-blue-200 bg-blue-50">
            <x-ui.card-content class="pt-6 text-center">
                <h5 class="text-blue-700 font-medium mb-2">Total Income</h5>
                <h3 class="text-3xl font-bold text-blue-900">৳{{ number_format($report['total_income'], 2) }}</h3>
            </x-ui.card-content>
        </x-ui.card>
        
        <x-ui.card class="border-red-200 bg-red-50">
            <x-ui.card-content class="pt-6 text-center">
                <h5 class="text-red-700 font-medium mb-2">Total Expense</h5>
                <h3 class="text-3xl font-bold text-red-900">৳{{ number_format($report['total_expense'], 2) }}</h3>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card class="{{ $report['profit_loss'] >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-yellow-200 bg-yellow-50' }}">
            <x-ui.card-content class="pt-6 text-center">
                <h5 class="{{ $report['profit_loss'] >= 0 ? 'text-emerald-700' : 'text-yellow-700' }} font-medium mb-2">Net Profit/Loss</h5>
                <h3 class="{{ $report['profit_loss'] >= 0 ? 'text-emerald-900' : 'text-yellow-900' }} text-3xl font-bold">৳{{ number_format($report['profit_loss'], 2) }}</h3>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Income by Category</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div style="height: 300px; position: relative;">
                    <canvas id="incomeChart"></canvas>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Expense by Category</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div style="height: 300px; position: relative;">
                    <canvas id="expenseChart"></canvas>
                </div>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    <!-- Detailed Table -->
    <x-ui.card>
        <x-ui.card-header>
            <x-ui.card-title>Daily Breakdown</x-ui.card-title>
        </x-ui.card-header>
        <x-ui.card-content>
            <x-ui.table>
                <x-ui.table-header>
                    <x-ui.table-row>
                        <x-ui.table-head>Date</x-ui.table-head>
                        <x-ui.table-head>Income</x-ui.table-head>
                        <x-ui.table-head>Expense</x-ui.table-head>
                        <x-ui.table-head>Net</x-ui.table-head>
                    </x-ui.table-row>
                </x-ui.table-header>
                <x-ui.table-body>
                    @foreach($report['daily_data'] as $date => $data)
                    <x-ui.table-row>
                        <x-ui.table-cell>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</x-ui.table-cell>
                        <x-ui.table-cell class="text-blue-600">৳{{ number_format($data['income'], 2) }}</x-ui.table-cell>
                        <x-ui.table-cell class="text-red-600">৳{{ number_format($data['expense'], 2) }}</x-ui.table-cell>
                        <x-ui.table-cell class="font-bold {{ $data['income'] - $data['expense'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            ৳{{ number_format($data['income'] - $data['expense'], 2) }}
                        </x-ui.table-cell>
                    </x-ui.table-row>
                    @endforeach
                </x-ui.table-body>
            </x-ui.table>
        </x-ui.card-content>
    </x-ui.card>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Income Chart
    const incomeCtx = document.getElementById('incomeChart');
    if (incomeCtx) {
        new Chart(incomeCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: @json(array_keys($report['income_by_category']->toArray())),
                datasets: [{
                    data: @json(array_values($report['income_by_category']->toArray())),
                    backgroundColor: ['#3d59f9', '#10b981', '#06b6d4', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ৳' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Expense Chart
    const expenseCtx = document.getElementById('expenseChart');
    if (expenseCtx) {
        new Chart(expenseCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: @json(array_keys($report['expense_by_category']->toArray())),
                datasets: [{
                    data: @json(array_values($report['expense_by_category']->toArray())),
                    backgroundColor: ['#ef4444', '#f59e0b', '#06b6d4', '#10b981', '#3d59f9', '#8b5cf6', '#ec4899'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ৳' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
