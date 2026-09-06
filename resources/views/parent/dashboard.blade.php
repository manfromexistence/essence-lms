@extends('layouts.admin')

@section('title', 'Children Overview')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Children Overview</h2>
            <p class="text-sm text-muted-foreground">Monitor your children's academic progress and activities.</p>
        </div>
    </div>

    @if(isset($children) && $children->count() > 0)
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-ui.card>
                <x-ui.card-content class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Children</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $children->count() }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-content class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Avg Attendance</p>
                            <p class="text-3xl font-bold text-green-600 mt-2">{{ number_format($children->avg('attendance_rate'), 1) }}%</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-content class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Pending Fees</p>
                            <p class="text-3xl font-bold text-amber-600 mt-2">৳{{ number_format($children->sum('pending_fees'), 0) }}</p>
                        </div>
                        <div class="p-3 bg-amber-100 rounded-full">
                            <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-content class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Avg Performance</p>
                            <p class="text-3xl font-bold text-purple-600 mt-2">
                                @php
                                    $avgPerformance = $children->filter(fn($c) => $c['latest_result'])->avg(fn($c) => $c['latest_result']->percentage ?? 0);
                                @endphp
                                {{ number_format($avgPerformance, 1) }}%
                            </p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-full">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <!-- Children Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($children as $childData)
                @php
                    $student = $childData['student'];
                    $attendanceRate = $childData['attendance_rate'];
                    $latestResult = $childData['latest_result'];
                    $pendingFees = $childData['pending_fees'];
                @endphp
                
                <x-ui.card class="hover:shadow-lg transition-shadow">
                    <x-ui.card-content class="pt-6">
                        <!-- Student Header -->
                        <div class="flex items-start space-x-4 mb-6">
                            <div class="flex-shrink-0">
                                @if($student->photo)
                                    <img src="{{ asset('storage/' . $student->photo) }}" alt="{{ $student->name }}" class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-lg">
                                @else
                                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center border-4 border-white shadow-lg">
                                        <span class="text-white text-2xl font-bold">{{ substr($student->name, 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xl font-bold text-gray-900 truncate">{{ $student->name }}</h3>
                                <p class="text-sm text-gray-500">ID: {{ $student->student_id }}</p>
                                <div class="mt-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $student->batch?->course?->name ?? 'No Course' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bars -->
                        <div class="space-y-4 mb-6">
                            <!-- Attendance Progress -->
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-700">Attendance Rate</span>
                                    <span class="text-sm font-bold {{ $attendanceRate >= 80 ? 'text-green-600' : ($attendanceRate >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $attendanceRate }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="h-2.5 rounded-full {{ $attendanceRate >= 80 ? 'bg-green-600' : ($attendanceRate >= 60 ? 'bg-yellow-600' : 'bg-red-600') }}" style="width: {{ $attendanceRate }}%"></div>
                                </div>
                            </div>

                            <!-- Performance Progress -->
                            @if($latestResult)
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-medium text-gray-700">Latest Exam Score</span>
                                        <span class="text-sm font-bold text-purple-600">{{ $latestResult->percentage }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-purple-600 h-2.5 rounded-full" style="width: {{ $latestResult->percentage }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-3 gap-3 mb-6">
                            <div class="text-center p-3 bg-blue-50 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-xs text-gray-600">Attendance</p>
                                <p class="text-sm font-bold text-blue-600">{{ $attendanceRate }}%</p>
                            </div>

                            <div class="text-center p-3 bg-purple-50 rounded-lg">
                                <svg class="w-6 h-6 text-purple-600 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-xs text-gray-600">Result</p>
                                <p class="text-sm font-bold text-purple-600">{{ $latestResult ? $latestResult->percentage . '%' : 'N/A' }}</p>
                            </div>

                            <div class="text-center p-3 bg-amber-50 rounded-lg">
                                <svg class="w-6 h-6 text-amber-600 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-xs text-gray-600">Pending</p>
                                <p class="text-sm font-bold {{ $pendingFees > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    ৳{{ number_format($pendingFees, 0) }}
                                </p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-2 gap-3">
                            <a href="{{ route('dashboard.children.progress') }}" class="flex items-center justify-center px-4 py-2.5 bg-primary text-white rounded-lg hover:opacity-90 transition-all text-sm font-medium">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Progress
                            </a>
                            <a href="{{ route('dashboard.children.attendance') }}" class="flex items-center justify-center px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all text-sm font-medium">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                                Attendance
                            </a>
                        </div>
                    </x-ui.card-content>
                </x-ui.card>
            @endforeach
        </div>

        <!-- Quick Actions -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Quick Actions</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <a href="{{ route('dashboard.children.progress') }}" class="flex items-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg hover:shadow-md transition-all group">
                        <div class="p-3 bg-blue-500 rounded-lg mr-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Academic Progress</p>
                            <p class="text-sm text-gray-600">View exam results</p>
                        </div>
                    </a>

                    <a href="{{ route('dashboard.children.attendance') }}" class="flex items-center p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-lg hover:shadow-md transition-all group">
                        <div class="p-3 bg-green-500 rounded-lg mr-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Attendance Records</p>
                            <p class="text-sm text-gray-600">Check attendance</p>
                        </div>
                    </a>

                    <a href="{{ route('dashboard.children.fees') }}" class="flex items-center p-4 bg-gradient-to-br from-amber-50 to-amber-100 rounded-lg hover:shadow-md transition-all group">
                        <div class="p-3 bg-amber-500 rounded-lg mr-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Fee Status</p>
                            <p class="text-sm text-gray-600">Payment history</p>
                        </div>
                    </a>

                    <a href="{{ route('contact') }}" class="flex items-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg hover:shadow-md transition-all group">
                        <div class="p-3 bg-purple-500 rounded-lg mr-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Contact Teacher</p>
                            <p class="text-sm text-gray-600">Send message</p>
                        </div>
                    </a>
                </div>
            </x-ui.card-content>
        </x-ui.card>

    @else
        <x-ui.card>
            <x-ui.card-content class="pt-6">
                <div class="text-center py-12">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No children linked</h3>
                    <p class="mt-2 text-sm text-gray-500">Contact the administrator to link your children's profiles to your account.</p>
                    <div class="mt-6">
                        <a href="{{ route('contact') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:opacity-90">
                            Contact Administrator
                        </a>
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>
    @endif
</div>
@endsection
