@extends('layouts.admin')

@section('title', 'Add Video: ' . $course->name)
@section('page-title', 'Add New Video')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <form action="{{ route('dashboard.courses.videos.store', $course) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="space-y-6">
                <!-- Title -->
                <x-ui.input name="title" label="Video Title" required />

                <!-- Description -->
                <x-ui.textarea name="description" label="Description" rows="3" />

                <!-- Video Type Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Video Source</label>
                    <div class="grid grid-cols-4 gap-3">
                        <label class="border rounded-lg p-3 cursor-pointer hover:bg-gray-50 flex flex-col items-center">
                            <input type="radio" name="video_type" value="upload" checked onclick="toggleVideoFields('upload')">
                            <span class="mt-1 text-sm font-medium">File Upload</span>
                        </label>
                        <label class="border rounded-lg p-3 cursor-pointer hover:bg-gray-50 flex flex-col items-center">
                            <input type="radio" name="video_type" value="youtube" onclick="toggleVideoFields('youtube')">
                            <span class="mt-1 text-sm font-medium">YouTube</span>
                        </label>
                        <label class="border rounded-lg p-3 cursor-pointer hover:bg-gray-50 flex flex-col items-center">
                            <input type="radio" name="video_type" value="vimeo" onclick="toggleVideoFields('vimeo')">
                            <span class="mt-1 text-sm font-medium">Vimeo</span>
                        </label>
                        <label class="border rounded-lg p-3 cursor-pointer hover:bg-gray-50 flex flex-col items-center">
                            <input type="radio" name="video_type" value="facebook" onclick="toggleVideoFields('facebook')">
                            <span class="mt-1 text-sm font-medium">Facebook</span>
                        </label>
                    </div>
                </div>

                <!-- Fields -->
                <div id="upload-field">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Video File (MP4, Max 500MB)</label>
                    <input type="file" name="video_file" accept="video/mp4,video/quicktime,video/x-msvideo" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-bd-green file:text-white
                        hover:file:bg-bd-green-dark
                    "/>
                </div>

                <div id="external-field" class="hidden">
                    <x-ui.input name="external_id" label="Video URL or ID" placeholder="e.g. https://www.youtube.com/watch?v=dQw4w9WgXcQ" />
                    <p class="text-xs text-gray-500 mt-1">You can paste the full YouTube/Vimeo URL or just the video ID.</p>
                </div>

                <!-- Thumbnail -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Thumbnail (Optional)</label>
                    <input type="file" name="thumbnail_file" accept="image/*" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-gray-100 file:text-gray-700
                        hover:file:bg-gray-200
                    "/>
                </div>

                <!-- Duration -->
                <div class="grid grid-cols-2 gap-4">
                    <x-ui.input type="number" name="duration" label="Duration (Seconds)" placeholder="e.g. 120" />
                    
                    <div class="flex items-center pt-8">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_preview" value="1" class="rounded border-gray-300 text-bd-green shadow-sm focus:border-bd-green focus:ring focus:ring-bd-green focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-900">Free Preview (Public)</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end pt-6">
                    <button type="submit" class="px-6 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark">Upload Video</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleVideoFields(type) {
        const uploadField = document.getElementById('upload-field');
        const externalField = document.getElementById('external-field');
        
        if (type === 'upload') {
            uploadField.classList.remove('hidden');
            externalField.classList.add('hidden');
        } else {
            uploadField.classList.add('hidden');
            externalField.classList.remove('hidden');
        }
    }
</script>
@endsection
