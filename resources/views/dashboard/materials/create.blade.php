@extends('layouts.admin')

@section('title', 'Upload Material')
@section('page-title', 'Upload Course Material')
@section('page-description', 'Add a new resource to ' . $course->name)

@section('content')
    <div class="max-w-2xl">
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Material Details</x-ui.card-title>
                <x-ui.card-description>
                    Upload a file, or choose “External Link” to point at a URL instead.
                </x-ui.card-description>
            </x-ui.card-header>

            <x-ui.card-content>
                <form action="{{ route('dashboard.courses.materials.store', $course) }}" method="POST"
                    enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    <div class="space-y-2">
                        <x-ui.label for="title">Title <span class="text-destructive">*</span></x-ui.label>
                        <x-ui.input type="text" name="title" id="title" value="{{ old('title') }}" required autofocus />
                        @error('title')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="type">Type <span class="text-destructive">*</span></x-ui.label>
                        <x-ui.select-native name="type" id="material_type" required>
                            <option value="pdf" @selected(old('type') === 'pdf')>PDF Document</option>
                            <option value="video" @selected(old('type') === 'video')>Video</option>
                            <option value="document" @selected(old('type') === 'document')>Other Document</option>
                            <option value="image" @selected(old('type') === 'image')>Image</option>
                            <option value="link" @selected(old('type') === 'link')>External Link / URL</option>
                        </x-ui.select-native>
                        @error('type')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2" id="file_input_container">
                        <x-ui.label for="file">File</x-ui.label>
                        <input type="file" name="file" id="file"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" />
                        <p class="text-xs text-muted-foreground">
                            Accepted: pdf, doc(x), ppt(x), xls(x), jpg, png, mp4, webm, zip — up to 50 MB.
                        </p>
                        @error('file')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2" id="url_input_container" style="display: none;">
                        <x-ui.label for="file_path">URL / Link</x-ui.label>
                        <x-ui.input type="url" name="file_path" id="file_path" value="{{ old('file_path') }}"
                            placeholder="https://..." />
                        @error('file_path')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="description">Description</x-ui.label>
                        <x-ui.textarea name="description" id="description" rows="3">{{ old('description') }}</x-ui.textarea>
                        @error('description')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <x-ui.button type="submit">
                            <i class="fas fa-upload mr-2"></i> Upload Material
                        </x-ui.button>
                        <x-ui.button variant="outline" as="a"
                            href="{{ route('dashboard.courses.materials.index', $course) }}">
                            Cancel
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card-content>
        </x-ui.card>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeSelect = document.getElementById('material_type');
            const fileContainer = document.getElementById('file_input_container');
            const urlContainer = document.getElementById('url_input_container');

            function sync() {
                const isLink = typeSelect.value === 'link';
                fileContainer.style.display = isLink ? 'none' : 'block';
                urlContainer.style.display = isLink ? 'block' : 'none';
            }

            if (typeSelect) {
                typeSelect.addEventListener('change', sync);
                sync();
            }
        });
    </script>
@endpush
