@extends('layouts.admin')

@section('title', 'Payment Reports')
@section('page-title', 'পেমেন্ট রিপোর্ট')
@section('page-description', 'Analyze payment data with filtering and export options')

@section('content')
<div class="space-y-6">
    <!-- Summary Statistics - Requirement 6.5: Show total revenue, payment method breakdown, and outstanding dues -->
    @if($report && isset($report['summary']))
    @php
        // Keys must match generatePaymentReport()'s summary, which returns
        // total_amount / total_count / average_amount. The view previously asked
        // for total_revenue / total_transactions / average_payment, so all three
        // `?? 0` fallbacks fired and the page showed three permanent zeros above a
        // table of real rows. outstanding_dues is not part of this summary at
        // all, so it is replaced with pending amount, which the row data supports.
        $summary = $report['summary'] ?? [];
        $pendingAmount = collect($report['data'] ?? [])
            ->where('status', 'pending')
            ->sum('amount');
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
            <p class="text-sm font-medium text-gray-600">Total Revenue</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">৳{{ number_format($summary['total_amount'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-sm font-medium text-gray-600">Total Transactions</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($summary['total_count'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-amber-500">
            <p class="text-sm font-medium text-gray-600">Pending Amount</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">৳{{ number_format($pendingAmount, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
            <p class="text-sm font-medium text-gray-600">Average Payment</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">৳{{ number_format($summary['average_amount'] ?? 0, 2) }}</p>
        </div>
    </div>

    <!-- Payment Method Breakdown -->
    @if(!empty($summary['by_method']))
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Method Breakdown</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($summary['by_method'] as $method => $methodTotals)
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $method)) }}</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">৳{{ number_format($methodTotals['amount'] ?? 0, 2) }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $methodTotals['count'] ?? 0 }} payment(s)</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif
    @endif

    <!-- Filters Card - Requirement 6.2: Support filtering by date range, batch, and payment method -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Filter Payment Report</h3>
        </div>
        <div class="px-6 py-4">
            <form method="GET" action="{{ route('dashboard.reports.payment-summary') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Batch Filter -->
                    <div class="space-y-2">
                        <x-ui.select name="batch_id" id="batch_id" label="Batch">
                            <option value="">All Batches</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" {{ ($filters['batch_id'] ?? '') == $batch->id ? 'selected' : '' }}>
                                    {{ $batch->name }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <!-- Payment Method Filter -->
                    <div class="space-y-2">
                        <x-ui.select name="payment_method" id="payment_method" label="Payment Method">
                            <option value="">All Methods</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method }}" {{ ($filters['payment_method'] ?? '') === $method ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $method)) }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <!-- Status Filter -->
                    <div class="space-y-2">
                        <x-ui.select name="status" id="status" label="Status">
                            <option value="">All Status</option>
                            <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="refunded" {{ ($filters['status'] ?? '') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                        </x-ui.select>
                    </div>

                    <div class="space-y-2">
                        <x-ui.date-picker name="date_from" id="date_from" label="From Date" value="{{ $filters['start_date'] ?? '' }}" />
                    </div>

                    <div class="space-y-2">
                        <x-ui.date-picker name="date_to" id="date_to" label="To Date" value="{{ $filters['end_date'] ?? '' }}" />
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Generate Report
                    </button>

                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('dashboard.reports.payment-summary') }}" class="text-gray-600 hover:text-gray-800 text-sm">
                            Clear Filters
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Report Data Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100">
        <!-- Header with Export Buttons - Requirements 6.3, 6.4: Excel and PDF exports -->
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Payment Report</h3>
                @if($report)
                    <p class="text-sm text-gray-500">{{ count($report['data'] ?? []) }} records found</p>
                @endif
            </div>
            
            @if($report && !empty($report['data']))
            <div class="flex items-center gap-3">
                <!-- Excel Export Button - Requirement 6.3 -->
                <form action="{{ route('dashboard.reports.export-excel') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="report_type" value="payment">
                    <input type="hidden" name="batch_id" value="{{ $filters['batch_id'] ?? '' }}">
                    <input type="hidden" name="date_from" value="{{ $filters['start_date'] ?? '' }}">
                    <input type="hidden" name="date_to" value="{{ $filters['end_date'] ?? '' }}">
                    <input type="hidden" name="payment_method" value="{{ $filters['payment_method'] ?? '' }}">
                    <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium text-sm shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export Excel
                    </button>
                </form>

                <!-- PDF Export Button - Requirement 6.4 -->
                <form action="{{ route('dashboard.reports.export-pdf') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="report_type" value="payment">
                    <input type="hidden" name="batch_id" value="{{ $filters['batch_id'] ?? '' }}">
                    <input type="hidden" name="date_from" value="{{ $filters['start_date'] ?? '' }}">
                    <input type="hidden" name="date_to" value="{{ $filters['end_date'] ?? '' }}">
                    <input type="hidden" name="payment_method" value="{{ $filters['payment_method'] ?? '' }}">
                    <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium text-sm shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Export PDF
                    </button>
                </form>
            </div>
            @endif
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            @if($report && !empty($report['data']))
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($report['data'] as $record)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $record['receipt_number'] ?? 'N/A' }}</div>
                                    @if(isset($record['transaction_id']))
                                        <div class="text-xs text-gray-500">TXN: {{ $record['transaction_id'] }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold shadow-sm">
                                            {{ strtoupper(substr($record['student_name'] ?? 'S', 0, 1)) }}
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $record['student_name'] ?? 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">{{ $record['registration_no'] ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $record['batch_name'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">৳{{ number_format($record['amount'] ?? 0, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $methodClass = match($record['payment_method'] ?? 'cash') {
                                            'cash' => 'bg-green-100 text-green-800',
                                            'bkash' => 'bg-pink-100 text-pink-800',
                                            'nagad' => 'bg-orange-100 text-orange-800',
                                            'bank_transfer' => 'bg-blue-100 text-blue-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $methodClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $record['payment_method'] ?? 'Cash')) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusClass = match($record['status'] ?? 'completed') {
                                            'completed' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'failed' => 'bg-red-100 text-red-800',
                                            'refunded' => 'bg-gray-100 text-gray-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ ucfirst($record['status'] ?? 'Completed') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ isset($record['payment_date']) ? \Carbon\Carbon::parse($record['payment_date'])->format('M d, Y') : 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <!-- Empty State -->
                <div class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-16 h-16 bg-orange-50 text-orange-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900">No payment data found</h3>
                        <p class="mt-1 text-sm text-gray-500 max-w-sm">
                            @if(!empty(array_filter($filters)))
                                No payment records match your filter criteria. Try adjusting your filters.
                            @else
                                Payment records will appear here once payments are recorded.
                            @endif
                        </p>
                        @if(!empty(array_filter($filters)))
                            <a href="{{ route('dashboard.reports.payment-summary') }}"
                                class="mt-3 text-bd-green hover:text-bd-green-dark font-medium">
                                Clear all filters
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
