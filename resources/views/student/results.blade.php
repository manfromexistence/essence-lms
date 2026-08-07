@extends('layouts.admin')

@section('title', 'My Results')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Exam Results</h2>
            <p class="text-sm text-muted-foreground">{{ $student ? 'View your exam performance' : 'View all exam results' }}</p>
        </div>
    </div>

    <!-- Filters -->
    <x-ui.card>
        <x-ui.card-content class="pt-6">
            <form action="{{ route('student.results') }}" method="GET" class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Exam Type</label>
                    <select name="exam_type" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Types</option>
                        <option value="mcq" {{ ($filters['exam_type'] ?? '') === 'mcq' ? 'selected' : '' }}>MCQ</option>
                        <option value="cq" {{ ($filters['exam_type'] ?? '') === 'cq' ? 'selected' : '' }}>CQ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" 
                           class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" 
                           class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex items-end">
                    <x-ui.button type="submit">Filter</x-ui.button>
                </div>
            </form>
        </x-ui.card-content>
    </x-ui.card>

    <!-- Performance Chart -->
    @if(count($trends['scores']) > 1)
    <x-ui.card>
        <x-ui.card-header>
            <h3 class="text-lg font-semibold">Performance Trend</h3>
        </x-ui.card-header>
        <x-ui.card-content>
            <canvas id="performance-chart" height="100"></canvas>
        </x-ui.card-content>
    </x-ui.card>
    @endif

    <!-- Results Table -->
    <x-ui.card>
        <x-ui.card-content class="p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @if(!$student)
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            @endif
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($results as $result)
                        <tr class="hover:bg-gray-50">
                            @if(!$student)
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $result->student?->user?->name ?? 'N/A' }}
                            </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $result->exam?->title ?? 'Exam' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full {{ $result->exam?->type === 'mcq' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ strtoupper($result->exam?->type ?? 'N/A') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $result->marks }}/{{ $result->total_marks }}</div>
                                <div class="text-xs text-gray-500">{{ $result->percentage }}%</div>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                @if($student)
                                <a href="{{ route('student.exam-result', $result) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                <a href="{{ route('student.results.mark-sheet', $result) }}" class="text-green-600 hover:text-green-900">Download</a>
                                @else
                                <span class="text-gray-400 text-xs">View Only</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $student ? 6 : 7 }}" class="px-6 py-12 text-center text-gray-500">
                                No results found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($results->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $results->links() }}
            </div>
            @endif
        </x-ui.card-content>
    </x-ui.card>
    
    <div class="mt-6">
        @if($student)
        <x-ui.button variant="ghost" as="a" href="{{ route('student.dashboard') }}">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </x-ui.button>
        @else
        <x-ui.button variant="ghost" as="a" href="{{ route('dashboard') }}">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </x-ui.button>
        @endif
    </div>
</div>

@if(count($trends['scores']) > 1)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('performance-chart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($trends['labels']),
            datasets: [{
                label: 'Score (%)',
                data: @json($trends['scores']),
                borderColor: 'rgb(79, 70, 229)',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
</script>
@endpush
@endif
@endsection
