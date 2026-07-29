@extends('layouts.admin')

@section('title', 'Payment Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Payment Dashboard</h1>
        <p class="text-gray-600 mt-2">Track your course payments and enrollment status</p>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
        {{ session('error') }}
    </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Courses</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalCourses }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Fees</p>
                    <p class="text-3xl font-bold text-gray-800">৳{{ number_format($totalFees, 2) }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Paid</p>
                    <p class="text-3xl font-bold text-green-600">৳{{ number_format($totalPaid, 2) }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pending</p>
                    <p class="text-3xl font-bold text-yellow-600">৳{{ number_format($totalPending, 2) }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Course-wise Breakdown -->
    @if(count($enrollments) > 0)
    <div class="bg-white rounded-lg shadow-md mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Course-wise Payment Breakdown</h2>
        </div>
        <div class="p-6">
            <div class="space-y-6">
                @foreach($enrollments as $enrollment)
                <div class="border-2 border-gray-200 rounded-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ $enrollment['course']->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $enrollment['course']->code ?? '' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Course Fee</p>
                            <p class="text-2xl font-bold text-gray-800">৳{{ number_format($enrollment['total_fee'], 2) }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="bg-green-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600">Amount Deposited</p>
                            <p class="text-xl font-bold text-green-600">৳{{ number_format($enrollment['amount_deposited'], 2) }}</p>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600">Pending Review</p>
                            <p class="text-xl font-bold text-yellow-600">৳{{ number_format($enrollment['pending_amount'], 2) }}</p>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600">Remaining</p>
                            <p class="text-xl font-bold text-blue-600">৳{{ number_format($enrollment['total_fee'] - $enrollment['amount_deposited'] - $enrollment['pending_amount'], 2) }}</p>
                        </div>
                    </div>

                    <!-- Payment History for this Course -->
                    @if($enrollment['payments']->count() > 0)
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">Payment History:</p>
                        <div class="space-y-2">
                            @foreach($enrollment['payments'] as $payment)
                            <div class="flex justify-between items-center bg-gray-50 rounded p-3">
                                <div class="flex items-center space-x-3">
                                    @if($payment->status === 'approved')
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                                    @elseif($payment->status === 'pending')
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                    @elseif($payment->status === 'rejected')
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>
                                    @endif
                                    <span class="text-sm text-gray-600">{{ $payment->submitted_at->format('M d, Y') }}</span>
                                    <span class="text-sm text-gray-600">{{ strtoupper($payment->payment_method) }}</span>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-800">৳{{ number_format($payment->amount, 2) }}</p>
                                    @if($payment->status === 'rejected' && $payment->admin_notes)
                                        <p class="text-xs text-red-600 mt-1">{{ $payment->admin_notes }}</p>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Complete Payment History -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Complete Payment History</h2>
        </div>

        @if($payments->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Screenshot</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $payment->submitted_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $payment->course->name ?? 'N/A' }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ strtoupper($payment->payment_method) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $payment->transaction_id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="font-semibold text-gray-900">৳{{ number_format($payment->amount, 2) }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($payment->status === 'approved')
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                            @elseif($payment->status === 'pending')
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                            @elseif($payment->status === 'rejected')
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>
                            @endif
                            @if($payment->status === 'rejected' && $payment->admin_notes)
                                <p class="text-xs text-red-600 mt-1">{{ $payment->admin_notes }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($payment->screenshot_path)
                                <a href="{{ route('student.payment.proof', $payment) }}"
                                    target="_blank" 
                                    class="text-blue-600 hover:text-blue-800">
                                    View
                                </a>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-6 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No payment history</h3>
            <p class="mt-1 text-sm text-gray-500">You haven't made any course payments yet.</p>
            <div class="mt-6">
                <a href="{{ route('student.courses') }}" 
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Browse Courses
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
