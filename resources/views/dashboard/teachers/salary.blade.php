@extends('layouts.admin')

@section('title', 'Teacher Salary')
@section('page-title', 'শিক্ষক বেতন')
@section('page-description', 'Manage teacher salary records and payments')

@section('content')
    <div class="bg-white rounded-xl shadow-md border border-gray-100">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h2 class="text-lg font-semibold text-gray-900">Salary Management</h2>
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                    {{ $salaries->total() }} Records
                </span>
            </div>
            <button
                class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Salary Record
            </button>
        </div>

        @php
            $headers = [
                ['key' => 'teacher', 'label' => 'Teacher'],
                ['key' => 'month', 'label' => 'Month'],
                ['key' => 'amount', 'label' => 'Amount'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'payment_date', 'label' => 'Payment Date'],
                ['key' => 'actions', 'label' => 'Actions'],
            ];
        @endphp

        <x-ui.data-table id="salary-table" :headers="$headers" :rows="$salaries" :route="route('dashboard.teachers.salary')">
            @forelse($salaries as $salary)
                <tr class="hover:bg-gray-50 transition-colors">
                    <!-- Teacher -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="shrink-0 w-10 h-10">
                                @if ($salary->teacher->profile_image)
                                    <img src="{{ media_url($salary->teacher->profile_image ?? '') }}"
                                        alt="{{ $salary->teacher->user->name }}"
                                        class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm">
                                @else
                                    <div
                                        class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center text-white font-bold shadow-sm">
                                        {{ strtoupper(substr($salary->teacher->user->name ?? 'T', 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">{{ $salary->teacher->user->name ?? 'N/A' }}
                                </div>
                                <div class="text-xs text-gray-500">{{ $salary->teacher->department ?? 'General' }}</div>
                            </div>
                        </div>
                    </td>

                    <!-- Month -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $salary->month }}
                        </span>
                    </td>

                    <!-- Amount -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ${{ number_format($salary->amount, 2) }}
                    </td>

                    <!-- Status -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $salary->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($salary->status) }}
                        </span>
                    </td>

                    <!-- Payment Date -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $salary->payment_date ? $salary->payment_date->format('M d, Y') : 'Not paid' }}
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                        <div class="flex justify-start space-x-2">
                            @if ($salary->status === 'pending')
                                <button class="text-green-600 hover:text-green-900 transition-colors" title="Mark as Paid">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            @endif
                            <button class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button class="text-red-600 hover:text-red-900 transition-colors" title="Delete">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-sm font-medium text-gray-900">No salary records</h3>
                            <p class="mt-1 text-sm text-gray-500">Salary records will appear here once salaries are
                                processed.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-ui.data-table>
    </div>
@endsection
