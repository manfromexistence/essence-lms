@extends('layouts.admin')

@section('title', 'Create Exam')
@section('page-title', 'নতুন পরীক্ষা')
@section('page-description', 'Create a new MCQ or CQ exam')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">Create Exam</h2>
                <a href="{{ route('dashboard.exams.index') }}" class="text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('dashboard.exams.store') }}" method="POST" x-data="{ examType: '{{ old('type', 'mcq') }}' }">
                @csrf

                <!-- Exam Type -->
                <div class="mb-4">
                    <span class="block text-sm font-medium text-gray-700 mb-2">Exam Type *</span>
                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="type" value="mcq" x-model="examType" class="text-bd-green focus:ring-bd-green" required>
                            <span class="ml-2 text-sm text-gray-700">MCQ (Multiple Choice)</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="type" value="cq" x-model="examType" class="text-bd-green focus:ring-bd-green" required>
                            <span class="ml-2 text-sm text-gray-700">CQ (Written / Creative)</span>
                        </label>
                    </div>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Exam Title -->
                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Exam Title *</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required
                        placeholder="e.g. Advanced Microsoft Office - Final Term"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Batch and Course -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="batch_id" class="block text-sm font-medium text-gray-700 mb-2">Batch *</label>
                        <select name="batch_id" id="batch_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select a batch</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" @selected(old('batch_id') == $batch->id)>
                                    {{ $batch->name }}{{ $batch->course ? ' — ' . $batch->course->name : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('batch_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-2">Course</label>
                        <select name="course_id" id="course_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select a course (optional)</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>{{ $course->name }}</option>
                            @endforeach
                        </select>
                        @error('course_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Marks -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="total_marks" class="block text-sm font-medium text-gray-700 mb-2">Total Marks *</label>
                        <input type="number" name="total_marks" id="total_marks" value="{{ old('total_marks', 100) }}" required min="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('total_marks')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="pass_marks" class="block text-sm font-medium text-gray-700 mb-2">Pass Marks *</label>
                        <input type="number" name="pass_marks" id="pass_marks" value="{{ old('pass_marks', 40) }}" required min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('pass_marks')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Duration -->
                <div class="mb-4">
                    <label for="duration_minutes" class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes) *</label>
                    <input type="number" name="duration_minutes" id="duration_minutes" value="{{ old('duration_minutes', 60) }}" required min="1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('duration_minutes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Start and End Time -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">Start Time</label>
                        <input type="datetime-local" name="start_time" id="start_time" value="{{ old('start_time') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('start_time')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">End Time</label>
                        <input type="datetime-local" name="end_time" id="end_time" value="{{ old('end_time') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="mt-1 text-xs text-gray-500">Must be after the start time</p>
                        @error('end_time')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Instructions -->
                <div class="mb-6">
                    <label for="instructions" class="block text-sm font-medium text-gray-700 mb-2">Instructions</label>
                    <textarea name="instructions" id="instructions" rows="4"
                        placeholder="Optional instructions shown to students before the exam"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('instructions') }}</textarea>
                    @error('instructions')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <a href="{{ route('dashboard.exams.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors">
                        Create Exam
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
