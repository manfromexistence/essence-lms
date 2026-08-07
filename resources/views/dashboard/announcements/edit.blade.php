@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto">
    <x-ui.card>
        <x-ui.card-header>
            <x-ui.card-title>Edit Announcement</x-ui.card-title>
        </x-ui.card-header>
        <x-ui.card-content>
            {{-- Global Validation Error Display --}}
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 mr-2 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <strong class="font-semibold">Please fix the following errors:</strong>
                    </div>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('dashboard.announcements.update', $announcement) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div class="space-y-2">
                    <x-ui.label for="title">Title</x-ui.label>
                    <x-ui.input type="text" name="title" id="title" value="{{ old('title', $announcement->title) }}" required />
                    @error('title')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <x-ui.label for="content">Content</x-ui.label>
                    <x-ui.textarea name="content" id="content" rows="6" required>{{ old('content', $announcement->content) }}</x-ui.textarea>
                    @error('content')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <x-ui.select name="target_type" id="target_type" label="Target Audience" :selected="old('target_type', $announcement->target_type)" required>
                            <option value="all">Everyone</option>
                            <option value="batch">Specific Batch</option>
                            <option value="course">Specific Course</option>
                        </x-ui.select>
                        @error('target_type')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2" id="target_id_container" style="{{ old('target_type', $announcement->target_type) == 'all' ? 'display: none;' : '' }}">
                        <x-ui.select name="target_id" id="target_id" label="Select Target">
                            <option value="">-- Select --</option>
                        </x-ui.select>
                        @error('target_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <x-ui.select name="priority" label="Priority" :selected="old('priority', $announcement->priority)" required>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </x-ui.select>
                        @error('priority')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <x-ui.date-picker name="starts_at" id="starts_at" label="Start Date (Optional)" value="{{ old('starts_at', $announcement->starts_at ? $announcement->starts_at->format('Y-m-d') : '') }}" />
                        @error('starts_at')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <x-ui.date-picker name="expires_at" id="expires_at" label="Expiry Date (Optional)" value="{{ old('expires_at', $announcement->expires_at ? $announcement->expires_at->format('Y-m-d') : '') }}" />
                        @error('expires_at')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary" {{ old('is_active', $announcement->is_active) ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Active</label>
                </div>

                <div class="flex justify-end gap-4 pt-4">
                    <x-ui.button type="button" variant="outline" as="a" href="{{ route('dashboard.announcements.index') }}">Cancel</x-ui.button>
                    <x-ui.button type="submit">Update Announcement</x-ui.button>
                </div>
            </form>
        </x-ui.card-content>
    </x-ui.card>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const batches = @json($batches);
        const courses = @json($courses);
        const targetTypeSelect = document.getElementById('target_type');
        const targetIdContainer = document.getElementById('target_id_container'); 
        const targetIdSelect = document.getElementById('target_id'); 
        const currentTargetId = "{{ old('target_id', $announcement->target_id) }}";

        function updateTargetList(type) {
            targetIdSelect.innerHTML = '<option value="">-- Select --</option>';
            if (type === 'all') {
                targetIdContainer.style.display = 'none';
            } else {
                targetIdContainer.style.display = 'block';
                const data = type === 'batch' ? batches : courses;
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.text = item.name + (type === 'batch' && item.course ? ` (${item.course.name})` : '');
                    if (item.id == currentTargetId) option.selected = true;
                    targetIdSelect.appendChild(option);
                });
            }

            // Re-render the custom select dropdown so new options appear
            if (typeof renderOptions === 'function') {
                renderOptions('target_id');
                if (currentTargetId) {
                    const native = document.getElementById('target_id');
                    if (native && typeof selectOption === 'function') {
                        const opt = Array.from(native.options).find(o => String(o.value) === String(currentTargetId));
                        if (opt) selectOption('target_id', opt.value, opt.text);
                    }
                }
            }
        }

        targetTypeSelect.addEventListener('change', function() {
            updateTargetList(this.value);
        });
        
        // Initial load
        updateTargetList(targetTypeSelect.value);
    });
</script>
@endpush
@endsection
