@extends('layouts.admin')

@section('title', $course->name)
@section('page-title', 'Course View')

@section('content')
    <div class="max-w-[1600px] mx-auto">
        <!-- Main Player Layout -->
        <div class="flex flex-col lg:flex-row gap-6">
            
            <!-- Left Column: Video Player & Details (75%) -->
            <div class="lg:w-3/4 space-y-6">
                <!-- Video Player Container -->
                <div class="bg-black rounded-xl overflow-hidden shadow-2xl aspect-video relative group">
                    @php
                        $activeVideo = $course->videos->first(); // Default to first video
                        if(request()->has('video_id')) {
                            $activeVideo = $course->videos->where('id', request()->get('video_id'))->first() ?? $activeVideo;
                        }
                    @endphp

                    @if($activeVideo)
                        <iframe 
                            src="https://www.youtube.com/embed/{{ $activeVideo->external_id }}?rel=0&modestbranding=1" 
                            class="w-full h-full absolute inset-0"
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                        </iframe>
                    @else
                        <div class="flex flex-col items-center justify-center h-full text-white">
                            <svg class="w-16 h-16 opacity-50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-lg">No videos available for this course.</p>
                        </div>
                    @endif
                </div>

                <!-- Course Title & Actions -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                                {{ $activeVideo ? $activeVideo->title : $course->name }}
                            </h1>
                            <p class="text-sm text-gray-500 flex items-center gap-4">
                                <span>{{ $course->name }}</span>
                                <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                                <span>{{ $activeVideo ? 'Lesson ' . $activeVideo->order : 'Introduction' }}</span>
                            </p>
                        </div>
                        <div class="flex items-center space-x-3">
                             <a href="{{ route('dashboard.courses.edit', $course) }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Course
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Course Info Tabs -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden" x-data="{ tab: 'overview' }">
                    <div class="flex border-b border-gray-200">
                        <button @click="tab = 'overview'" 
                            :class="{ 'border-bd-green text-bd-green': tab === 'overview', 'border-transparent text-gray-500 hover:text-gray-700': tab !== 'overview' }"
                            class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm focus:outline-none transition-colors">
                            Overview
                        </button>
                        <button @click="tab = 'curriculum'" 
                            :class="{ 'border-bd-green text-bd-green': tab === 'curriculum', 'border-transparent text-gray-500 hover:text-gray-700': tab !== 'curriculum' }"
                            class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm focus:outline-none transition-colors">
                            Curriculum
                        </button>
                        <button @click="tab = 'batches'" 
                            :class="{ 'border-bd-green text-bd-green': tab === 'batches', 'border-transparent text-gray-500 hover:text-gray-700': tab !== 'batches' }"
                            class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm focus:outline-none transition-colors">
                            Batches
                        </button>
                    </div>

                    <div class="p-6">
                        <!-- Overview Tab -->
                        <div x-show="tab === 'overview'" class="space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">About this course</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    {{ $course->description ?? 'No description available for this course.' }}
                                </p>
                            </div>

                            @if($course->objectives && count($course->objectives) > 0)
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-3">What you'll learn</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach($course->objectives as $objective)
                                            <div class="flex items-start">
                                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span class="text-sm text-gray-600">{{ $objective }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                             <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-gray-100">
                                <div>
                                    <span class="block text-xs text-gray-500 uppercase">Level</span>
                                    <span class="font-medium text-gray-900">{{ ucfirst($course->level ?? 'All Levels') }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-500 uppercase">Duration</span>
                                    <span class="font-medium text-gray-900">{{ $course->duration ?? '0' }} {{ $course->duration_unit ?? 'Hours' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-500 uppercase">Lectures</span>
                                    <span class="font-medium text-gray-900">{{ $course->videos->count() }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-500 uppercase">Students</span>
                                    <span class="font-medium text-gray-900">{{ $course->students_count }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Curriculum Tab -->
                        <div x-show="tab === 'curriculum'" x-cloak>
                             @if($course->syllabus && count($course->syllabus) > 0)
                                <div class="space-y-4">
                                    @foreach($course->syllabus as $index => $topic)
                                        <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                                            <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-white border border-gray-200 rounded-full text-sm font-semibold text-gray-600 mr-4">
                                                {{ $index + 1 }}
                                            </span>
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-900">{{ $topic }}</h4>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-center py-6">No syllabus defined yet.</p>
                            @endif
                        </div>

                        <!-- Batches Tab -->
                        <div x-show="tab === 'batches'" x-cloak>
                             @if($course->batches->count() > 0)
                                <div class="space-y-3">
                                    @foreach($course->batches as $batch)
                                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-bd-green transition-colors">
                                            <div>
                                                <h4 class="font-medium text-gray-900">{{ $batch->name }}</h4>
                                                <p class="text-sm text-gray-500">{{ $batch->schedule ?? 'TBA' }}</p>
                                            </div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $batch->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ ucfirst($batch->status) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <p class="text-gray-500">No active batches for this course.</p>
                                    <a href="{{ route('dashboard.batches.create', ['course_id' => $course->id]) }}" class="text-bd-green hover:underline text-sm mt-2 inline-block">Create a Batch</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Playlist (25%) -->
            <div class="lg:w-1/4">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden sticky top-6">
                    <div class="p-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="font-semibold text-gray-900">Course Content</h3>
                        <p class="text-xs text-gray-500 mt-1">{{ $course->videos->count() }} videos • {{ $course->duration ?? 'N/A' }} {{ $course->duration_unit ?? '' }}</p>
                    </div>
                    <div class="max-h-[calc(100vh-200px)] overflow-y-auto">
                        @forelse($course->videos as $video)
                            <a href="?video_id={{ $video->id }}" 
                               class="block p-3 border-b border-gray-100 hover:bg-gray-50 transition-colors {{ ($activeVideo && $activeVideo->id === $video->id) ? 'bg-emerald-50 border-l-4 border-l-bd-green' : 'border-l-4 border-l-transparent' }}">
                                <div class="flex gap-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-24 h-14 bg-gray-100 rounded overflow-hidden relative">
                                            @if($video->video_type === 'youtube' && $video->external_id)
                                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-red-400 to-pink-500" data-youtube-id="{{ $video->external_id }}" data-video-title="{{ $video->title }}">
                                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                                    </svg>
                                                </div>
                                            @elseif($video->video_type === 'youtube')
                                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-red-400 to-pink-500">
                                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                                    </svg>
                                                </div>
                                            @elseif($video->video_type === 'vimeo')
                                                @if($video->thumbnail)
                                                <img src="{{ media_url($video->thumbnail) }}" alt="{{ $video->title }}" class="w-full h-full object-cover">
                                                @else
                                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-sky-400 to-blue-600">
                                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                @endif
                                            @elseif($video->thumbnail)
                                                <img src="{{ media_url($video->thumbnail) }}" alt="{{ $video->title }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-400 to-teal-500">
                                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                            @endif
                                            @if($activeVideo && $activeVideo->id === $video->id)
                                                <div class="absolute inset-0 bg-bd-green bg-opacity-20 border-2 border-bd-green rounded flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium {{ ($activeVideo && $activeVideo->id === $video->id) ? 'text-bd-green' : 'text-gray-700' }} line-clamp-2 mb-1">
                                            {{ $video->title }}
                                        </h4>
                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                            <span class="inline-flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ floor($video->duration / 60) }} min
                                            </span>
                                            @if($video->is_preview)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-medium">
                                                    Preview
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="p-8 text-center text-gray-500">
                                <p class="text-sm">No videos uploaded yet.</p>
                                <a href="{{ route('dashboard.courses.videos.index', $course) }}" class="text-bd-green hover:underline text-xs mt-2 block">Upload Videos</a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load YouTube thumbnails asynchronously to avoid 404 errors
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-youtube-id]').forEach(function(container) {
                const videoId = container.getAttribute('data-youtube-id');
                const videoTitle = container.getAttribute('data-video-title');
                
                // Try to load thumbnail with fetch to avoid console errors
                fetch(`https://img.youtube.com/vi/${videoId}/hqdefault.jpg`, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            // Thumbnail exists, replace icon with image
                            container.innerHTML = `<img src="https://img.youtube.com/vi/${encodeURIComponent(videoId)}/hqdefault.jpg" alt="" class="w-full h-full object-cover">`;
                        }
                        // If not ok, keep the YouTube icon (already rendered)
                    })
                    .catch(() => {
                        // On error, keep the YouTube icon (already rendered)
                    });
            });
        });
    </script>
@endsection