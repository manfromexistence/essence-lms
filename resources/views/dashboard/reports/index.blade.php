@extends('layouts.admin')

@section('title', 'Reports')
@section('page-title', 'Reports')
@section('page-description', 'Generate, review and export institute reports')

@section('content')
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
                $cards = [
                    [
                        'route' => 'dashboard.reports.attendance',
                        'icon' => 'fa-user-check',
                        'tone' => 'text-emerald-600 bg-emerald-50',
                        'title' => 'Attendance Report',
                        'body' => 'Daily and per-batch attendance with present, absent and late breakdowns.',
                    ],
                    [
                        'route' => 'dashboard.reports.payment-summary',
                        'icon' => 'fa-money-bill-transfer',
                        'tone' => 'text-blue-600 bg-blue-50',
                        'title' => 'Payment Report',
                        'body' => 'Collected revenue, outstanding dues and payment-method breakdown.',
                    ],
                    [
                        'route' => 'dashboard.reports.performance',
                        'icon' => 'fa-chart-line',
                        'tone' => 'text-violet-600 bg-violet-50',
                        'title' => 'Performance Report',
                        'body' => 'Exam averages, pass rates and grade distribution per batch or course.',
                    ],
                    [
                        'route' => 'dashboard.reports.student',
                        'icon' => 'fa-users',
                        'tone' => 'text-amber-600 bg-amber-50',
                        'title' => 'Student Report',
                        'body' => 'Enrollment, payment and performance data for every student.',
                    ],
                    [
                        'route' => 'dashboard.reports.charts',
                        'icon' => 'fa-chart-pie',
                        'tone' => 'text-cyan-600 bg-cyan-50',
                        'title' => 'Charts',
                        'body' => 'Revenue, attendance and admission trends as interactive charts.',
                    ],
                    [
                        'route' => 'dashboard.reports.export',
                        'icon' => 'fa-file-export',
                        'tone' => 'text-rose-600 bg-rose-50',
                        'title' => 'Exports',
                        'body' => 'Queue Excel and PDF exports and download them when ready.',
                    ],
                ];
            @endphp

            @foreach ($cards as $card)
                <a href="{{ route($card['route']) }}"
                    class="block rounded-xl border border-border bg-card p-6 transition-shadow hover:shadow-md">
                    <div class="flex items-start gap-4">
                        <div class="h-11 w-11 rounded-lg {{ $card['tone'] }} flex items-center justify-center shrink-0">
                            <i class="fas {{ $card['icon'] }}"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold">{{ $card['title'] }}</h3>
                            <p class="text-sm text-muted-foreground mt-1">{{ $card['body'] }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endsection
