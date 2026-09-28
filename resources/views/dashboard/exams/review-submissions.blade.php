@extends('layouts.admin')

@section('title', 'Review Exam Submissions')
@section('page-title', 'পরীক্ষার উত্তর পর্যালোচনা')
@section('page-description', 'Review and grade student exam submissions')

@push('styles')
<style>
    .canvas-container {
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        margin: 1rem 0;
    }
    .tool-btn {
        @apply px-4 py-2 rounded-lg transition-colors;
    }
    .tool-btn.active {
        @apply bg-bd-green text-white;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Exam Info -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $exam->title }}</h2>
                <p class="text-gray-500 mt-1">{{ $exam->course->name ?? 'N/A' }} - {{ $exam->batch->name ?? 'N/A' }}</p>
            </div>
            <a href="{{ route('dashboard.exams.show', $exam) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                Back to Exam
            </a>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-sm text-blue-600 font-medium">Total Submissions</div>
                <div class="text-2xl font-bold text-blue-900 mt-1">{{ $submissions->total() }}</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-sm text-yellow-600 font-medium">Pending Review</div>
                <div class="text-2xl font-bold text-yellow-900 mt-1">{{ $pendingCount }}</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-sm text-green-600 font-medium">Reviewed</div>
                <div class="text-2xl font-bold text-green-900 mt-1">{{ $reviewedCount }}</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-sm text-purple-600 font-medium">Average Score</div>
                <div class="text-2xl font-bold text-purple-900 mt-1">{{ number_format($averageScore, 1) }}%</div>
            </div>
        </div>
    </div>

    <!-- Submissions List -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Student Submissions</h3>

        @if($submissions->count() > 0)
        <div class="space-y-4">
            @foreach($submissions as $submission)
            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                <span class="text-indigo-600 font-semibold">{{ substr($submission->student->name_bn ?? 'S', 0, 1) }}</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">{{ $submission->student->name_bn ?? $submission->student->user->name ?? 'N/A' }}</h4>
                                <p class="text-sm text-gray-500">ID: {{ $submission->student->registration_no ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Submitted</p>
                            <p class="text-sm font-medium text-gray-900">{{ $submission->submitted_at->format('M d, Y h:i A') }}</p>
                        </div>

                        @if($submission->isEvaluated())
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Score</p>
                            <p class="text-lg font-bold text-green-600">{{ $submission->marks }}/{{ $exam->total_marks }}</p>
                        </div>
                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Reviewed</span>
                        @else
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">Pending</span>
                        @endif

                        <a href="{{ route('dashboard.exams.review-submission', [$exam, $submission]) }}" 
                           class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            {{ $submission->isEvaluated() ? 'View Review' : 'Review Now' }}
                        </a>
                    </div>
                </div>

                @if($submission->isEvaluated() && $submission->feedback)
                <div class="mt-3 p-3 bg-blue-50 rounded-lg">
                    <p class="text-sm text-blue-900"><strong>Feedback:</strong> {{ $submission->feedback }}</p>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $submissions->links() }}
        </div>
        @else
        <div class="text-center py-8 text-gray-500">
            <p>No submissions yet.</p>
        </div>
        @endif
    </div>
</div>
@endsection
