@extends('layouts.admin')

@section('title', 'Edit Video: ' . $video->title)
@section('page-title', 'Edit Video')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <form action="{{ route('dashboard.courses.videos.update', [$course, $video]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Title -->
                <x-ui.input name="title" label="Video Title" :value="$video->title" required />

                <!-- Description -->
                <x-ui.textarea name="description" label="Description" :value="$video->description" rows="3" />

                <!-- Video Type Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Video Source</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="border rounded-lg p-3 cursor-pointer hover:bg-gray-50 flex flex-col items-center {{ $video->video_type == 'upload' ? 'bg-blue-50 border-blue-200' : '' }}">
                            <input type="radio" name="video_type" value="upload" {{ $video->video_type == 'upload' ? 'checked' : '' }} onclick="toggleVideoFields('upload')">
                            <span class="mt-1 text-sm font-medium">File Upload</span>
                        </label>
                        <label class="border rounded-lg p-3 cursor-pointer hover:bg-gray-50 flex flex-col items-center {{ $video->video_type == 'youtube' ? 'bg-blue-50 border-blue-200' : '' }}">
                            <input type="radio" name="video_type" value="youtube" {{ $video->video_type == 'youtube' ? 'checked' : '' }} onclick="toggleVideoFields('youtube')">
                            <span class="mt-1 text-sm font-medium">YouTube</span>
                        </label>
                        <label class="border rounded-lg p-3 cursor-pointer hover:bg-gray-50 flex flex-col items-center {{ $video->video_type == 'vimeo' ? 'bg-blue-50 border-blue-200' : '' }}">
                            <input type="radio" name="video_type" value="vimeo" {{ $video->video_type == 'vimeo' ? 'checked' : '' }} onclick="toggleVideoFields('vimeo')">
                            <span class="mt-1 text-sm font-medium">Vimeo</span>
                        </label>
                    </div>
                </div>

                <!-- Fields -->
                <div id="upload-field" class="{{ $video->video_type == 'upload' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Video File</label>
                    @if($video->video_type == 'upload' && $video->video_path)
                        <div class="mb-2 text-sm text-gray-600 bg-gray-50 p-2 rounded">
                            Current file: <a href="{{ route('dashboard.courses.videos.stream', [$course, $video]) }}" target="_blank" class="text-blue-600 underline">View Video</a>
                        </div>
                    @endif
                    <input type="file" name="video_file" accept="video/mp4,video/quicktime,video/x-msvideo" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-bd-green file:text-white
                        hover:file:bg-bd-green-dark
                    "/>
                    <p class="text-xs text-gray-500 mt-1">Leave empty to keep current file.</p>
                </div>

                <div id="external-field" class="{{ $video->video_type != 'upload' ? '' : 'hidden' }}">
                    <x-ui.input name="external_id" label="Video URL or ID" :value="$video->external_id" placeholder="e.g. https://www.youtube.com/watch?v=dQw4w9WgXcQ" />
                    <p class="text-xs text-gray-500 mt-1">You can paste the full YouTube/Vimeo URL or just the video ID.</p>
                </div>

                <!-- Thumbnail -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Thumbnail</label>
                     @if($video->thumbnail)
                        <div class="mb-2">
                             <img src="{{ asset('storage/' . $video->thumbnail) }}" class="h-20 w-32 object-cover rounded">
                        </div>
                    @endif
                    <input type="file" name="thumbnail_file" accept="image/*" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-gray-100 file:text-gray-700
                        hover:file:bg-gray-200
                    "/>
                    <p class="text-xs text-gray-500 mt-1">Leave empty to keep current thumbnail.</p>
                </div>

                <!-- Duration -->
                <div class="grid grid-cols-2 gap-4">
                    <x-ui.input type="number" name="duration" label="Duration (Seconds)" :value="$video->duration" />
                    
                    <div class="flex items-center pt-8">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_preview" value="1" {{ $video->is_preview ? 'checked' : '' }} class="rounded border-gray-300 text-bd-green shadow-sm focus:border-bd-green focus:ring focus:ring-bd-green focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-900">Free Preview (Public)</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-between pt-6">
                    <a href="{{ route('dashboard.courses.videos.index', $course) }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-6 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark">Update Video</button>
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
