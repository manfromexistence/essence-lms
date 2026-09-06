@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold tracking-tight">Announcements</h2>
        <x-ui.button as="a" href="{{ route('dashboard.announcements.create') }}">
            <i class="fas fa-plus mr-2"></i> Create Announcement
        </x-ui.button>
    </div>

    <x-ui.card>
        <x-ui.table>
            <x-ui.table-header>
                <x-ui.table-row>
                    <x-ui.table-head>Title</x-ui.table-head>
                    <x-ui.table-head>Target</x-ui.table-head>
                    <x-ui.table-head>Priority</x-ui.table-head>
                    <x-ui.table-head>Starts At</x-ui.table-head>
                    <x-ui.table-head>Expires At</x-ui.table-head>
                    <x-ui.table-head>Status</x-ui.table-head>
                    <x-ui.table-head class="text-right">Actions</x-ui.table-head>
                </x-ui.table-row>
            </x-ui.table-header>
            <x-ui.table-body>
                @forelse($announcements as $announcement)
                <x-ui.table-row>
                    <x-ui.table-cell class="font-medium">{{ $announcement->title }}</x-ui.table-cell>
                    <x-ui.table-cell>{{ $announcement->target_name }}</x-ui.table-cell>
                    <x-ui.table-cell>
                        <x-ui.badge style="background-color: {{ $announcement->priority_color }}; color: white;">
                            {{ ucfirst($announcement->priority) }}
                        </x-ui.badge>
                    </x-ui.table-cell>
                    <x-ui.table-cell>{{ $announcement->starts_at ? $announcement->starts_at->format('M d, Y') : 'Immediately' }}</x-ui.table-cell>
                    <x-ui.table-cell>{{ $announcement->expires_at ? $announcement->expires_at->format('M d, Y') : 'Never' }}</x-ui.table-cell>
                    <x-ui.table-cell>
                        @if($announcement->is_active)
                            <x-ui.badge class="bg-emerald-500 hover:bg-emerald-600">Active</x-ui.badge>
                        @else
                            <x-ui.badge variant="secondary">Inactive</x-ui.badge>
                        @endif
                    </x-ui.table-cell>
                    <x-ui.table-cell class="text-right">
                        <div class="flex justify-end gap-2">
                            <x-ui.button variant="outline" size="sm" type="button" onclick="openEditModal({{ $announcement->id }})">
                                Edit
                            </x-ui.button>
                            <form action="{{ route('dashboard.announcements.destroy', $announcement) }}" method="POST" class="inline-block" onsubmit="return confirmDelete(this, 'Are you sure you want to delete this announcement?')">
                                @csrf
                                @method('DELETE')
                                <x-ui.button variant="destructive" size="sm" type="submit">
                                    <i class="fas fa-trash"></i>
                                </x-ui.button>
                            </form>
                        </div>
                    </x-ui.table-cell>
                </x-ui.table-row>
                @empty
                <x-ui.table-row>
                    <x-ui.table-cell colspan="7" class="text-center py-8 text-muted-foreground">
                        No announcements found.
                    </x-ui.table-cell>
                </x-ui.table-row>
                @endforelse
            </x-ui.table-body>
        </x-ui.table>
        <div class="p-4 border-t">
            {{ $announcements->links() }}
        </div>
    </x-ui.card>
</div>

