@extends('layouts.admin')

@section('title', 'Student Groups')
@section('page-title', 'ছাত্র গ্রুপ')
@section('page-description', 'Manage student groups and collaborative learning teams')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-md">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Student Groups Management</h2>
                    <p class="text-sm text-gray-500 mt-1">Create and manage collaborative learning groups for better student engagement</p>
                </div>
                <button onclick="openGroupModal()"
                   class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Create Group
                </button>
            </div>
        </div>

        <!-- Groups Overview -->
        @if($batches->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($batches as $batch)
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <!-- Batch Header -->
                        <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-4 py-3">
                            <h3 class="text-white font-semibold">{{ $batch->name }}</h3>
                            <p class="text-purple-100 text-sm">{{ $batch->course->name ?? 'No course' }}</p>
                        </div>

                        <!-- Groups List -->
                        <div class="p-4">
                            @if($batch->students->isNotEmpty())
                            <div class="space-y-1">
                                @foreach($batch->students->take(6) as $member)
                                <div class="flex items-center text-sm text-gray-600">
                                    <div class="w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center mr-2">
                                        <span class="text-xs font-medium text-white">{{ mb_substr($member->user->name ?? $member->name_bn ?? '?', 0, 1) }}</span>
                                    </div>
                                    {{ $member->user->name ?? $member->name_bn ?? 'Student' }}
                                </div>
                                @endforeach
                                @if($batch->students->count() > 6)
                                <p class="text-xs text-gray-500">+{{ $batch->students->count() - 6 }} more in this batch</p>
                                @endif
                            </div>
                            @else
                            <p class="text-sm text-gray-500">No students assigned to this batch yet.</p>
                            @endif

                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <button onclick="openGroupModal({{ $batch->id }})"
                                   class="w-full inline-flex items-center justify-center px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add New Group
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Group Activities Overview -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Group Activities & Projects</h3>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Group projects are managed per batch. Open a batch to organize its students.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-purple-50 text-purple-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No Batches Available</h3>
                    <p class="text-gray-500 max-w-sm mx-auto mb-6">Create batches first to organize students into groups.</p>
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

    <!-- Create Group Modal -->
    <div id="groupModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Create Student Group</h3>
                    <button onclick="closeGroupModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="groupForm">
                    <div class="mb-4 space-y-2">
                        <x-ui.select id="group_batch_id" name="batch_id" label="Batch">
                            <option value="">Select Batch</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}">{{ $batch->name }} ({{ $batch->course->name ?? 'No course' }})</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="mb-4">
                        <label for="group_name" class="block text-sm font-medium text-gray-700 mb-2">Group Name</label>
                        <input type="text" id="group_name" name="name" placeholder="e.g., Group Alpha"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <div class="mb-4">
                        <label for="group_project" class="block text-sm font-medium text-gray-700 mb-2">Project/Assignment</label>
                        <input type="text" id="group_project" name="project" placeholder="e.g., E-commerce Website"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <div class="mb-4 space-y-2">
                        <x-ui.select id="group_size" name="size" label="Group Size">
                            <option value="2">2 members</option>
                            <option value="3" selected>3 members</option>
                            <option value="4">4 members</option>
                            <option value="5">5 members</option>
                        </x-ui.select>
                    </div>

                    <div class="mb-6 space-y-2">
                        <x-ui.date-picker id="group_deadline" name="deadline" label="Deadline" />
                    </div>

                    <div class="flex items-center justify-end space-x-3">
                        <button type="button" onclick="closeGroupModal()"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            Create Group
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openGroupModal(batchId = null) {
            document.getElementById('groupModal').classList.remove('hidden');
            if (batchId) {
                document.getElementById('group_batch_id').value = batchId;
            }
        }

        function closeGroupModal() {
            document.getElementById('groupModal').classList.add('hidden');
            document.getElementById('groupForm').reset();
        }

        // Close modal when clicking outside
        document.getElementById('groupModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeGroupModal();
            }
        });
    </script>
@endsection