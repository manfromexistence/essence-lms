@extends('layouts.admin')

@section('title', 'Manage Courses')
@section('page-title', 'Course Management')
@section('page-description', 'Manage all courses available in the system')

@section('content')
    <div class="bg-white rounded-xl shadow-md">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h2 class="text-lg font-semibold text-gray-900">All Courses</h2>
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                    {{ $courses->count() }} Total
                </span>
            </div>
            <a href="{{ route('dashboard.courses.create') }}"
                class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Course
            </a>
        </div>

        <!-- Filters -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <form action="{{ route('dashboard.courses.index') }}" method="GET" class="flex items-center space-x-4">
                <div class="flex-1">
                    <x-ui.input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Search by name, code, or description..." 
                    />
                </div>
                <div class="w-40">
                    <x-ui.select
                        name="delivery_mode"
                        :options="['all' => 'All Modes', 'online' => 'Online', 'offline' => 'Offline']"
                        :selected="request('delivery_mode', $modeFilter ?? session('course_mode', 'all'))"
                    />
                </div>
                <div class="w-48">
                    <x-ui.select 
                        name="status" 
                        :options="['' => 'All Status', 'active' => 'Active', 'inactive' => 'Inactive', 'draft' => 'Draft']" 
                        :selected="request('status')" 
                    />
                </div>
                <x-ui.button type="submit" variant="default" class="bg-gray-800 hover:bg-gray-900">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                </x-ui.button>
                @if(request('search') || request('status') || request('delivery_mode'))
                    <a href="{{ route('dashboard.courses.index', ['delivery_mode' => 'all']) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Course Grid -->
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 auto-rows-fr justify-items-stretch items-stretch">
            @forelse($courses as $course)
                    <x-ui.card class="group relative overflow-hidden hover:shadow-xl hover:border-bd-green transition-all duration-200 h-full flex flex-col min-h-96">
                        <a href="{{ route('dashboard.courses.show', $course) }}" class="block relative h-48 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center overflow-hidden">
                            @if($course->image)
                                @if(str_starts_with($course->image, 'http'))
                                    <img src="{{ $course->image }}" alt="{{ $course->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                @else
                                    <img src="{{ media_url($course->image) }}" alt="{{ $course->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                @endif
                            @else
                                <div class="flex items-center justify-center w-full h-full bg-gradient-to-br from-emerald-400 to-teal-500">
                                    <svg class="w-16 h-16 text-white opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                            @endif
                            <div class="absolute top-3 right-3">
                                <x-ui.badge :variant="($course->status ?? 'active') === 'active' ? 'success' : 'secondary'">
                                    {{ ucfirst($course->status ?? 'Active') }}
                                </x-ui.badge>
                            </div>
                        </a>
                        
                        <x-ui.card-content class="flex-1 flex flex-col p-5 relative">
                            <a href="{{ route('dashboard.courses.show', $course) }}" class="block mb-2">
                                <h3 class="text-lg font-bold text-gray-900 line-clamp-2 min-h-[56px] group-hover:text-bd-green transition-colors">
                                    {{ $course->name ?? 'Course Name' }}
                                </h3>
                            </a>
                            <a href="{{ route('dashboard.courses.show', $course) }}" class="block mb-4 flex-1">
                                <p class="text-sm text-gray-600 line-clamp-2">
                                    {{ $course->description ?? 'Learn full-stack web development from scratch with HTML, CSS, JavaScript, PHP, and Laravel.' }}
                                </p>
                            </a>
                            <a href="{{ route('dashboard.courses.show', $course) }}" class="block mb-4 pb-4 border-b border-gray-100">
                                <div class="flex items-center justify-between text-sm text-gray-600">
                                    <span class="flex items-center" title="Students enrolled">
                                        <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <span class="font-medium">{{ $course->students_count ?? 0 }}</span>
                                    </span>
                                    <span class="flex items-center" title="Videos">
                                        <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <span class="font-medium">{{ $course->videos_count ?? 0 }}</span>
                                    </span>
                                </div>
                            </a>
                            <div class="flex items-center justify-between mt-auto pt-2">
                                <a href="{{ route('dashboard.courses.show', $course) }}" class="flex-1 mr-2">
                                    <span class="text-2xl font-bold text-bd-green hover:text-bd-green-dark transition-colors">
                                        ৳{{ number_format($course->price ?? 0) }}
                                    </span>
                                </a>
                                <div class="flex items-center space-x-1 relative z-50">
                                    <a href="{{ route('dashboard.courses.edit', $course) }}" title="Edit Course"
                                        onclick="event.stopPropagation();"
                                        class="p-2 text-gray-500 hover:text-bd-green hover:bg-emerald-50 rounded-lg transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <div onclick="event.stopPropagation();">
                                        <x-ui.delete-confirm 
                                            :action="route('dashboard.courses.destroy', $course)"
                                            title="Delete Course"
                                            message="Are you sure you want to delete this course? This action cannot be undone."
                                            triggerClass="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            <x-slot name="trigger">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </x-slot>
                                        </x-ui.delete-confirm>
                                    </div>
                                </div>
                            </div>
                        </x-ui.card-content>
                    </x-ui.card>
            @empty
                <div class="col-span-full py-12 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No courses found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new course.</p>
                </div>
            @endforelse
            </div>
        </div>

        <!-- Pagination -->
        @if(method_exists($courses, 'hasPages') && $courses->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $courses->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection