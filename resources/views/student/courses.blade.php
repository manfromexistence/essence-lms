@extends('layouts.admin')

@section('title', 'Browse Courses')

@section('content')
<div class="min-h-screen bg-gray-100 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Browse Courses</h1>
            <p class="text-gray-600">Explore and enroll in available courses</p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Courses Grid -->
        @if($courses->isEmpty())
        <div class="bg-white shadow rounded-lg p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No courses available</h3>
            <p class="mt-1 text-sm text-gray-500">Check back later for new courses.</p>
        </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($courses as $course)
            <div class="bg-white shadow rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <!-- Course Image -->
                @if($course->image)
                <div class="h-48 bg-gradient-to-br from-indigo-500 to-purple-600 relative">
                    <img src="{{ Storage::url($course->image) }}" alt="{{ $course->name }}" class="w-full h-full object-cover">
                    <!-- Status Badge -->
                    @if(in_array($course->id, $enrolledCourseIds))
                    <span class="absolute top-4 right-4 px-3 py-1 bg-green-500 text-white text-xs font-semibold rounded-full shadow-lg">
                        Enrolled
                    </span>
                    @elseif(in_array($course->id, $pendingPaymentCourseIds))
                    <span class="absolute top-4 right-4 px-3 py-1 bg-yellow-500 text-white text-xs font-semibold rounded-full shadow-lg">
                        Payment Pending
                    </span>
                    @endif
                </div>
                @else
                <div class="h-48 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center relative">
                    <svg class="h-20 w-20 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <!-- Status Badge -->
                    @if(in_array($course->id, $enrolledCourseIds))
                    <span class="absolute top-4 right-4 px-3 py-1 bg-green-500 text-white text-xs font-semibold rounded-full shadow-lg">
                        Enrolled
                    </span>
                    @elseif(in_array($course->id, $pendingPaymentCourseIds))
                    <span class="absolute top-4 right-4 px-3 py-1 bg-yellow-500 text-white text-xs font-semibold rounded-full shadow-lg">
                        Payment Pending
                    </span>
                    @endif
                </div>
                @endif

                <!-- Course Content -->
                <div class="p-6">
                    <!-- Course Title -->
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $course->name }}</h3>
                    
                    <!-- Course Code -->
                    @if($course->code)
                    <p class="text-xs text-gray-500 mb-2">{{ $course->code }}</p>
                    @endif

                    <!-- Course Description -->
                    <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                        {{ $course->description ?? 'No description available.' }}
                    </p>

                    <!-- Course Details -->
                    <div class="space-y-2 mb-4">
                        <!-- Instructor -->
                        @if($course->teachers && $course->teachers->count() > 0)
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="h-4 w-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>{{ $course->teachers->pluck('name')->join(', ') }}</span>
                        </div>
                        @endif

                        <!-- Duration -->
                        @if($course->duration)
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="h-4 w-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ $course->duration }} {{ $course->duration_unit ?? 'months' }}</span>
                        </div>
                        @endif

                        <!-- Students Enrolled -->
                        @if($course->batches && $course->batches->count() > 0)
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="h-4 w-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>{{ $course->batches->count() }} {{ Str::plural('batch', $course->batches->count()) }}</span>
                        </div>
                        @endif
                    </div>

                    <!-- Course Fee and Action -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <div>
                            <p class="text-xs text-gray-500">Course Fee</p>
                            <p class="text-2xl font-bold text-indigo-600">৳{{ number_format($course->price, 2) }}</p>
                        </div>
                        
                        <!-- Action Button -->
                        @if(in_array($course->id, $enrolledCourseIds))
                        <a href="{{ route('student.course.watch', $course) }}" class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700">
                            Continue learning
                        </a>
                        @elseif(in_array($course->id, $pendingPaymentCourseIds))
                        <!-- Payment Pending - Show status -->
                        <span class="px-4 py-2 bg-yellow-100 text-yellow-800 text-sm font-semibold rounded-lg">
                            ⏳ Pending
                        </span>
                        @else
                        <!-- Not Enrolled - Show Buy Course button -->
                        <a href="{{ route('student.payment.form', $course) }}" 
                           class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors duration-200">
                            Buy Course
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Back to Dashboard Link -->
        <div class="mt-8">
            <a href="{{ route('student.dashboard') }}" class="text-indigo-600 hover:text-indigo-500">
                ← Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Add custom styles for line-clamp if not available in Tailwind config -->
<style>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endsection
