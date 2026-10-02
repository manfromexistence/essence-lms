@extends('layouts.admin')

@section('title', 'Academic Progress')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Academic Progress</h2>
            <p class="text-sm text-muted-foreground">Track your children's academic performance over time.</p>
        </div>
    </div>

    @if(isset($progressData) && $progressData->count() > 0)
        @foreach($progressData as $data)
            @php
                $student = $data['student'];
                $results = $data['results'];
                $averagePercentage = $data['average_percentage'];
                $totalExams = $data['total_exams'];
            @endphp
            
            <x-ui.card>
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            @if($student->photo)
                                <img src="{{ $student->photo }}" alt="{{ $student->name }}" class="w-12 h-12 rounded-full object-cover">
                            @else
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center">
                                    <span class="text-white text-lg font-bold">{{ substr($student->name, 0, 1) }}</span>
                                </div>
                            @endif
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $student->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $student->batch?->course?->name ?? 'No Course' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <x-ui.card-content class="pt-6">
                    <!-- Performance Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-2xl font-bold text-blue-600">{{ $totalExams }}</p>
                            <p class="text-sm text-gray-600">Total Exams</p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <p class="text-2xl font-bold text-purple-600">{{ number_format($averagePercentage, 2) }}%</p>
                            <p class="text-sm text-gray-600">Average Score</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <p class="text-2xl font-bold text-green-600">
                                @if($averagePercentage >= 80) A+
                                @elseif($averagePercentage >= 70) A
                                @elseif($averagePercentage >= 60) B
                                @elseif($averagePercentage >= 50) C
                                @elseif($averagePercentage >= 40) D
                                @else F
                                @endif
                            </p>
                            <p class="text-sm text-gray-600">Average Grade</p>
                        </div>
                    </div>

                    <!-- Recent Results -->
                    @if($results->count() > 0)
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Recent Exam Results</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($results as $result)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $result->exam?->name ?? 'Exam' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full {{ $result->exam?->type === 'mcq' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                                    {{ strtoupper($result->exam?->type ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $result->marks }}/{{ $result->total_marks }}
                                                <span class="text-gray-500">({{ $result->percentage }}%)</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full 
                                                    @if($result->percentage >= 80) bg-green-100 text-green-800
                                                    @elseif($result->percentage >= 60) bg-blue-100 text-blue-800
                                                    @elseif($result->percentage >= 40) bg-yellow-100 text-yellow-800
                                                    @else bg-red-100 text-red-800 @endif">
                                                    {{ $result->grade }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $result->created_at->format('M d, Y') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-gray-500 py-8">No exam results found.</p>
                    @endif
                </x-ui.card-content>
            </x-ui.card>
        @endforeach
    @else
        <x-ui.card>
            <x-ui.card-content class="pt-6">
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No progress data</h3>
                    <p class="mt-1 text-sm text-gray-500">Academic progress will appear here once available.</p>
                </div>
            </x-ui.card-content>
        </x-ui.card>
    @endif
</div>
@endsection