@extends('layouts.admin')

@section('title', 'Fee Status')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Fee Status</h2>
            <p class="text-sm text-muted-foreground">Monitor payment status and fee records for your children.</p>
        </div>
    </div>

    @if(isset($feesData) && $feesData->count() > 0)
        @foreach($feesData as $data)
            @php
                $student = $data['student'];
            @endphp
            
            <x-ui.card>
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            @if($student->photo)
                                <img src="{{ $student->photo }}" alt="{{ $student->name }}" class="w-12 h-12 rounded-full object-cover">
                            @else
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center">
                                    <span class="text-white text-lg font-bold">{{ substr($student->name, 0, 1) }}</span>
                                </div>
                            @endif
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $student->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $student->batch?->course?->name ?? 'No Course' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <x-ui.card-content class="pt-6">
                    <!-- Fee Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <p class="text-2xl font-bold text-gray-900">৳{{ number_format($data['total_fee'], 2) }}</p>
                            <p class="text-sm text-gray-600">Total Fee</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <p class="text-2xl font-bold text-green-600">৳{{ number_format($data['total_paid'], 2) }}</p>
                            <p class="text-sm text-gray-600">Paid</p>
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-lg">
                            <p class="text-2xl font-bold text-red-600">৳{{ number_format($data['pending_amount'], 2) }}</p>
                            <p class="text-sm text-gray-600">Pending</p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-2xl font-bold text-blue-600">{{ $data['payment_percentage'] }}%</p>
                            <p class="text-sm text-gray-600">Progress</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Payment Progress</span>
                            <span class="text-sm font-medium text-gray-700">{{ $data['payment_percentage'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-bd-green h-3 rounded-full transition-all" style="width: {{ $data['payment_percentage'] }}%"></div>
                        </div>
                    </div>

                    <!-- Payment History -->
                    @if($data['payments']->count() > 0)
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Payment History</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt #</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($data['payments'] as $payment)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                #{{ $payment->receipt_number ?? $payment->id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $payment->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                ৳{{ number_format($payment->amount, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                                                {{ $payment->payment_method ?? 'Cash' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full 
                                                    @if($payment->status === 'completed') bg-green-100 text-green-800
                                                    @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800
                                                    @else bg-red-100 text-red-800 @endif">
                                                    {{ ucfirst($payment->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-gray-500 py-8">No payment records found.</p>
                    @endif
                </x-ui.card-content>
            </x-ui.card>
        @endforeach
    @else
        <x-ui.card>
            <x-ui.card-content class="pt-6">
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No fee data</h3>
                    <p class="mt-1 text-sm text-gray-500">Fee records will appear here once available.</p>
                </div>
            </x-ui.card-content>
        </x-ui.card>
    @endif
</div>
@endsection