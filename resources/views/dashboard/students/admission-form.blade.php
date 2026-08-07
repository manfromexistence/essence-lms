@extends('layouts.admin')

@section('title', 'Digital Admission Form')
@section('page-title', 'ডিজিটাল ভর্তি ফরম')
@section('page-description', 'Manage student admission forms and submissions')

@section('content')
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Applications</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_applications'] }}</p>
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
                    <p class="text-sm font-medium text-gray-600">Pending Admissions</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['pending_admissions'] }}</p>
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
                    <p class="text-sm font-medium text-gray-600">Approved Admissions</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['approved_admissions'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Recent Applications</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['recent_applications'] }}</p>
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
                    <p class="text-sm font-medium text-gray-600">Rejected Admissions</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['rejected_admissions'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="{{ route('dashboard.students.admission-form') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                           placeholder="Search by name, email, phone..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-bd-green focus:border-transparent">
                </div>

                <div>
                    <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                    <select name="course_id" id="course_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-bd-green focus:border-transparent">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }} (Class {{ $course->class }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="admission_status" class="block text-sm font-medium text-gray-700 mb-1">Admission Status</label>
                    <select name="admission_status" id="admission_status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-bd-green focus:border-transparent">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('admission_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('admission_status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('admission_status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="recent" {{ request('admission_status') == 'recent' ? 'selected' : '' }}>Recent (30 days)</option>
                    </select>
                </div>

                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                    <select name="sort" id="sort" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-bd-green focus:border-transparent">
                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Application Date</option>
                        <option value="student" {{ request('sort') == 'student' ? 'selected' : '' }}>Student Name</option>
                        <option value="course" {{ request('sort') == 'course' ? 'selected' : '' }}>Course</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Filter
                </button>
                <a href="{{ route('dashboard.students.admission-form') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-100">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h2 class="text-lg font-semibold text-gray-900">Admission Forms</h2>
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $students->total() ?? $students->count() }} Total
                </span>
            </div>
            <a href="{{ route('dashboard.students.create') }}"
                class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Student
            </a>
        </div>

        @php
            $headers = [
                ['key' => 'student', 'label' => 'Student'],
                ['key' => 'course_batch', 'label' => 'Course & Batch'],
                ['key' => 'class_schedule', 'label' => 'Class & Schedule'],
                ['key' => 'admission_status', 'label' => 'Admission Status'],
                ['key' => 'enrolled', 'label' => 'Enrolled'],
                ['key' => 'actions', 'label' => 'Actions'],
            ];
        @endphp

        <x-ui.data-table id="admission-form-table" :headers="$headers" :rows="$students" :route="route('dashboard.students.admission-form')">
            @forelse($students as $student)
                <tr class="hover:bg-gray-50 transition-colors">
                    <!-- Student -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="shrink-0 w-10 h-10">
                                @if ($student->profile_image)
                                    <img class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm"
                                        src="{{ Str::startsWith($student->profile_image, 'http') ? $student->profile_image : asset('storage/' . $student->profile_image) }}"
                                        alt="{{ $student->user->name }}">
                                @else
                                    <div
                                        class="w-10 h-10 bg-bd-green rounded-full flex items-center justify-center text-white font-bold shadow-sm">
                                        {{ strtoupper(substr($student->user->name ?? 'S', 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">{{ $student->user->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">ID: STU-{{ str_pad($student->id, 5, '0', STR_PAD_LEFT) }}</div>
                                <div class="text-xs text-gray-500">{{ $student->user->email ?? 'N/A' }}</div>
                                @if($student->phone)
                                    <div class="text-xs text-gray-500">{{ $student->phone }}</div>
                                @endif
                            </div>
                        </div>
                    </td>

                    <!-- Course & Batch -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="space-y-1">
                            @if($student->batch && $student->batch->course)
                                <div class="text-sm font-medium text-gray-900">{{ $student->batch->course->name }}</div>
                                <div class="text-xs text-gray-500">Course Code: {{ $student->batch->course->code ?? 'N/A' }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    {{ $student->batch->name }}
                                </span>
                                @if($student->batch->code)
                                    <div class="text-xs text-gray-500">Batch: {{ $student->batch->code }}</div>
                                @endif
                            @else
                                <div class="text-sm text-gray-500">No course assigned</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                    Unassigned
                                </span>
                            @endif
                        </div>
                    </td>

                    <!-- Class & Schedule -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="space-y-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $student->class ? 'Class ' . $student->class : 'N/A' }}
                            </span>
                            @if($student->batch && $student->batch->schedule)
                                <div class="text-xs text-gray-600">{{ $student->batch->schedule }}</div>
                            @else
                                <div class="text-xs text-gray-500">No schedule</div>
                            @endif
                        </div>
                    </td>

                    <!-- Admission Status -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $admissionStatus = $student->admission_status ?? ($student->batch_id ? 'approved' : 'pending');
                            $statusBadge = [
                                'approved' => ['bg-green-100 text-green-800', 'Admitted'],
                                'rejected' => ['bg-red-100 text-red-800', 'Rejected'],
                                'pending' => ['bg-yellow-100 text-yellow-800', 'Pending'],
                            ][$admissionStatus] ?? ['bg-gray-100 text-gray-800', ucfirst($admissionStatus)];
                        @endphp
                        <div class="space-y-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusBadge[0] }}">
                                {{ $statusBadge[1] }}
                            </span>
                            @if($admissionStatus === 'approved' && !$student->batch_id)
                                <div class="text-xs text-gray-500">No batch assigned yet</div>
                            @endif
                        </div>
                    </td>

                    <!-- Enrolled -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $student->created_at->format('M d, Y') }}
                        <div class="text-xs text-gray-400">{{ $student->created_at->format('h:i A') }}</div>
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                        <div class="flex justify-start items-center space-x-3">
                            <a href="{{ route('dashboard.students.show', $student) }}"
                                class="text-gray-500 hover:text-blue-600 transition-colors" title="View Details">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            @if($admissionStatus !== 'approved')
                                <form method="POST" action="{{ route('dashboard.students.admission-status', $student) }}"
                                    class="inline">
                                    @csrf
                                    <input type="hidden" name="admission_status" value="approved">
                                    <button type="submit"
                                        class="inline-flex items-center px-2.5 py-1 rounded-md bg-green-100 text-green-800 text-xs font-semibold hover:bg-green-200 transition-colors"
                                        title="Approve admission">
                                        Approve
                                    </button>
                                </form>
                            @endif
                            @if($admissionStatus !== 'rejected')
                                <form method="POST" action="{{ route('dashboard.students.admission-status', $student) }}"
                                    class="inline">
                                    @csrf
                                    <input type="hidden" name="admission_status" value="rejected">
                                    <button type="submit"
                                        class="inline-flex items-center px-2.5 py-1 rounded-md bg-red-100 text-red-800 text-xs font-semibold hover:bg-red-200 transition-colors"
                                        title="Reject admission">
                                        Reject
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('dashboard.students.edit', $student) }}"
                                class="text-gray-500 hover:text-bd-green transition-colors" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
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
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-sm font-medium text-gray-900">No admission forms found</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by adding a new student admission.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-ui.data-table>
    </div>
@endsection