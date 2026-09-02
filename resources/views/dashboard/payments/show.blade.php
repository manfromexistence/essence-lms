@extends('layouts.admin')

@section('title', 'Payment Details')
@section('page-title', 'Payment Details')
@section('page-description', 'View payment information and receipt')

@section('content')
    <div class="max-w-4xl mx-auto">
        <!-- Success Message -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        <!-- Payment Status Card -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Payment #{{ $payment->receipt_number ?? 'N/A' }}</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Recorded on {{ $payment->created_at->format('F d, Y \a\t h:i A') }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @if($payment->status === 'completed') bg-green-100 text-green-800
                        @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($payment->status === 'refunded') bg-gray-100 text-gray-800
                        @else bg-red-100 text-red-800 @endif">
                        {{ ucfirst($payment->status) }}
                    </span>
                </div>
            </div>

            <!-- Payment Amount -->
            <div class="p-8 bg-gradient-to-r from-bd-green to-green-600 text-white text-center">
                <p class="text-sm uppercase tracking-wider opacity-80">Payment Amount</p>
                <p class="text-4xl font-bold mt-2">৳{{ number_format($payment->amount, 2) }}</p>
                <p class="text-sm mt-2 opacity-80">
                    via {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                </p>
            </div>

            <!-- Payment Details Grid -->
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Receipt Number</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $payment->receipt_number ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Payment Date</p>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $payment->payment_date ? $payment->payment_date->format('F d, Y') : 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Payment Method</p>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                        </p>
                    </div>
                    @if($payment->transaction_id)
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Transaction ID</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $payment->transaction_id }}</p>
                    </div>
                    @endif
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    @if($payment->invoice)
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Invoice Reference</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $payment->invoice->invoice_number }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Student Balance After Payment</p>
                        <p class="text-sm font-semibold {{ $studentBalance > 0 ? 'text-red-600' : 'text-green-600' }}">
                            ৳{{ number_format($studentBalance, 2) }}
                            @if($studentBalance > 0)
                                <span class="text-xs text-gray-500">(Due)</span>
                            @elseif($studentBalance < 0)
                                <span class="text-xs text-gray-500">(Advance)</span>
                            @else
                                <span class="text-xs text-gray-500">(Paid in Full)</span>
                            @endif
                        </p>
                    </div>
                    @if($payment->notes)
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Notes</p>
                        <p class="text-sm text-gray-700">{{ $payment->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Student Information -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Student Information</h3>
            </div>
            <div class="p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        @if($payment->student->profile_image)
                            <img class="w-16 h-16 rounded-full object-cover border-2 border-gray-200"
                                src="{{ media_url($payment->student->profile_image) }}"
                                alt="{{ $payment->student->user->name ?? 'Student' }}">
                        @else
                            <div class="w-16 h-16 bg-bd-green rounded-full flex items-center justify-center text-white text-xl font-bold">
                                {{ strtoupper(substr($payment->student->user->name ?? 'S', 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="ml-4 flex-1">
                        <h4 class="text-lg font-semibold text-gray-900">
                            {{ $payment->student->user->name ?? 'N/A' }}
                        </h4>
                        <p class="text-sm text-gray-500">{{ $payment->student->registration_no ?? 'No Registration' }}</p>
                        
                        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">Batch</p>
                                <p class="text-sm font-medium text-gray-900">{{ $payment->student->batch->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Total Amount</p>
                                <p class="text-sm font-medium text-gray-900">৳{{ number_format($payment->student->total_amount, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Paid Amount</p>
                                <p class="text-sm font-medium text-green-600">৳{{ number_format($payment->student->paid_amount, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Due Amount</p>
                                <p class="text-sm font-medium text-red-600">৳{{ number_format($payment->student->due_amount, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap justify-between items-center gap-4 mb-10">
            <a href="{{ route('dashboard.payments.index') }}"
                class="inline-flex items-center px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Payments
            </a>
            
            <div class="flex items-center space-x-3">
                <a href="{{ route('dashboard.payments.history', $payment->student) }}"
                    class="inline-flex items-center px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Payment History
                </a>
                
                <a href="{{ route('dashboard.payments.receipt', $payment) }}" target="_blank"
                    class="inline-flex items-center px-4 py-2 text-white bg-bd-green rounded-lg hover:bg-bd-green-dark transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print Receipt
                </a>
            </div>
        </div>
    </div>
@endsection