<!-- Edit Announcement Modal -->
<div id="editAnnouncementModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white">
            <h3 class="text-xl font-semibold text-gray-900">Edit Announcement</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="editAnnouncementForm" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')
            
            <div class="space-y-2">
                <label for="edit_title" class="block text-sm font-semibold text-gray-700">Title *</label>
                <input type="text" name="title" id="edit_title" required class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent transition-all outline-none">
            </div>

            <div class="space-y-2">
                <label for="edit_content" class="block text-sm font-semibold text-gray-700">Content *</label>
                <textarea name="content" id="edit_content" rows="6" required class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent transition-all outline-none"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label for="edit_target_type" class="block text-sm font-semibold text-gray-700">Target Audience *</label>
                    <select name="target_type" id="edit_target_type" required class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent transition-all outline-none">
                        <option value="all">Everyone</option>
                        <option value="batch">Specific Batch</option>
                        <option value="course">Specific Course</option>
                    </select>
                </div>
                <div class="space-y-2" id="edit_target_id_container" style="display: none;">
                    <label for="edit_target_id" class="block text-sm font-semibold text-gray-700">Select Target</label>
                    <select name="target_id" id="edit_target_id" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent transition-all outline-none">
                        <option value="">-- Select --</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-2">
                    <label for="edit_priority" class="block text-sm font-semibold text-gray-700">Priority *</label>
                    <select name="priority" id="edit_priority" required class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent transition-all outline-none">
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label for="edit_starts_at" class="block text-sm font-semibold text-gray-700">Start Date (Optional)</label>
                    <input type="date" name="starts_at" id="edit_starts_at" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent transition-all outline-none">
                </div>
                <div class="space-y-2">
                    <label for="edit_expires_at" class="block text-sm font-semibold text-gray-700">Expiry Date (Optional)</label>
                    <input type="date" name="expires_at" id="edit_expires_at" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent transition-all outline-none">
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="h-4 w-4 rounded border-gray-300 text-bd-green focus:ring-bd-green">
                <label for="edit_is_active" class="text-sm font-medium text-gray-700">Active</label>
            </div>

            <div class="flex justify-end gap-4 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeEditModal()" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2.5 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium">
                    Update Announcement
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const batches = @json($batches ?? []);
    const courses = @json($courses ?? []);
    const announcements = @json($announcements->items());

    function openEditModal(announcementId) {
        const announcement = announcements.find(a => a.id === announcementId);
        if (!announcement) {
            return;
        }

        // Set form action
        const form = document.getElementById('editAnnouncementForm');
        if (form) {
            form.action = `/dashboard/announcements/${announcementId}`;
        }

        // Populate form fields
        const titleInput = document.getElementById('edit_title');
        const contentInput = document.getElementById('edit_content');
        const targetTypeSelect = document.getElementById('edit_target_type');
        const prioritySelect = document.getElementById('edit_priority');
        const startsAtInput = document.getElementById('edit_starts_at');
        const expiresAtInput = document.getElementById('edit_expires_at');
        const isActiveCheckbox = document.getElementById('edit_is_active');

        if (titleInput) titleInput.value = announcement.title || '';
        if (contentInput) contentInput.value = announcement.content || '';
        if (targetTypeSelect) targetTypeSelect.value = announcement.target_type || 'all';
        if (prioritySelect) prioritySelect.value = announcement.priority || 'normal';
        if (startsAtInput) startsAtInput.value = announcement.starts_at ? announcement.starts_at.split('T')[0] : '';
        if (expiresAtInput) expiresAtInput.value = announcement.expires_at ? announcement.expires_at.split('T')[0] : '';
        if (isActiveCheckbox) isActiveCheckbox.checked = announcement.is_active ? true : false;

        // Update target list and show modal
        updateEditTargetList(announcement.target_type, announcement.target_id);
        
        const modal = document.getElementById('editAnnouncementModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    function closeEditModal() {
        const modal = document.getElementById('editAnnouncementModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function updateEditTargetList(type, selectedId = null) {
        const targetIdContainer = document.getElementById('edit_target_id_container');
        const targetIdSelect = document.getElementById('edit_target_id');
        
        if (!targetIdSelect) return;
        
        targetIdSelect.innerHTML = '<option value="">-- Select --</option>';
        
        if (type === 'all') {
            if (targetIdContainer) targetIdContainer.style.display = 'none';
        } else {
            if (targetIdContainer) targetIdContainer.style.display = 'block';
            const data = type === 'batch' ? batches : courses;
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.text = item.name + (type === 'batch' && item.course ? ` (${item.course.name})` : '');
                if (item.id == selectedId) option.selected = true;
                targetIdSelect.appendChild(option);
            });
        }
    }

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        // Listen for target type changes
        const editTargetType = document.getElementById('edit_target_type');
        if (editTargetType) {
            editTargetType.addEventListener('change', function() {
                updateEditTargetList(this.value);
            });
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
            }
        });

        // Close modal on background click
        const editModal = document.getElementById('editAnnouncementModal');
        if (editModal) {
            editModal.addEventListener('click', function(e) {
                if (e.target === this) closeEditModal();
            });
        }
    });
</script>
@endpush
@endsection
