@extends('layouts.admin')

@section('title', 'Batch & Class Assignment')
@section('page-title', 'ব্যাচ ও ক্লাস নিয়োগ')
@section('page-description', 'Assign students to batches and classes')

@section('content')
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Batch Assignments</h2>
            <button onclick="openBulkAssignModal()"
                class="px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors text-sm font-medium">
                Bulk Assign
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-bd-green focus:ring-bd-green">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Batch</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($students as $student)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" 
                                    class="student-checkbox rounded border-gray-300 text-bd-green focus:ring-bd-green">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="shrink-0 w-8 h-8">
                                        @if ($student->profile_image)
                                            <img class="w-8 h-8 rounded-full object-cover border border-gray-100"
                                                src="{{ Str::startsWith($student->profile_image, 'http') ? $student->profile_image : asset('storage/' . $student->profile_image) }}"
                                                alt="{{ $student->user->name }}">
                                        @else
                                            <div class="w-8 h-8 bg-bd-green rounded-full flex items-center justify-center text-white text-xs font-bold">
                                                {{ strtoupper(substr($student->user->name ?? 'S', 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $student->user->name ?? 'N/A' }}</div>
                                        <div class="text-xs text-gray-500">ID: STU-{{ str_pad($student->id, 5, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    {{ $student->batch->name ?? 'Unassigned' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $admissionStatus = $student->admission_status ?? ($student->batch_id ? 'approved' : 'pending');
                                    $statusBadge = [
                                        'approved' => ['bg-green-100 text-green-800', 'Admitted'],
                                        'rejected' => ['bg-red-100 text-red-800', 'Rejected'],
                                        'pending' => ['bg-yellow-100 text-yellow-800', 'Pending'],
                                    ][$admissionStatus] ?? ['bg-gray-100 text-gray-800', ucfirst($admissionStatus)];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusBadge[0] }}">
                                    {{ $statusBadge[1] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="openChangeBatchModal({{ $student->id }}, '{{ $student->user->name ?? 'N/A' }}', {{ $student->batch_id ?? 'null' }})" 
                                    class="text-bd-green hover:text-bd-green-dark">Change Batch</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $students->links() }}
            </div>
        @endif
    </div>

    <!-- Change Batch Modal -->
    <div id="changeBatchModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Change Batch Assignment</h3>
                <form id="changeBatchForm" method="POST" action="{{ route('dashboard.students.batch-assignment.update') }}">
                    @csrf
                    <input type="hidden" id="studentId" name="student_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Student</label>
                        <p id="studentName" class="text-sm text-gray-900 bg-gray-50 p-2 rounded"></p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="batchSelect" class="block text-sm font-medium text-gray-700 mb-2">Select Batch</label>
                        <select id="batchSelect" name="batch_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-bd-green focus:border-transparent">
                            <option value="">Unassigned</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeBatchModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="px-4 py-2 bg-bd-green text-white rounded-md hover:bg-bd-green-dark transition-colors">
                            Update Batch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Assign Modal -->
    <div id="bulkAssignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Bulk Batch Assignment</h3>
                <form id="bulkAssignForm" method="POST" action="{{ route('dashboard.students.batch-assignment.bulk') }}">
                    @csrf
                    <div id="selectedStudentsContainer"></div>
                    
                    <div class="mb-4">
                        <label for="bulkBatchSelect" class="block text-sm font-medium text-gray-700 mb-2">Select Batch</label>
                        <select id="bulkBatchSelect" name="batch_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-bd-green focus:border-transparent">
                            <option value="">Unassigned</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeBulkModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="px-4 py-2 bg-bd-green text-white rounded-md hover:bg-bd-green-dark transition-colors">
                            Assign Batch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Select All functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Individual checkbox change
        document.querySelectorAll('.student-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const selectAll = document.getElementById('selectAll');
                const allCheckboxes = document.querySelectorAll('.student-checkbox');
                const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
                
                selectAll.checked = allCheckboxes.length === checkedBoxes.length;
                selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < allCheckboxes.length;
            });
        });

        function openChangeBatchModal(studentId, studentName, currentBatchId) {
            document.getElementById('studentId').value = studentId;
            document.getElementById('studentName').textContent = studentName;
            document.getElementById('batchSelect').value = currentBatchId || '';
            document.getElementById('changeBatchModal').classList.remove('hidden');
        }

        function closeBatchModal() {
            document.getElementById('changeBatchModal').classList.add('hidden');
        }

        function openBulkAssignModal() {
            const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
            
            if (checkedBoxes.length === 0) {
                alert('Please select at least one student.');
                return;
            }

            const container = document.getElementById('selectedStudentsContainer');
            container.innerHTML = '';
            
            checkedBoxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'student_ids[]';
                input.value = checkbox.value;
                container.appendChild(input);
            });

            document.getElementById('bulkAssignModal').classList.remove('hidden');
        }

        function closeBulkModal() {
            document.getElementById('bulkAssignModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const changeBatchModal = document.getElementById('changeBatchModal');
            const bulkAssignModal = document.getElementById('bulkAssignModal');
            
            if (event.target === changeBatchModal) {
                closeBatchModal();
            }
            if (event.target === bulkAssignModal) {
                closeBulkModal();
            }
        });
    </script>
@endsection