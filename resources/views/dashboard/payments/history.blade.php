@extends('layouts.admin')

@section('title', 'Payment History - ' . ($student->user->name ?? 'Student'))
@section('page-title', 'Payment History')
@section('page-description', 'Complete payment history for ' . ($student->user->name ?? 'Student'))

@section('content')
    <div class="max-w-6xl mx-auto">
        <!-- Student Summary Card -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        @if($student->profile_image)
                            <img class="w-16 h-16 rounded-full object-cover border-2 border-gray-200"
                                src="{{ media_url($student->profile_image) }}"
                                alt="{{ $student->user->name ?? 'Student' }}">
                        @else
                            <div class="w-16 h-16 bg-bd-green rounded-full flex items-center justify-center text-white text-xl font-bold">
                                {{ strtoupper(substr($student->user->name ?? 'S', 0, 1)) }}
                            </div>
                        @endif
                        <div class="ml-4">
                            <h2 class="text-xl font-bold text-gray-900">{{ $student->user->name ?? 'N/A' }}</h2>
                            <p class="text-sm text-gray-500">{{ $student->registration_no ?? 'No Registration' }}</p>
                            <p class="text-sm text-gray-500">{{ $student->batch->name ?? 'No Batch' }}</p>
                        </div>
                    </div>
                    <a href="{{ route('dashboard.payments.create') }}?student_id={{ $student->id }}"
                        class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Record Payment
                    </a>
                </div>
            </div>

            <!-- Payment Summary Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-gray-200">
                <div class="p-6 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Total Amount</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">৳{{ number_format($summary['total_amount'], 2) }}</p>
                </div>
                <div class="p-6 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Total Paid</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">৳{{ number_format($summary['total_paid'], 2) }}</p>
                </div>
                <div class="p-6 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Due Amount</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">৳{{ number_format($summary['total_due'], 2) }}</p>
                </div>
                <div class="p-6 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Total Payments</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ $summary['payment_count'] }}</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <form action="{{ route('dashboard.payments.history', $student) }}" method="GET" class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center space-x-2">
                        <x-ui.date-picker name="date_from" value="{{ request('date_from') }}" />
                        <span class="text-gray-500">to</span>
                        <x-ui.date-picker name="date_to" value="{{ request('date_to') }}" />
                    </div>
                    
                    <div class="w-48">
                        <x-ui.select name="payment_method">
                            <option value="">All Methods</option>
                            <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="bkash" {{ request('payment_method') === 'bkash' ? 'selected' : '' }}>bKash</option>
                            <option value="nagad" {{ request('payment_method') === 'nagad' ? 'selected' : '' }}>Nagad</option>
                            <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        </x-ui.select>
                    </div>

                    <div class="w-40">
                        <x-ui.select name="status">
                            <option value="">All Status</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                        </x-ui.select>
                    </div>

                    <button type="submit"
                        class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition-colors text-sm">
                        Filter
                    </button>
                    
                    @if(request()->hasAny(['date_from', 'date_to', 'payment_method', 'status']))
                        <a href="{{ route('dashboard.payments.history', $student) }}"
                            class="px-4 py-2 text-gray-600 hover:text-gray-800 text-sm">
                            Clear Filters
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Payment History Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Payment Records</h3>
            </div>
            
            @if($payments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($payments as $payment)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900">{{ $payment->receipt_number ?? 'N/A' }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : 'N/A' }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-bold text-gray-900">৳{{ number_format($payment->amount, 2) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($payment->status === 'completed') bg-green-100 text-green-800
                                            @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($payment->status === 'refunded') bg-gray-100 text-gray-800
                                            @else bg-red-100 text-red-800 @endif">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="{{ route('dashboard.payments.show', $payment) }}"
                                            class="text-blue-600 hover:text-blue-800 mr-3" title="View Details">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('dashboard.payments.receipt', $payment) }}" target="_blank"
                                            class="text-green-600 hover:text-green-800" title="Print Receipt">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-12 text-center">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-16 h-16 bg-orange-50 text-orange-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900">No payments found</h3>
                        <p class="mt-1 text-sm text-gray-500 max-w-sm">
                            @if(request()->hasAny(['date_from', 'date_to', 'payment_method', 'status']))
                                No payment records match your filter criteria. Try adjusting your filters or date range.
                            @else
                                This student has no payment records yet.
                            @endif
                        </p>
                        @if(request()->hasAny(['date_from', 'date_to', 'payment_method', 'status']))
                            <a href="{{ route('dashboard.payments.history', $student) }}"
                                class="mt-3 text-bd-green hover:text-bd-green-dark font-medium">
                                Clear all filters
                            </a>
                        @else
                            <a href="{{ route('dashboard.payments.create') }}?student_id={{ $student->id }}"
                                class="mt-4 inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Record First Payment
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Invoices Section -->
        @if($invoices->count() > 0)
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Invoices</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($invoices as $invoice)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900">{{ $invoice->invoice_number }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-bold text-gray-900">৳{{ number_format($invoice->amount, 2) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'N/A' }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($invoice->status === 'paid') bg-green-100 text-green-800
                                            @elseif($invoice->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($invoice->status === 'overdue' || $invoice->isOverdue()) bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst($invoice->isOverdue() && $invoice->status === 'pending' ? 'overdue' : $invoice->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Back Button -->
        <div class="flex justify-start mb-10">
            <a href="{{ route('dashboard.payments.index') }}"
                class="inline-flex items-center px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Payments
            </a>
        </div>
    </div>
@endsection
