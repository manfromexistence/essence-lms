@extends('layouts.admin')

@section('title', 'Student Dashboard')

@section('content')
<div class="min-h-screen bg-gray-100">
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Welcome, {{ $student->name ?? 'Student' }}</h1>
                <p class="text-gray-600">{{ $batch?->name ?? 'No batch assigned' }} • {{ $course?->name ?? 'No course' }}</p>
            </div>

            <!-- Alerts -->
            @if(!$payment_summary['is_fully_paid'])
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            You have a pending payment of <strong>৳{{ number_format($payment_summary['due_amount'], 2) }}</strong>
                            <a href="{{ route('student.payments') }}" class="font-medium underline">View Details</a>
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Enrolled Courses -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Enrolled Courses</dt>
                                    <dd class="text-lg font-semibold text-gray-900">{{ $course_progress->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Videos Completed -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Videos Completed</dt>
                                    <dd class="text-lg font-semibold text-gray-900">{{ $course_progress->sum('completed_videos') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Certificates Earned -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Certificates Earned</dt>
                                    <dd class="text-lg font-semibold text-gray-900">{{ $certificates->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Status -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Payment Progress</dt>
                                    <dd class="text-lg font-semibold text-gray-900">{{ $payment_summary['payment_percentage'] }}%</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- My Courses Section -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">My Courses</h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @forelse($course_progress as $item)
                        <div class="px-4 py-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $item['course']->name }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $item['completed_videos'] }}/{{ $item['total_videos'] }} videos completed</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-semibold text-gray-900">{{ $item['progress_percentage'] }}%</span>
                                </div>
                            </div>
                            <div class="mt-2 bg-gray-200 rounded-full h-2">
                                <div class="bg-indigo-600 rounded-full h-2" style="width: {{ $item['progress_percentage'] }}%"></div>
                            </div>
                            <div class="mt-3 text-right">
                                @if($item['progress_percentage'] >= 100)
                                    <a href="{{ route('student.courses') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Review course →</a>
                                @else
                                    <a href="{{ route('student.course.watch', $item['course']) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Continue learning →</a>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="px-4 py-8 text-center text-gray-500">
                            No enrolled courses yet.
                            <a href="{{ route('courses') }}" class="block mt-2 text-sm font-medium text-indigo-600 hover:text-indigo-500">Browse courses →</a>
                        </div>
                        @endforelse
                    </div>
                    <div class="px-4 py-3 bg-gray-50 text-right">
                        <a href="{{ route('student.courses') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all courses →</a>
                    </div>
                </div>

                <!-- Certificates Section -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">My Certificates</h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @forelse($certificates as $certificate)
                        <div class="px-4 py-4 flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $certificate->course?->name ?? 'Course Certificate' }}</p>
                                <p class="text-xs text-gray-500 mt-1">Issued {{ $certificate->issued_at?->format('M d, Y') }}</p>
                            </div>
                            <a href="{{ route('student.certificates.show', $certificate) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View →</a>
                        </div>
                        @empty
                        <div class="px-4 py-8 text-center text-gray-500">
                            No certificates yet. Complete all videos of a course to earn one.
                        </div>
                        @endforelse
                    </div>
                    <div class="px-4 py-3 bg-gray-50 text-right">
                        <a href="{{ route('student.certificates.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all certificates →</a>
                    </div>
                </div>
            </div>

            <!-- Announcements Section -->
            @if($announcements->count() > 0)
            <div class="mt-6 bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Announcements</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($announcements as $announcement)
                    <div class="px-4 py-4">
                        <div class="flex items-start">
                            <span class="flex-shrink-0 h-2 w-2 mt-2 rounded-full 
                                @if($announcement->priority === 'urgent') bg-red-500
                                @elseif($announcement->priority === 'high') bg-orange-500
                                @else bg-blue-500 @endif"></span>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">{{ $announcement->title }}</p>
                                <p class="text-sm text-gray-500 mt-1">{{ Str::limit($announcement->content, 150) }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $announcement->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Group Links -->
            @if($batch && ($batch->telegram_link || $batch->facebook_link))
            <div class="mt-6 bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Join Our Groups</h3>
                <div class="flex space-x-4">
                    @if($batch->telegram_link)
                    <a href="{{ $batch->telegram_link }}" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        Telegram Group
                    </a>
                    @endif
                    @if($batch->facebook_link)
                    <a href="{{ $batch->facebook_link }}" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Facebook Group
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Quick Links -->
            <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('student.courses') }}" class="bg-white shadow rounded-lg p-4 text-center hover:bg-gray-50 transition">
                    <svg class="mx-auto h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span class="mt-2 block text-sm font-medium text-gray-900">My Courses</span>
                </a>
                <a href="{{ route('student.materials') }}" class="bg-white shadow rounded-lg p-4 text-center hover:bg-gray-50 transition">
                    <svg class="mx-auto h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span class="mt-2 block text-sm font-medium text-gray-900">Materials</span>
                </a>
                <a href="{{ route('student.certificates.index') }}" class="bg-white shadow rounded-lg p-4 text-center hover:bg-gray-50 transition">
                    <svg class="mx-auto h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="mt-2 block text-sm font-medium text-gray-900">Certificates</span>
                </a>
                <a href="{{ route('student.payments') }}" class="bg-white shadow rounded-lg p-4 text-center hover:bg-gray-50 transition">
                    <svg class="mx-auto h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="mt-2 block text-sm font-medium text-gray-900">Payments</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection