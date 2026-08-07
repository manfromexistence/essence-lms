@extends('layouts.admin')

@section('title', 'Student Attendance Tracking')
@section('page-title', 'শিক্ষার্থী উপস্থিতি ট্র্যাকিং')
@section('page-description', 'Track and manage student attendance')

@section('content')
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Records</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_records'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Present Today</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['present_today'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Absent Today</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['absent_today'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Late Today</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['late_today'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="{{ route('dashboard.students.attendance') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Student</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                           placeholder="Search by name..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-bd-green focus:border-transparent">
                </div>

                <div>
                    <label for="batch_id" class="block text-sm font-medium text-gray-700 mb-1">Batch</label>
                    <x-ui.select name="batch_id" id="batch_id" :selected="(string) request('batch_id')">
                        <option value="">All Batches</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                {{ $batch->name }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <x-ui.select name="status" id="status" :selected="(string) request('status')">
                        <option value="">All Status</option>
                        <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                        <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                        <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                    </x-ui.select>
                </div>

                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-bd-green focus:border-transparent">
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-bd-green focus:border-transparent">
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Filter
                </button>
                <a href="{{ route('dashboard.students.attendance') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h2 class="text-lg font-semibold text-gray-900">Attendance Records</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $attendances->total() ?? $attendances->count() }} Total
                </span>
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'student', 'label' => 'Student'],
                ['key' => 'batch', 'label' => 'Batch'],
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'day', 'label' => 'Day'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'recorded', 'label' => 'Recorded At'],
            ];
        @endphp

        <x-ui.data-table id="attendance-table" :headers="$headers" :rows="$attendances" :route="route('dashboard.students.attendance')">
            @forelse($attendances as $attendance)
                <tr class="hover:bg-gray-50 transition-colors">
                    <!-- Student -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="shrink-0 w-10 h-10">
                                @if ($attendance->student->profile_image)
                                    <img class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm"
                                        src="{{ Str::startsWith($attendance->student->profile_image, 'http') ? $attendance->student->profile_image : asset('storage/' . $attendance->student->profile_image) }}"
                                        alt="{{ $attendance->student->user->name ?? 'Student' }}">
                                @else
                                    <div class="w-10 h-10 bg-bd-green rounded-full flex items-center justify-center text-white font-bold shadow-sm">
                                        {{ strtoupper(substr($attendance->student->user->name ?? 'S', 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">{{ $attendance->student->user->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">ID: STU-{{ str_pad($attendance->student->id, 5, '0', STR_PAD_LEFT) }}</div>
                            </div>
                        </div>
                    </td>

                    <!-- Batch -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($attendance->batch)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                {{ $attendance->batch->name }}
                            </span>
                            @if($attendance->batch->code)
                                <div class="text-xs text-gray-500 mt-1">{{ $attendance->batch->code }}</div>
                            @endif
                        @else
                            <span class="text-sm text-gray-500">N/A</span>
                        @endif
                    </td>

                    <!-- Date -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            {{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($attendance->date)->diffForHumans() }}
                        </div>
                    </td>

                    <!-- Day -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                            {{ \Carbon\Carbon::parse($attendance->date)->format('l') }}
                        </span>
                    </td>

                    <!-- Status -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $statusColors = [
                                'present' => 'bg-green-100 text-green-800',
                                'absent' => 'bg-red-100 text-red-800',
                                'late' => 'bg-yellow-100 text-yellow-800',
                            ];
                            $statusIcons = [
                                'present' => '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                                'absent' => '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                                'late' => '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$attendance->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {!! $statusIcons[$attendance->status] ?? '' !!}
                            {{ ucfirst($attendance->status) }}
                        </span>
                    </td>

                    <!-- Recorded At -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $attendance->created_at->format('M d, Y') }}
                        <div class="text-xs text-gray-400">{{ $attendance->created_at->format('h:i A') }}</div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            <h3 class="text-sm font-medium text-gray-900">No attendance records found</h3>
                            <p class="mt-1 text-sm text-gray-500">Start tracking attendance to see records here.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-ui.data-table>
    </div>
@endsection