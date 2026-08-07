@extends('layouts.admin')

@section('title', 'Manage Videos: ' . $course->name)
@section('page-title', 'Manage Videos')

@section('content')
<div class="bg-white rounded-xl shadow-md border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900"> Videos for {{ $course->name }}</h2>
        <a href="{{ route('dashboard.courses.videos.create', $course) }}" class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add New Video
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">#</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thumbnail</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preview</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="video-list">
                @forelse($videos as $video)
                <tr data-id="{{ $video->id }}" class="hover:bg-gray-50 group">
                    <td class="px-6 py-4 whitespace-nowrap text-gray-400 cursor-move">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="h-10 w-16 bg-gray-100 rounded overflow-hidden">
                            @if($video->thumbnail)
                                <img src="{{ asset('storage/' . $video->thumbnail) }}" class="h-full w-full object-cover">
                            @elseif($video->video_type == 'youtube' && $video->external_id)
                                <img src="https://img.youtube.com/vi/{{ $video->external_id }}/hqdefault.jpg" class="h-full w-full object-cover" onerror="this.onerror=null; this.src='https://img.youtube.com/vi/{{ $video->external_id }}/mqdefault.jpg';">
                            @else
                                <div class="h-full w-full flex items-center justify-center text-gray-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-text-light">{{ $video->title }}</div>
                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ $video->description }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $video->video_type == 'upload' ? 'bg-blue-100 text-blue-800' : 
                               ($video->video_type == 'youtube' ? 'bg-red-100 text-red-800' : 'bg-indigo-100 text-indigo-800') }}">
                            {{ ucfirst($video->video_type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($video->is_preview)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Preview</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        @php
                            $streamUrl = $video->video_type === 'upload' && $video->video_path
                                ? route('dashboard.courses.videos.stream', [$course, $video])
                                : '';
                        @endphp
                        <button onclick="playVideo('{{ $video->video_type }}', '{{ $video->external_id }}', '{{ $streamUrl }}')" 
                                class="text-bd-green hover:text-bd-green-dark mr-3" title="Play Video">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </button>
                        <a href="{{ route('dashboard.courses.videos.edit', [$course, $video]) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                        <form action="{{ route('dashboard.courses.videos.destroy', [$course, $video]) }}" method="POST" class="inline" onsubmit="return confirmDelete(this, 'Are you sure you want to delete this video?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        No videos uploaded yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Video Player Modal -->
<div id="videoModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeVideoModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-black rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-black relative">
                <button onclick="closeVideoModal()" class="absolute top-2 right-2 text-white hover:text-gray-300 z-10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <div id="videoPlayerContainer" class="aspect-w-16 aspect-h-9 flex items-center justify-center bg-black h-96">
                    <!-- Video content will be injected here -->
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('video-list');
        var sortable = Sortable.create(el, {
            handle: '.cursor-move',
            animation: 150,
            onEnd: function() {
                var order = [];
                document.querySelectorAll('#video-list tr').forEach(function(row) {
                    order.push(row.dataset.id);
                });
                
                fetch('{{ route("dashboard.courses.videos.reorder", $course) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ order: order })
                });
            }
        });
    });

    function playVideo(type, externalId, videoPath) {
        const container = document.getElementById('videoPlayerContainer');
        const modal = document.getElementById('videoModal');
        let html = '';

        if (type === 'youtube') {
            html = `<iframe width="100%" height="100%" src="https://www.youtube.com/embed/${externalId}?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
        } else if (type === 'vimeo') {
            html = `<iframe src="https://player.vimeo.com/video/${externalId}?autoplay=1" width="100%" height="100%" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>`;
        } else if (type === 'upload') {
            html = `<video controls autoplay class="w-full h-full"><source src="${videoPath}" type="video/mp4">Your browser does not support the video tag.</video>`;
        }

        container.innerHTML = html;
        modal.classList.remove('hidden');
    }

    function closeVideoModal() {
        const container = document.getElementById('videoPlayerContainer');
        const modal = document.getElementById('videoModal');
        container.innerHTML = ''; // Stop playback
        modal.classList.add('hidden');
    }
</script>
@endpush
@endsection
