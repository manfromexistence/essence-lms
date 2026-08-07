@extends('layouts.admin')

@section('title', 'Exam Result')

@section('content')
<div class="min-h-screen bg-gray-100 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Result Header -->
        <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-8 text-center {{ $result->percentage >= 40 ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-red-500 to-rose-600' }}">
                <h1 class="text-2xl font-bold text-white mb-2">{{ $result->exam->title ?? 'Exam Result' }}</h1>
                <div class="text-6xl font-bold text-white my-4">{{ $result->grade }}</div>
                <p class="text-white text-xl">{{ $result->marks }}/{{ $result->total_marks }} marks</p>
                <p class="text-white/80 text-lg">{{ $result->percentage }}%</p>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 border-t">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="text-lg font-semibold {{ $result->percentage >= 40 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $result->percentage >= 40 ? 'Passed' : 'Failed' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Date</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $result->created_at->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Remarks</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $result->feedback ?? $result->remarks ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-between mb-6">
            <a href="{{ route('student.results') }}" class="text-indigo-600 hover:text-indigo-500">← Back to Results</a>
            <a href="{{ route('student.results.mark-sheet', $result) }}" 
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download Mark Sheet
            </a>
        </div>

        <!-- Answer Review -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Answer Review</h2>
                <p class="text-sm text-gray-500">Review your answers with explanations</p>
            </div>
            
            <div class="divide-y divide-gray-200">
                @foreach($questions as $index => $question)
                @php
                    $studentAnswer = $attempt?->answers[$question->id] ?? null;
                    $isCorrect = $studentAnswer === $question->correct_answer;
                @endphp
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <span class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-sm font-medium">Q{{ $index + 1 }}</span>
                        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $isCorrect ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $isCorrect ? '✓ Correct' : '✗ Incorrect' }}
                        </span>
                    </div>
                    
                    <p class="text-gray-900 font-medium mb-4">{{ $question->question_text }}</p>
                    
                    <div class="space-y-2 ml-4">
                        @foreach(['A', 'B', 'C', 'D'] as $optKey)
                        @php
                            $optionIndex = array_search($optKey, ['A', 'B', 'C', 'D']);
                            $optionText = $question->options[$optionIndex] ?? null;
                        @endphp
                        @if($optionText !== null)
                        <div class="flex items-center p-3 rounded-lg
                            @if($optKey === $question->correct_answer) bg-green-50 border border-green-200
                            @elseif($optKey === $studentAnswer && !$isCorrect) bg-red-50 border border-red-200
                            @else bg-gray-50 @endif">
                            <span class="font-semibold mr-2 
                                @if($optKey === $question->correct_answer) text-green-700
                                @elseif($optKey === $studentAnswer && !$isCorrect) text-red-700
                                @else text-gray-600 @endif">{{ $optKey }}.</span>
                            <span class="
                                @if($optKey === $question->correct_answer) text-green-700
                                @elseif($optKey === $studentAnswer && !$isCorrect) text-red-700
                                @else text-gray-700 @endif">
                                {{ $optionText }}
                            </span>
                            @if($optKey === $question->correct_answer)
                            <span class="ml-auto text-green-600 text-sm">✓ Correct Answer</span>
                            @endif
                            @if($optKey === $studentAnswer)
                            <span class="ml-auto text-sm {{ $isCorrect ? 'text-green-600' : 'text-red-600' }}">Your Answer</span>
                            @endif
                        </div>
                        @endif
                        @endforeach
                    </div>
                    
                    @if($question->explanation)
                    <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm font-medium text-blue-800">Explanation:</p>
                        <p class="text-sm text-blue-700 mt-1">{{ $question->explanation }}</p>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
