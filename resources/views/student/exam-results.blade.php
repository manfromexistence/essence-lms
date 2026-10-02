@extends('layouts.admin')

@section('title', 'Exam Results - ' . $exam->title)

@section('content')
<div class="min-h-screen bg-gray-100 py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Result Header -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-8 text-center {{ $performance['passed'] ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-red-500 to-rose-600' }}">
                <h1 class="text-3xl font-bold text-white mb-2">{{ $exam->title }}</h1>
                <div class="text-7xl font-bold text-white my-6">{{ $performance['grade'] }}</div>
                <div class="flex justify-center items-center space-x-8 text-white">
                    <div>
                        <p class="text-4xl font-bold">{{ $performance['score'] }}/{{ $performance['total_marks'] }}</p>
                        <p class="text-lg opacity-90">Total Score</p>
                    </div>
                    <div class="h-16 w-px bg-white opacity-30"></div>
                    <div>
                        <p class="text-4xl font-bold">{{ $performance['percentage'] }}%</p>
                        <p class="text-lg opacity-90">Percentage</p>
                    </div>
                </div>
                <div class="mt-6">
                    <span class="inline-flex items-center px-6 py-2 rounded-full text-lg font-semibold {{ $performance['passed'] ? 'bg-white text-green-600' : 'bg-white text-red-600' }}">
                        @if($performance['passed'])
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            PASSED
                        @else
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            FAILED
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-bold text-gray-900">Performance Metrics</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6">
                <!-- Time Taken -->
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="flex justify-center mb-2">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-blue-900">{{ $performance['time_taken_formatted'] }}</p>
                    <p class="text-sm text-blue-700 mt-1">Time Taken</p>
                </div>

                <!-- Accuracy -->
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <div class="flex justify-center mb-2">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-purple-900">{{ $performance['accuracy'] }}%</p>
                    <p class="text-sm text-purple-700 mt-1">Accuracy Rate</p>
                </div>

                <!-- Rank -->
                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                    <div class="flex justify-center mb-2">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-yellow-900">#{{ $performance['rank'] }}</p>
                    <p class="text-sm text-yellow-700 mt-1">Your Rank</p>
                </div>

                <!-- Total Students -->
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="flex justify-center mb-2">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-green-900">{{ $performance['total_students'] }}</p>
                    <p class="text-sm text-green-700 mt-1">Total Students</p>
                </div>
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="flex justify-between items-center mb-6">
            <a href="{{ route('student.exams') }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Exams
            </a>
            @if(isset($result) && $result)
            <a href="{{ route('student.results.mark-sheet', $result->id) }}"
               class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-lg hover:shadow-xl transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download PDF
            </a>
            @endif
        </div>

        <!-- Question-by-Question Breakdown -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-bold text-gray-900">Question-by-Question Breakdown</h2>
                <p class="text-sm text-gray-600 mt-1">Detailed review of your answers</p>
            </div>
            
            <div class="divide-y divide-gray-200">
                @foreach($questions as $index => $question)
                <div class="p-6 hover:bg-gray-50 transition-colors duration-150">
                    <!-- Question Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <span class="flex-shrink-0 bg-indigo-100 text-indigo-700 px-4 py-2 rounded-lg text-sm font-bold">
                                Question {{ $index + 1 }}
                            </span>
                            @if($question['type'] === 'mcq')
                                <span class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-medium">MCQ</span>
                            @elseif($question['type'] === 'cq')
                                <span class="text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded-full font-medium">Creative</span>
                            @endif
                        </div>
                        @if($question['is_correct'] !== null)
                            <span class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-bold {{ $question['is_correct'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                @if($question['is_correct'])
                                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Correct
                                @else
                                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Incorrect
                                @endif
                            </span>
                        @endif
                    </div>
                    
                    <!-- Question Text -->
                    <div class="mb-4">
                        <p class="text-lg text-gray-900 font-medium leading-relaxed">{{ $question['question_text'] }}</p>
                        <p class="text-sm text-gray-500 mt-1">Marks: {{ $question['marks'] }}</p>
                    </div>
                    
                    @if($question['type'] === 'mcq')
                        <!-- MCQ Options -->
                        <div class="space-y-2 ml-4">
                            @if(is_array($question['options']))
                                @foreach($question['options'] as $optionKey => $optionValue)
                                    @php
                                        $isCorrectOption = $optionKey === $question['correct_answer'];
                                        $isStudentAnswer = $optionKey === $question['student_answer'];
                                        $isWrongAnswer = $isStudentAnswer && !$isCorrectOption;
                                    @endphp
                                    <div class="flex items-start p-4 rounded-lg border-2 transition-all duration-150
                                        @if($isCorrectOption) 
                                            bg-green-50 border-green-300
                                        @elseif($isWrongAnswer) 
                                            bg-red-50 border-red-300
                                        @else 
                                            bg-gray-50 border-gray-200
                                        @endif">
                                        <span class="flex-shrink-0 font-bold mr-3 text-lg
                                            @if($isCorrectOption) text-green-700
                                            @elseif($isWrongAnswer) text-red-700
                                            @else text-gray-600
                                            @endif">
                                            {{ $optionKey }}.
                                        </span>
                                        <span class="flex-1
                                            @if($isCorrectOption) text-green-900 font-medium
                                            @elseif($isWrongAnswer) text-red-900
                                            @else text-gray-700
                                            @endif">
                                            {{ $optionValue }}
                                        </span>
                                        <div class="flex-shrink-0 ml-3">
                                            @if($isCorrectOption)
                                                <span class="inline-flex items-center text-sm font-semibold text-green-700">
                                                    <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Correct Answer
                                                </span>
                                            @endif
                                            @if($isStudentAnswer && !$isCorrectOption)
                                                <span class="inline-flex items-center text-sm font-semibold text-red-700">
                                                    <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Your Answer
                                                </span>
                                            @elseif($isStudentAnswer && $isCorrectOption)
                                                <span class="inline-flex items-center text-sm font-semibold text-green-700">
                                                    Your Answer
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @elseif($question['type'] === 'cq')
                        <!-- CQ Answer -->
                        <div class="ml-4 space-y-4">
                            <!-- Student's Text Answer -->
                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
                                <p class="text-sm font-semibold text-blue-800 mb-2">Your Answer:</p>
                                <div class="text-gray-800 prose prose-sm max-w-none">
                                    @if($question['student_answer'])
                                        {!! nl2br(e($question['student_answer'])) !!}
                                    @else
                                        <p class="text-gray-500 italic">No answer provided</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Screenshots if available -->
                            @if(isset($attempt->screenshots[$question['id']]) && !empty($attempt->screenshots[$question['id']]))
                                <div class="bg-gray-50 border-l-4 border-gray-400 p-4 rounded-r-lg">
                                    <p class="text-sm font-semibold text-gray-800 mb-3">Submitted Screenshots:</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @if(is_array($attempt->screenshots[$question['id']]))
                                            @foreach($attempt->screenshots[$question['id']] as $screenshot)
                                                <div class="relative group">
                                                    <img src="{{ media_url($screenshot) }}" 
                                                         alt="Screenshot for question {{ $index + 1 }}" 
                                                         class="w-full h-48 object-cover rounded-lg border-2 border-gray-300 shadow-sm hover:shadow-lg transition-shadow duration-200">
                                                    <a href="{{ media_url($screenshot) }}" 
                                                       target="_blank" rel="noopener"
                                                       class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 rounded-lg">
                                                        <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                                        </svg>
                                                    </a>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="relative group">
                                                <img src="{{ media_url($attempt->screenshots[$question['id']]) }}" 
                                                     alt="Screenshot for question {{ $index + 1 }}" 
                                                     class="w-full h-48 object-cover rounded-lg border-2 border-gray-300 shadow-sm hover:shadow-lg transition-shadow duration-200">
                                                <a href="{{ media_url($attempt->screenshots[$question['id']]) }}" 
                                                   target="_blank" rel="noopener"
                                                   class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 rounded-lg">
                                                    <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                                    </svg>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Correct Answer (if available) -->
                            @if($question['correct_answer'])
                                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
                                    <p class="text-sm font-semibold text-green-800 mb-2">Model Answer:</p>
                                    <div class="text-gray-800 prose prose-sm max-w-none">
                                        {!! nl2br(e($question['correct_answer'])) !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Back to Top Button -->
        <div class="mt-8 text-center">
            <button type="button" onclick="window.scrollTo({top: 0, behavior: 'smooth'});"
               class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                Back to Top
            </button>
        </div>
    </div>
</div>
@endsection
