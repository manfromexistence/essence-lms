@extends('layouts.admin')

@section('title', 'Learning Materials')
@section('page-title', 'Learning Materials')
@section('page-description', 'Upload and manage study materials for each course')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold tracking-tight">Course Materials</h2>
    </div>

    <x-ui.card>
        <x-ui.card-header>
            <x-ui.card-title>Select a Course</x-ui.card-title>
            <x-ui.card-description>Choose a course to manage its learning materials</x-ui.card-description>
        </x-ui.card-header>
        <x-ui.card-content>
            @if($courses->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($courses as $course)
                        <a href="{{ route('dashboard.courses.materials', ['course_id' => $course->id]) }}"
                           class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-bd-green hover:shadow-md">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-bd-green">
                                        <i class="fas fa-file-alt text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 group-hover:text-bd-green">{{ $course->name }}</h3>
                                        <p class="text-xs text-gray-500">{{ $course->code }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                    {{ $course->materials()->count() }} files
                                </span>
                            </div>
                            <p class="mt-3 text-sm text-gray-500 line-clamp-2">{{ $course->description }}</p>
                            <span class="mt-4 inline-flex items-center text-sm font-semibold text-bd-green">
                                Manage materials <i class="fas fa-arrow-right ml-2"></i>
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center text-gray-500">
                    <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
                    <p>No active courses yet. Create a course first.</p>
                </div>
            @endif
        </x-ui.card-content>
    </x-ui.card>
</div>
@endsection
