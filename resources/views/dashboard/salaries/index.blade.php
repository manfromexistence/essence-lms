@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold tracking-tight">Teacher Salaries</h2>
        <div class="flex gap-2">
            <a href="{{ route('dashboard.salaries.report') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-chart-line mr-2"></i> Salary Report
            </a>
            <button onclick="openCreateModal()" class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm shadow-sm">
                <i class="fas fa-plus mr-2"></i> Record Payment
            </button>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
        <form action="{{ route('dashboard.salaries.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Filter by Teacher</label>
                <x-ui.select name="teacher_id" :selected="(string) request('teacher_id')">
                    <option value="">All Teachers</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                            {{ $teacher->user->name }}
                        </option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Filter by Month</label>
                <input type="month" name="month" value="{{ request('month') }}" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2.5 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm shadow-sm flex items-center justify-center">
                    <i class="fas fa-filter mr-2"></i> Filter Records
                </button>
            </div>
            @if(request('teacher_id') || request('month'))
            <div class="flex items-end">
                <a href="{{ route('dashboard.salaries.index') }}" class="w-full px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm text-center shadow-sm flex items-center justify-center">
                    <i class="fas fa-times mr-2"></i> Clear Filters
                </a>
            </div>
            @endif
        </form>
    </div>

    <!-- Data Table -->
    <x-ui.data-table 
        :headers="[
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'teacher', 'label' => 'Teacher'],
            ['key' => 'amount', 'label' => 'Amount'],
            ['key' => 'method', 'label' => 'Method'],
            ['key' => 'notes', 'label' => 'Notes'],
            ['key' => 'actions', 'label' => 'Actions'],
        ]"
        :rows="$salaries"
        :route="route('dashboard.salaries.index')"
        id="salaries-table"
    >
        @forelse($salaries as $salary)
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ $salary->payment_date->format('M d, Y') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <a href="{{ route('dashboard.salaries.history', $salary->teacher) }}" class="text-bd-green hover:text-bd-green-dark font-medium hover:underline">
                    {{ $salary->teacher->user->name }}
                </a>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                ৳{{ number_format($salary->amount, 2) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $salary->payment_method ?? 'N/A' }}
                </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">
                <div class="max-w-xs truncate">{{ $salary->notes ?? '-' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" style="min-width: 120px;">
                <div class="flex items-center justify-end gap-2" style="cursor: default;">
                    <button type="button" onclick='openEditModal(@json($salary))' class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors border border-blue-200" title="Edit Salary" style="cursor: pointer;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </button>
                    <button type="button" onclick="openDeleteModal({{ $salary->id }}, '{{ addslashes($salary->teacher->user->name) }}')" class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-red-600 hover:bg-red-50 transition-colors border border-red-200" title="Delete Salary" style="cursor: pointer;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center justify-center text-gray-500">
                    <svg class="w-12 h-12 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-sm font-medium text-gray-900 mb-1">No salary records found</h3>
                    <p class="text-sm text-gray-500">Get started by recording a salary payment.</p>
                    <button onclick="openCreateModal()" class="mt-4 inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm shadow-sm">
                        <i class="fas fa-plus mr-2"></i> Record First Payment
                    </button>
                </div>
            </td>
        </tr>
        @endforelse
    </x-ui.data-table>

    <!-- Create Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-[500px] shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Record Salary Payment</h3>
                <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form action="{{ route('dashboard.salaries.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Teacher <span class="text-red-500">*</span></label>
                        <x-ui.select name="teacher_id" required>
                            <option value="">Select Teacher</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('teacher_id')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Amount <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 font-medium">৳</span>
                            <input type="number" name="amount" min="0" step="0.01" required class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background pl-10 pr-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        </div>
                        @error('amount')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <x-ui.date-picker 
                            name="payment_date" 
                            label="Payment Date" 
                            :value="date('Y-m-d')" 
                            required 
                        />
                        @error('payment_date')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                        <x-ui.select name="payment_method">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Mobile Banking">Mobile Banking</option>
                        </x-ui.select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 resize-none" placeholder="Add any additional notes..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark">Save Payment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-[500px] shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Edit Salary Payment</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Teacher <span class="text-red-500">*</span></label>
                        <x-ui.select name="teacher_id" id="edit_teacher_id" required>
                            <option value="">Select Teacher</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Amount <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 font-medium">৳</span>
                            <input type="number" id="edit_amount" name="amount" min="0" step="0.01" required class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background pl-10 pr-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        </div>
                    </div>
                    <div class="space-y-2">
                        <x-ui.date-picker 
                            name="payment_date" 
                            id="edit_payment_date"
                            label="Payment Date" 
                            required 
                        />
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                        <x-ui.select name="payment_method" id="edit_payment_method">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Mobile Banking">Mobile Banking</option>
                        </x-ui.select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="edit_notes" name="notes" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 resize-none" placeholder="Add any additional notes..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Delete Salary Record</h3>
                <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <p class="text-gray-600 mb-4">Are you sure you want to delete the salary record for "<span id="delete_teacher_name" class="font-semibold"></span>"?</p>
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }

        function openEditModal(salary) {
            // Set custom select values (native select + dropdown display)
            if (typeof selectOption === 'function') {
                selectOption('edit_teacher_id', salary.teacher_id, null);
                selectOption('edit_payment_method', salary.payment_method || 'Cash', null);
            } else {
                document.getElementById('edit_teacher_id').value = salary.teacher_id;
                document.getElementById('edit_payment_method').value = salary.payment_method || 'Cash';
            }
            document.getElementById('edit_amount').value = salary.amount;
            
            // Set date picker value
            const editDateInput = document.querySelector('#edit_payment_date');
            if (editDateInput) {
                editDateInput.value = salary.payment_date;
                // Trigger display update
                const displayEl = editDateInput.closest('.custom-date-picker').querySelector('[id$="-display"]');
                if (displayEl && salary.payment_date) {
                    const date = new Date(salary.payment_date);
                    displayEl.textContent = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                    displayEl.classList.remove('text-gray-500');
                    displayEl.classList.add('text-gray-900');
                }
            }
            
            document.getElementById('edit_notes').value = salary.notes || '';
            document.getElementById('editForm').action = `/dashboard/salaries/${salary.id}`;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function openDeleteModal(id, teacherName) {
            document.getElementById('delete_teacher_name').textContent = teacherName;
            document.getElementById('deleteForm').action = `/dashboard/salaries/${id}`;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeCreateModal();
                closeEditModal();
                closeDeleteModal();
            }
        });
    </script>
</div>
@endsection
