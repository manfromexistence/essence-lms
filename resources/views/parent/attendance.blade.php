@extends('layouts.admin')

@section('title', 'Attendance Records')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Attendance Records</h2>
            <p class="text-sm text-muted-foreground">View your children's attendance history and statistics.</p>
        </div>
    </div>

    @if(isset($attendanceData) && $attendanceData->count() > 0)
        @foreach($attendanceData as $data)
            @php
                $student = $data['student'];
                $attendanceRate = $data['attendance_rate'];
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
                    <!-- Statistics -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <p class="text-2xl font-bold text-gray-900">{{ $data['total_days'] }}</p>
                            <p class="text-sm text-gray-600">Total Days</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <p class="text-2xl font-bold text-green-600">{{ $data['present_days'] }}</p>
                            <p class="text-sm text-gray-600">Present</p>
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-lg">
                            <p class="text-2xl font-bold text-red-600">{{ $data['absent_days'] }}</p>
                            <p class="text-sm text-gray-600">Absent</p>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 rounded-lg">
                            <p class="text-2xl font-bold text-yellow-600">{{ $data['late_days'] }}</p>
                            <p class="text-sm text-gray-600">Late</p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-2xl font-bold text-blue-600">{{ $attendanceRate }}%</p>
                            <p class="text-sm text-gray-600">Rate</p>
                        </div>
                    </div>

                    <!-- Recent Attendance -->
                    @if($data['recent_attendance']->count() > 0)
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Recent Attendance (Last 30 Days)</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($data['recent_attendance'] as $attendance)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full 
                                                    @if($attendance->status === 'present') bg-green-100 text-green-800
                                                    @elseif($attendance->status === 'absent') bg-red-100 text-red-800
                                                    @elseif($attendance->status === 'late') bg-yellow-100 text-yellow-800
                                                    @else bg-gray-100 text-gray-800 @endif">
                                                    {{ ucfirst($attendance->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                {{ $attendance->remarks ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-gray-500 py-8">No attendance records found.</p>
                    @endif
                </x-ui.card-content>
            </x-ui.card>
        @endforeach
    @else
        <x-ui.card>
            <x-ui.card-content class="pt-6">
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No attendance data</h3>
                    <p class="mt-1 text-sm text-gray-500">Attendance records will appear here once available.</p>
                </div>
            </x-ui.card-content>
        </x-ui.card>
    @endif
</div>
@endsection