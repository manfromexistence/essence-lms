@extends('layouts.admin')

@section('title', 'Payment Review')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Payment Review</h1>
        <p class="text-gray-600 mt-2">Review and approve student course payment submissions</p>
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

    <!-- Pending Payments Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Pending Payments ({{ $pendingPayments->total() }})</h2>
        </div>

        @if($pendingPayments->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($pendingPayments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <p class="font-medium text-gray-900">{{ $payment->student->user->name ?? 'N/A' }}</p>
                                <p class="text-sm text-gray-500">{{ $payment->student->user->email ?? 'N/A' }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $payment->course->name ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-500">{{ $payment->course->code ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ strtoupper($payment->payment_method) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="font-semibold text-gray-900">৳{{ number_format($payment->amount, 2) }}</p>
                            <p class="text-xs text-gray-500">{{ $payment->transaction_id }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $payment->submitted_at->format('M d, Y') }}<br>
                            <span class="text-xs">{{ $payment->submitted_at->format('h:i A') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('payment.review.detail', $payment) }}" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition inline-block">
                                Review
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $pendingPayments->links() }}
        </div>
        @else
        <div class="px-6 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No pending payments</h3>
            <p class="mt-1 text-sm text-gray-500">All payments have been reviewed.</p>
        </div>
        @endif
    </div>
</div>
@endsection
