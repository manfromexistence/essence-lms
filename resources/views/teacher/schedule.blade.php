@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">My Schedule</h1>
        <p class="text-gray-600">View your weekly class schedule</p>
    </div>

    <div class="bg-white rounded-lg shadow">
        @if($schedules->count() > 0)
            <div class="p-6">
                @foreach($days as $dayNumber => $dayName)
                    @if(isset($schedules[$dayNumber]) && $schedules[$dayNumber]->count() > 0)
                        <div class="mb-6 last:mb-0">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                                <span class="w-2 h-2 bg-blue-600 rounded-full mr-2"></span>
                                {{ $dayName }}
                            </h3>
                            <div class="space-y-3">
                                @foreach($schedules[$dayNumber] as $schedule)
                                    <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="flex-shrink-0">
                                            <div class="w-16 h-16 bg-blue-100 rounded-lg flex flex-col items-center justify-center">
                                                <span class="text-xs text-blue-600 font-medium">{{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i') }}</span>
                                                <span class="text-xs text-blue-600">{{ \Carbon\Carbon::parse($schedule->start_time)->format('A') }}</span>
                                            </div>
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="text-sm font-semibold text-gray-900">{{ $schedule->batch->course->name }}</h4>
                                                    <p class="text-sm text-gray-600">{{ $schedule->batch->name }}</p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-medium text-gray-900">
                                                        {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }} - 
                                                        {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
                                                    </p>
                                                    @if($schedule->room)
                                                        <p class="text-xs text-gray-500">Room: {{ $schedule->room }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No schedule found</h3>
                <p class="mt-1 text-sm text-gray-500">You don't have any classes scheduled yet.</p>
            </div>
        @endif
    </div>

    <!-- Today's Highlight -->
    @php
        $today = strtolower(\Carbon\Carbon::now()->format('l'));
        $todaySchedules = $schedules[$today] ?? collect();
    @endphp
    
    @if($todaySchedules->count() > 0)
        <div class="mt-6 bg-blue-50 border-l-4 border-blue-600 rounded-lg p-6">
            <div class="flex items-center mb-3">
                <svg class="h-6 w-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-blue-900">Today's Classes</h3>
            </div>
            <div class="space-y-2">
                @foreach($todaySchedules as $schedule)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-blue-800 font-medium">{{ $schedule->batch->course->name }} ({{ $schedule->batch->name }})</span>
                        <span class="text-blue-600">
                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }} - 
                            {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
