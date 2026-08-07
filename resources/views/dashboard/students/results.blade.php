@extends('layouts.admin')

@section('title', 'Exam Results')
@section('page-title', 'পরীক্ষার ফলাফল')
@section('page-description', 'Track and manage student mark sheets and results')

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
                    <p class="text-sm font-medium text-gray-600">Total Results</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_results'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Average Score</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['average_score'], 1) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Highest Score</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['highest_score'], 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Exams</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_exams'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="{{ route('dashboard.students.results') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Student</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                           placeholder="Search by name..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-bd-green focus:border-transparent">
                </div>

                <div>
                    <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                    <x-ui.select name="student_id" id="student_id" :selected="(string) request('student_id')">
                        <option value="">All Students</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->user->name ?? 'N/A' }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div>
                    <label for="exam_id" class="block text-sm font-medium text-gray-700 mb-1">Exam</label>
                    <x-ui.select name="exam_id" id="exam_id" :selected="(string) request('exam_id')">
                        <option value="">All Exams</option>
                        @foreach($exams as $exam)
                            <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>
                                {{ $exam->title }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div>
                    <label for="grade" class="block text-sm font-medium text-gray-700 mb-1">Grade</label>
                    <x-ui.select name="grade" id="grade" :selected="(string) request('grade')">
                        <option value="">All Grades</option>
                        <option value="A+" {{ request('grade') == 'A+' ? 'selected' : '' }}>A+</option>
                        <option value="A" {{ request('grade') == 'A' ? 'selected' : '' }}>A</option>
                        <option value="B+" {{ request('grade') == 'B+' ? 'selected' : '' }}>B+</option>
                        <option value="B" {{ request('grade') == 'B' ? 'selected' : '' }}>B</option>
                        <option value="C" {{ request('grade') == 'C' ? 'selected' : '' }}>C</option>
                        <option value="D" {{ request('grade') == 'D' ? 'selected' : '' }}>D</option>
                        <option value="F" {{ request('grade') == 'F' ? 'selected' : '' }}>F</option>
                    </x-ui.select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <x-ui.select name="status" id="status" :selected="(string) request('status')">
                        <option value="">All Status</option>
                        <option value="passed" {{ request('status') == 'passed' ? 'selected' : '' }}>Passed</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </x-ui.select>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Filter
                </button>
                <a href="{{ route('dashboard.students.results') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Results Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h2 class="text-lg font-semibold text-gray-900">Exam Results</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $results->total() ?? $results->count() }} Total
                </span>
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'student', 'label' => 'Student'],
                ['key' => 'exam', 'label' => 'Exam'],
                ['key' => 'subject', 'label' => 'Subject'],
                ['key' => 'marks', 'label' => 'Marks'],
                ['key' => 'percentage', 'label' => 'Percentage'],
                ['key' => 'grade', 'label' => 'Grade'],
                ['key' => 'rank', 'label' => 'Rank'],
                ['key' => 'date', 'label' => 'Date'],
            ];
        @endphp

        <x-ui.data-table id="results-table" :headers="$headers" :rows="$results" :route="route('dashboard.students.results')">
            @forelse($results as $result)
                <tr class="hover:bg-gray-50 transition-colors">
                    <!-- Student -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="shrink-0 w-10 h-10">
                                @if ($result->student->profile_image)
                                    <img class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm"
                                        src="{{ Str::startsWith($result->student->profile_image, 'http') ? $result->student->profile_image : asset('storage/' . $result->student->profile_image) }}"
                                        alt="{{ $result->student->user->name ?? 'Student' }}">
                                @else
                                    <div class="w-10 h-10 bg-bd-green rounded-full flex items-center justify-center text-white font-bold shadow-sm">
                                        {{ strtoupper(substr($result->student->user->name ?? 'S', 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">{{ $result->student->user->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">ID: STU-{{ str_pad($result->student_id, 5, '0', STR_PAD_LEFT) }}</div>
                                @if($result->student->batch)
                                    <div class="text-xs text-gray-500">{{ $result->student->batch->name }}</div>
                                @endif
                            </div>
                        </div>
                    </td>

                    <!-- Exam -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($result->exam)
                            <div class="text-sm font-medium text-gray-900">{{ $result->exam->title }}</div>
                            <div class="text-xs text-gray-500">{{ $result->exam->type ?? 'N/A' }}</div>
                        @else
                            <span class="text-sm text-gray-500">N/A</span>
                        @endif
                    </td>

                    <!-- Subject -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $result->subject_name ?? 'General' }}
                        </span>
                    </td>

                    <!-- Marks -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-900">
                            {{ $result->obtained_marks ?? $result->marks ?? 0 }} / {{ $result->total_marks ?? 100 }}
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                            @php
                                $percentage = $result->total_marks > 0 ? (($result->obtained_marks ?? $result->marks ?? 0) / $result->total_marks) * 100 : 0;
                            @endphp
                            <div class="bg-bd-green h-1.5 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </td>

                    <!-- Percentage -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            {{ number_format($percentage, 1) }}%
                        </div>
                    </td>

                    <!-- Grade -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $gradeColors = [
                                'A+' => 'bg-green-100 text-green-800',
                                'A' => 'bg-blue-100 text-blue-800',
                                'B+' => 'bg-cyan-100 text-cyan-800',
                                'B' => 'bg-indigo-100 text-indigo-800',
                                'C' => 'bg-yellow-100 text-yellow-800',
                                'D' => 'bg-orange-100 text-orange-800',
                                'F' => 'bg-red-100 text-red-800',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold {{ $gradeColors[$result->grade] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $result->grade ?? 'N/A' }}
                        </span>
                    </td>

                    <!-- Rank -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($result->rank)
                            <div class="flex items-center">
                                @if($result->rank == 1)
                                    <svg class="w-5 h-5 text-yellow-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endif
                                <span class="text-sm font-medium text-gray-900">#{{ $result->rank }}</span>
                            </div>
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </td>

                    <!-- Date -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $result->created_at->format('M d, Y') }}
                        <div class="text-xs text-gray-400">{{ $result->created_at->format('h:i A') }}</div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-sm font-medium text-gray-900">No exam results found</h3>
                            <p class="mt-1 text-sm text-gray-500">Results will appear here once exams are completed.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-ui.data-table>
    </div>
@endsection