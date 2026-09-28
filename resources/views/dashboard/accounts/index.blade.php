@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold tracking-tight">Accounts Overview</h2>
    </div>
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-ui.card class="bg-primary text-primary-foreground">
            <x-ui.card-header class="pb-2">
                <x-ui.card-title class="text-sm font-medium">Today's Income</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="text-2xl font-bold">৳{{ number_format($overview['today']['income'], 2) }}</div>
            </x-ui.card-content>
        </x-ui.card>
        <x-ui.card class="bg-destructive text-destructive-foreground">
            <x-ui.card-header class="pb-2">
                <x-ui.card-title class="text-sm font-medium">Today's Expense</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="text-2xl font-bold">৳{{ number_format($overview['today']['expense'], 2) }}</div>
            </x-ui.card-content>
        </x-ui.card>
        <x-ui.card class="bg-emerald-600 text-white">
            <x-ui.card-header class="pb-2">
                <x-ui.card-title class="text-sm font-medium">Monthly Net</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="text-2xl font-bold">৳{{ number_format($overview['this_month']['net'], 2) }}</div>
            </x-ui.card-content>
        </x-ui.card>
        <x-ui.card class="bg-sky-500 text-white">
            <x-ui.card-header class="pb-2">
                <x-ui.card-title class="text-sm font-medium">Total Balance</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="text-2xl font-bold">৳{{ number_format($overview['total_balance'], 2) }}</div>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Monthly Chart -->
        <div class="md:col-span-2">
            <x-ui.card>
                <x-ui.card-header>
                    <x-ui.card-title>Financial Overview (Last 6 Months)</x-ui.card-title>
                </x-ui.card-header>
                <x-ui.card-content>
                    <div style="height: 400px; position: relative;">
                        <canvas id="financialChart"></canvas>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <!-- This Month Summary -->
        <div>
            <x-ui.card>
                <x-ui.card-header>
                    <x-ui.card-title>This Month</x-ui.card-title>
                </x-ui.card-header>
                <x-ui.card-content class="space-y-4">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between border-b pb-2">
                            <span class="text-sm font-medium">Total Income</span>
                            <x-ui.badge variant="default">৳{{ number_format($overview['this_month']['income'], 2) }}</x-ui.badge>
                        </div>
                        <div class="flex items-center justify-between border-b pb-2">
                            <span class="text-sm font-medium">Total Expense</span>
                            <x-ui.badge variant="destructive">৳{{ number_format($overview['this_month']['expense'], 2) }}</x-ui.badge>
                        </div>
                        <div class="flex items-center justify-between border-b pb-2">
                            <span class="text-sm font-medium">Net Profit/Loss</span>
                            <x-ui.badge class="{{ $overview['this_month']['net'] >= 0 ? 'bg-emerald-600' : 'bg-yellow-500' }}">
                                ৳{{ number_format($overview['this_month']['net'], 2) }}
                            </x-ui.badge>
                        </div>
                    </div>
                    
                    <div class="pt-4 space-y-2">
                        <x-ui.button class="w-full" variant="outline" as="a" href="{{ route('dashboard.accounts.income') }}">Manage Income</x-ui.button>
                        <x-ui.button class="w-full text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200" variant="outline" as="a" href="{{ route('dashboard.accounts.expenses') }}">Manage Expenses</x-ui.button>
                        <x-ui.button class="w-full" variant="secondary" as="a" href="{{ route('dashboard.accounts.reports') }}">View Reports</x-ui.button>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('financialChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json(array_column($chartData, 'month')),
                datasets: [{
                    label: 'Income',
                    data: @json(array_column($chartData, 'income')),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    borderRadius: 6
                }, {
                    label: 'Expense',
                    data: @json(array_column($chartData, 'expense')),
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    borderColor: 'rgba(239, 68, 68, 1)',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '৳' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
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
                                return context.dataset.label + ': ৳' + context.parsed.y.toLocaleString();
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
