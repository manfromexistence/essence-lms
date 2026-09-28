@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold tracking-tight">Salary Report: {{ $year }}</h2>
        <form action="" method="GET" class="flex items-center">
            <div class="w-[120px]">
                <x-ui.select name="year" onchange="this.form.submit()" :selected="$year">
                    @for($y = date('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </x-ui.select>
            </div>
        </form>
    </div>

    <!-- Monthly Chart -->
    <x-ui.card>
        <x-ui.card-content class="pt-6">
            <div class="h-[300px] w-full">
                <canvas id="salaryChart"></canvas>
            </div>
        </x-ui.card-content>
    </x-ui.card>

    <!-- Yearly Summary Table -->
    <x-ui.card>
        <x-ui.card-header>
            <x-ui.card-title>Yearly Summary by Teacher</x-ui.card-title>
        </x-ui.card-header>
        <x-ui.card-content>
            <x-ui.table>
                <x-ui.table-header>
                    <x-ui.table-row>
                        <x-ui.table-head>Teacher Name</x-ui.table-head>
                        <x-ui.table-head>Total Paid ({{ $year }})</x-ui.table-head>
                        <x-ui.table-head>Monthly Average</x-ui.table-head>
                    </x-ui.table-row>
                </x-ui.table-header>
                <x-ui.table-body>
                    @foreach($teacherSummary as $teacher)
                    <x-ui.table-row>
                        <x-ui.table-cell class="font-medium">{{ $teacher->user->name }}</x-ui.table-cell>
                        <x-ui.table-cell class="font-bold">৳{{ number_format($teacher->salaries_sum_amount ?? 0, 2) }}</x-ui.table-cell>
                        <x-ui.table-cell>৳{{ number_format(($teacher->salaries_sum_amount ?? 0) / 12, 2) }}</x-ui.table-cell>
                    </x-ui.table-row>
                    @endforeach
                </x-ui.table-body>
            </x-ui.table>
        </x-ui.card-content>
    </x-ui.card>
</div>

@push('scripts')
<script>
    // Chart.js is provided by the bundled admin.js module. Module scripts are
    // deferred, so Chart is not defined yet while this inline script is parsed —
    // defer initialisation until the DOM is ready.
    document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('salaryChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Total Salary Paid',
                data: Object.values(@json($monthlySummary)),
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
    });
</script>
@endpush
@endsection
