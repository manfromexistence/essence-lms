@extends('layouts.admin')

@section('title', 'Class ' . $classNumber . ' Details')
@section('page-title', 'Class ' . $classNumber)
@section('page-description', 'View all courses, batches, and students for Class ' . $classNumber)

@section('content')
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-bd-green">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Courses</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $courses->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-bd-green bg-opacity-10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-bd-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-amber-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Batches</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $batches->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-amber-500 bg-opacity-10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Students</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $students->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-500 bg-opacity-10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Courses Section -->
    <div class="bg-white rounded-xl shadow-md mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Courses</h2>
            <a href="{{ route('dashboard.courses.create') }}?class={{ $classNumber }}" 
               class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Course
            </a>
        </div>
        <div class="p-6">
            @if($courses->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($courses as $course)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <h3 class="font-semibold text-gray-900 mb-2">{{ $course->name }}</h3>
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $course->description ?? 'No description' }}</p>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">{{ $course->batches->count() }} Batches</span>
                                <a href="{{ route('dashboard.courses.edit', $course) }}" class="text-bd-green hover:underline">Edit</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-center text-gray-500 py-8">No courses found for this class.</p>
            @endif
        </div>
    </div>

    <!-- Batches Section -->
    <div class="bg-white rounded-xl shadow-md mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Batches</h2>
            <a href="{{ route('dashboard.batches.create') }}?class={{ $classNumber }}" 
               class="inline-flex items-center px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Batch
            </a>
        </div>
        <div class="p-6">
            @if($batches->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedule</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Students</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($batches as $batch)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $batch->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $batch->code }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $batch->course->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $batch->schedule ?? 'Not scheduled' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $batch->students->count() }} / {{ $batch->max_students ?? '∞' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $batch->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($batch->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="{{ route('dashboard.batches.edit', $batch) }}" class="text-bd-green hover:underline">Edit</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center text-gray-500 py-8">No batches found for this class.</p>
            @endif
        </div>
    </div>

    <!-- Students Section -->
    <div class="bg-white rounded-xl shadow-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Students</h2>
            <a href="{{ route('dashboard.students.create') }}?class={{ $classNumber }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Student
            </a>
        </div>
        <div class="p-6">
            @if($students->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($students as $student)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center mb-3">
                                @if($student->profile_image)
                                    <img src="{{ media_url($student->profile_image) }}" alt="{{ $student->user->name ?? 'Student' }}" class="w-10 h-10 rounded-full mr-3">
                                @else
                                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold mr-3">
                                        {{ substr($student->user->name ?? 'S', 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $student->user->name ?? 'N/A' }}</h3>
                                    <p class="text-xs text-gray-500">{{ $student->registration_no }}</p>
                                </div>
                            </div>
                            <div class="text-sm text-gray-600">
                                <p>Batch: {{ $student->batch->name ?? 'Not assigned' }}</p>
                                <p>Course: {{ $student->batch->course->name ?? 'N/A' }}</p>
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <a href="{{ route('dashboard.students.edit', $student) }}" class="text-bd-green hover:underline text-sm">View Details</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-center text-gray-500 py-8">No students found for this class.</p>
            @endif
        </div>
    </div>
@endsection
