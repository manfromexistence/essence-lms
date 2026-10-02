@extends('layouts.admin')

@section('title', 'Edit Exam')
@section('page-title', 'পরীক্ষা সম্পাদনা')
@section('page-description', 'Edit exam details and settings')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">Edit Exam</h2>
                <a href="{{ route('dashboard.exams.show', $exam) }}" class="text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>

            <form action="{{ route('dashboard.exams.update', $exam) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Exam Title -->
                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Exam Title *</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $exam->title) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Batch and Course -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="batch_id" class="block text-sm font-medium text-gray-700 mb-2">Batch</label>
                        <select name="batch_id" id="batch_id" disabled
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                            <option value="{{ $exam->batch_id }}">{{ $exam->batch->name ?? 'N/A' }}</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Batch cannot be changed after creation</p>
                    </div>

                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-2">Course</label>
                        <select name="course_id" id="course_id" disabled
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                            <option value="{{ $exam->course_id }}">{{ $exam->course->name ?? 'N/A' }}</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Course cannot be changed after creation</p>
                    </div>
                </div>

                <!-- Marks -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="total_marks" class="block text-sm font-medium text-gray-700 mb-2">Total Marks *</label>
                        <input type="number" name="total_marks" id="total_marks" value="{{ old('total_marks', $exam->total_marks) }}" required min="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('total_marks')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="pass_marks" class="block text-sm font-medium text-gray-700 mb-2">Pass Marks *</label>
                        <input type="number" name="pass_marks" id="pass_marks" value="{{ old('pass_marks', $exam->pass_marks) }}" required min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('pass_marks')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Duration -->
                <div class="mb-4">
                    <label for="duration_minutes" class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes) *</label>
                    <input type="number" name="duration_minutes" id="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes) }}" required min="1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('duration_minutes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Start and End Time -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">Start Time</label>
                        <input type="datetime-local" name="start_time" id="start_time" 
                            value="{{ old('start_time', $exam->start_time ? $exam->start_time->format('Y-m-d\TH:i') : '') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('start_time')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">End Time</label>
                        <input type="datetime-local" name="end_time" id="end_time" 
                            value="{{ old('end_time', $exam->end_time ? $exam->end_time->format('Y-m-d\TH:i') : '') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('end_time')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Status -->
                <div class="mb-4">
                    <x-ui.select 
                        name="status" 
                        label="Status" 
                        :selected="old('status', $exam->status)"
                        :required="true"
                    >
                        <option value="draft">Draft</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="active">Active</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="completed">Completed</option>
                    </x-ui.select>
                </div>

                <!-- Instructions -->
                <div class="mb-6">
                    <label for="instructions" class="block text-sm font-medium text-gray-700 mb-2">Instructions</label>
                    <textarea name="instructions" id="instructions" rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('instructions', $exam->instructions) }}</textarea>
                    @error('instructions')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <a href="{{ route('dashboard.exams.show', $exam) }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors">
                        Update Exam
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
