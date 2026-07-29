@extends('layouts.admin')

@section('title', 'Payment Detail')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('payment.review.list') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
            ← Back to Payment Review
        </a>
        <h1 class="text-3xl font-bold text-gray-800">Payment Detail</h1>
        <p class="text-gray-600 mt-2">Review payment information and approve or reject</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Payment Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Student Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Student Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Name</p>
                        <p class="font-semibold text-gray-800">{{ $payment->student->user->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Email</p>
                        <p class="font-semibold text-gray-800">{{ $payment->student->user->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Student ID</p>
                        <p class="font-semibold text-gray-800">{{ $payment->student->registration_no ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Phone</p>
                        <p class="font-semibold text-gray-800">{{ $payment->student->user->phone ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Course Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Course Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Course Name</p>
                        <p class="font-semibold text-gray-800">{{ $payment->course->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Course Code</p>
                        <p class="font-semibold text-gray-800">{{ $payment->course->code ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Course Fee</p>
                        <p class="font-semibold text-gray-800 text-green-600">৳{{ number_format($payment->course->price ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Payment Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Payment Method</p>
                        <p class="font-semibold text-gray-800">
                            <span class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800">
                                {{ strtoupper($payment->payment_method) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Transaction ID</p>
                        <p class="font-semibold text-gray-800">{{ $payment->transaction_id }}</p>
                    </div>
                    @if($payment->sender_number)
                    <div>
                        <p class="text-sm text-gray-600">Sender bKash Number</p>
                        <p class="font-semibold text-gray-800">{{ $payment->sender_number }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-sm text-gray-600">Amount Paid</p>
                        <p class="font-semibold text-gray-800 text-2xl text-green-600">৳{{ number_format($payment->amount, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Submitted At</p>
                        <p class="font-semibold text-gray-800">{{ $payment->submitted_at->format('M d, Y h:i A') }}</p>
                    </div>
                    @if($payment->notes)
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-600">Student Notes</p>
                        <p class="text-gray-800">{{ $payment->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Payment Screenshot -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Payment Screenshot</h2>
                <div class="border-2 border-gray-200 rounded-lg p-4">
                    @if($payment->screenshot_path)
                        @php
                            $extension = pathinfo($payment->screenshot_path, PATHINFO_EXTENSION);
                        @endphp
                        @if(in_array(strtolower($extension), ['jpg', 'jpeg', 'png']))
                            <img src="{{ route('payment.proof', $payment) }}"
                                alt="Payment Screenshot" 
                                class="max-w-full h-auto rounded cursor-pointer"
                                onclick="window.open(this.src, '_blank')">
                            <p class="text-sm text-gray-500 mt-2 text-center">Click image to view full size</p>
                        @elseif(strtolower($extension) === 'pdf')
                            <div class="text-center py-8">
                                <svg class="mx-auto h-16 w-16 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"></path>
                                </svg>
                                <p class="mt-2 text-gray-700 font-semibold">PDF Document</p>
                                <a href="{{ route('payment.proof', $payment) }}"
                                    target="_blank" 
                                    class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition">
                                    View PDF
                                </a>
                            </div>
                        @endif
                    @else
                        <p class="text-gray-500 text-center py-8">No screenshot available</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Actions -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Review Actions</h2>

                @if($payment->isPending())
                    <!-- Approve Form -->
                    <form action="{{ route('payment.approve', $payment) }}" method="POST" class="mb-4">
                        @csrf
                        <div class="mb-4">
                            <label for="admin_notes_approve" class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Optional)</label>
                            <textarea id="admin_notes_approve" name="admin_notes" rows="3" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                                placeholder="Add any notes about this approval"></textarea>
                        </div>
                        <button type="submit" 
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-lg transition"
                            onclick="return confirm('Are you sure you want to approve this payment and enroll the student?')">
                            ✓ Approve Payment
                        </button>
                    </form>

                    <!-- Reject Form -->
                    <form action="{{ route('payment.reject', $payment) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="admin_notes_reject" class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                            <textarea id="admin_notes_reject" name="admin_notes" rows="3" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" 
                                placeholder="Explain why this payment is being rejected" required></textarea>
                            @error('admin_notes')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" 
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-lg transition"
                            onclick="return confirm('Are you sure you want to reject this payment?')">
                            ✗ Reject Payment
                        </button>
                    </form>
                @else
                    <!-- Payment Already Processed -->
                    <div class="text-center py-6">
                        @if($payment->isApproved())
                            <div class="bg-green-100 border-2 border-green-500 rounded-lg p-4">
                                <svg class="mx-auto h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="mt-2 font-semibold text-green-800">Payment Approved</p>
                                <p class="text-sm text-green-600 mt-1">{{ $payment->reviewed_at->format('M d, Y') }}</p>
                            </div>
                        @elseif($payment->isRejected())
                            <div class="bg-red-100 border-2 border-red-500 rounded-lg p-4">
                                <svg class="mx-auto h-12 w-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="mt-2 font-semibold text-red-800">Payment Rejected</p>
                                <p class="text-sm text-red-600 mt-1">{{ $payment->reviewed_at->format('M d, Y') }}</p>
                            </div>
                        @endif

                        @if($payment->admin_notes)
                            <div class="mt-4 text-left">
                                <p class="text-sm font-medium text-gray-700">Admin Notes:</p>
                                <p class="text-sm text-gray-600 mt-1">{{ $payment->admin_notes }}</p>
                            </div>
                        @endif

                        @if($payment->reviewer)
                            <p class="text-sm text-gray-500 mt-4">Reviewed by: {{ $payment->reviewer->name }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
