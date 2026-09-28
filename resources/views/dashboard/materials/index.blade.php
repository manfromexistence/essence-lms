@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold tracking-tight">Course Materials: {{ $course->name }}</h2>
        <x-ui.button variant="secondary" as="a" href="{{ route('dashboard.courses.index') }}">
            <i class="fas fa-arrow-left mr-2"></i> Back to Courses
        </x-ui.button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        <!-- Upload Form -->
        <div class="md:col-span-4">
            <x-ui.card>
                <x-ui.card-header>
                    <x-ui.card-title>Upload New Material</x-ui.card-title>
                </x-ui.card-header>
                <x-ui.card-content>
                    <form action="{{ route('dashboard.courses.materials.store', $course) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        
                        <div class="space-y-2">
                            <x-ui.label for="title">Title</x-ui.label>
                            <x-ui.input type="text" name="title" id="title" required />
                        </div>

                        <div class="space-y-2">
                            <x-ui.select name="type" id="material_type" label="Type" required>
                                <option value="pdf">PDF Document</option>
                                <option value="video">Video</option>
                                <option value="document">Other Document</option>
                                <option value="image">Image</option>
                                <option value="link">External Link / URL</option>
                            </x-ui.select>
                        </div>

                        <div class="space-y-2" id="file_input_container">
                            <x-ui.label for="file">File</x-ui.label>
                            <input type="file" name="file" id="file" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" />
                        </div>

                        <div class="space-y-2" id="url_input_container" style="display: none;">
                            <x-ui.label for="file_path">URL / Link</x-ui.label>
                            <x-ui.input type="url" name="file_path" id="file_path" placeholder="https://..." />
                        </div>

                        <div class="space-y-2">
                            <x-ui.label for="description">Description (Optional)</x-ui.label>
                            <x-ui.textarea name="description" id="description" rows="2" />
                        </div>

                        <x-ui.button type="submit" class="w-full">Upload Material</x-ui.button>
                    </form>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <!-- Materials List -->
        <div class="md:col-span-8">
            <x-ui.card>
                <x-ui.card-header>
                    <x-ui.card-title>Existing Materials</x-ui.card-title>
                </x-ui.card-header>
                <x-ui.card-content>
                    <x-ui.alert class="mb-4 bg-blue-50 text-blue-900 border-blue-200">
                        <i class="fas fa-info-circle mr-2"></i>
                        <x-ui.alert-description>Drag and drop items to reorder them.</x-ui.alert-description>
                    </x-ui.alert>
                    
                    <div id="materialsList" class="space-y-2">
                        @forelse($materials as $material)
                        <div class="flex items-center justify-between p-3 bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow" data-id="{{ $material->id }}">
                            <div class="flex items-center gap-4">
                                <span class="text-muted-foreground cursor-move drag-handle px-1 hover:text-foreground">
                                    <i class="fas fa-grip-vertical"></i>
                                </span>
                                <div class="w-8 flex justify-center">
                                    @if($material->type == 'pdf') <i class="fas fa-file-pdf text-red-500 text-xl"></i>
                                    @elseif($material->type == 'video') <i class="fas fa-video text-blue-500 text-xl"></i>
                                    @elseif($material->type == 'link') <i class="fas fa-link text-cyan-500 text-xl"></i>
                                    @elseif($material->type == 'image') <i class="fas fa-image text-orange-500 text-xl"></i>
                                    @else <i class="fas fa-file text-gray-500 text-xl"></i>
                                    @endif
                                </div>
                                <div>
                                    <h6 class="font-medium text-sm">{{ $material->title }}</h6>
                                    <p class="text-xs text-muted-foreground">{{ ucfirst($material->type) }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                @if($material->type == 'link')
                                    <x-ui.button variant="outline" size="sm" as="a" href="{{ $material->file_path }}" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </x-ui.button>
                                @else
                                    <x-ui.button variant="outline" size="sm" as="a" href="{{ Storage::url($material->file_path) }}" target="_blank">
                                        <i class="fas fa-download"></i>
                                    </x-ui.button>
                                @endif
                                <form action="{{ route('dashboard.courses.materials.destroy', [$course, $material]) }}" method="POST" class="inline-block" onsubmit="return confirmDelete(this, 'Are you sure you want to delete this material? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button variant="outline" size="sm" type="submit" class="text-destructive hover:bg-destructive/10 border-destructive/20">
                                        <i class="fas fa-trash"></i>
                                    </x-ui.button>
                                </form>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8 text-muted-foreground bg-muted/20 rounded-lg border border-dashed">
                            No materials uploaded yet.
                        </div>
                        @endforelse
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle input based on type
        const typeSelect = document.getElementById('material_type'); // Native select inside x-ui.select
        const fileContainer = document.getElementById('file_input_container');
        const urlContainer = document.getElementById('url_input_container');

        if(typeSelect) {
            typeSelect.addEventListener('change', function() {
                const type = this.value;
                if (type === 'link') {
                    fileContainer.style.display = 'none';
                    urlContainer.style.display = 'block';
                } else {
                    fileContainer.style.display = 'block';
                    urlContainer.style.display = 'none';
                }
            });
        }

        // Drag and Drop Sortable
        var el = document.getElementById('materialsList');
        if(el) {
            var sortable = Sortable.create(el, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function (evt) {
                    var newOrder = [];
                    el.querySelectorAll('div[data-id]').forEach(function (div) {
                        newOrder.push(div.getAttribute('data-id'));
                    });

                    // Send reorder request
                    fetch("{{ route('dashboard.courses.materials.reorder', $course) }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({ order: newOrder })
                    });
                }
            });
        }
    });
</script>
@endpush
@endsection
