@extends('layouts.admin')

@section('title', 'Class Attendance')
@section('page-title', 'ক্লাস উপস্থিতি')
@section('page-description', 'Track attendance per class session')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-md">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Attendance Management</h2>
                    <p class="text-sm text-gray-500 mt-1">Track and manage student attendance for all class sessions</p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-64">
                        <x-ui.select id="batchFilter" name="batch_id" onchange="filterAttendance()">
                            <option value="">All Batches</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}">{{ $batch->name }} ({{ $batch->course->name ?? 'No course' }})</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <button onclick="openAttendanceModal()"
                       class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        Take Attendance
                    </button>
                </div>
            </div>
        </div>

        <!-- Attendance Overview -->
        @if($batches->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($batches->take(4) as $batch)
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 truncate">{{ $batch->name }}</h3>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                         {{ $batch->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($batch->status) }}
                            </span>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Total Students</span>
                                <span class="font-medium">{{ $batch->students->count() }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Classes Held</span>
                                <span class="font-medium">{{ $batch->attendances->count() }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Avg Attendance</span>
                                <span class="font-medium">
                                    @if($batch->attendances->count() > 0)
                                        {{ number_format($batch->attendances->avg('present_count') / max($batch->students->count(), 1) * 100, 1) }}%
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('dashboard.batches.show', $batch) }}#attendance"
                               class="w-full inline-flex items-center justify-center px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                                View Details
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Recent Attendance Records -->
            <div class="bg-white rounded-xl shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Attendance Records</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Present</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php
                                $recentAttendance = collect();
                                foreach($batches as $batch) {
                                    // Group attendances by date for this batch
                                    $attendancesByDate = $batch->attendances->groupBy('date');
                                    foreach($attendancesByDate as $date => $attendances) {
                                        $presentCount = $attendances->where('status', 'present')->count();
                                        $totalStudents = $batch->students->count();
                                        $recentAttendance->push([
                                            'batch' => $batch,
                                            'date' => \Carbon\Carbon::parse($date),
                                            'present_count' => $presentCount,
                                            'total_students' => $totalStudents,
                                            'percentage' => $totalStudents > 0 ? ($presentCount / $totalStudents) * 100 : 0
                                        ]);
                                    }
                                }
                                $recentAttendance = $recentAttendance->sortByDesc('date')->take(10);
                            @endphp

                            @forelse($recentAttendance as $record)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $record['date']->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $record['batch']->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $record['batch']->course->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $record['present_count'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $record['total_students'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                     {{ $record['percentage'] >= 80 ? 'bg-green-100 text-green-800' :
                                                        ($record['percentage'] >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ number_format($record['percentage'], 1) }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('dashboard.batches.show', $record['batch']) }}#attendance" class="text-indigo-600 hover:text-indigo-900">View Details</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="w-12 h-12 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                            </svg>
                                        </div>
                                        <h4 class="text-gray-500 font-medium">No attendance records yet</h4>
                                        <p class="text-gray-400 text-sm mt-1">Start taking attendance for your classes</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No Batches Available</h3>
                    <p class="text-gray-500 max-w-sm mx-auto mb-6">Create batches first to start tracking attendance.</p>
                    <a href="{{ route('dashboard.batches.create') }}"
                       class="inline-flex items-center px-6 py-3 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create First Batch
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Take Attendance Modal -->
    <div id="attendanceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Take Attendance</h3>
                    <button onclick="closeAttendanceModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="attendanceForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="space-y-2">
                            <x-ui.select id="attendance_batch_id" name="batch_id" label="Batch" onchange="loadStudents()">
                                <option value="">Select Batch</option>
                                @foreach($batches as $batch)
                                    <option value="{{ $batch->id }}">{{ $batch->name }} ({{ $batch->course->name ?? 'No course' }})</option>
                                @endforeach
                            </x-ui.select>
                        </div>

                        <div class="space-y-2">
                            <x-ui.date-picker id="attendance_date" name="date" label="Date" value="{{ date('Y-m-d') }}" />
                        </div>
                    </div>

                    <div id="studentsList" class="hidden">
                        <h4 class="text-md font-medium text-gray-900 mb-4">Mark Attendance</h4>
                        <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                            <div id="studentsContainer" class="divide-y divide-gray-200">
                                <!-- Students will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeAttendanceModal()"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" id="submitAttendance" disabled
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Save Attendance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAttendanceModal() {
            document.getElementById('attendanceModal').classList.remove('hidden');
        }

        function closeAttendanceModal() {
            document.getElementById('attendanceModal').classList.add('hidden');
            document.getElementById('attendanceForm').reset();
            document.getElementById('studentsList').classList.add('hidden');
            document.getElementById('submitAttendance').disabled = true;
        }

        function loadStudents() {
            const batchId = document.getElementById('attendance_batch_id').value;
            if (!batchId) {
                document.getElementById('studentsList').classList.add('hidden');
                return;
            }
            const url = new URL(window.location.href);
            url.searchParams.set('batch_id', batchId);
            window.location.href = url.toString();
        }

        function filterAttendance() {
            const batchId = document.getElementById('batchFilter').value;
            const url = new URL(window.location.href);
            if (batchId) { url.searchParams.set('batch_id', batchId); } else { url.searchParams.delete('batch_id'); }
            window.location.href = url.toString();
        }

        // Close modal when clicking outside
        document.getElementById('attendanceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAttendanceModal();
            }
        });
    </script>
@endsection