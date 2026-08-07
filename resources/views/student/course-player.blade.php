@extends('layouts.admin')

@section('title', $course->name)

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-5 flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('student.courses') }}" class="text-sm text-indigo-600 hover:underline">← My courses</a>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $course->name }}</h1>
            <p class="text-gray-600">{{ $video->title }}</p>
        </div>
        <div class="text-right text-sm text-gray-600">
            {{ $progress->where('completed', true)->count() }} / {{ $videos->count() }} completed
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <main class="lg:col-span-2">
            <div class="aspect-video overflow-hidden rounded-xl bg-black shadow">
                @if($video->video_type === 'upload')
                    <video id="course-video" class="h-full w-full" controls autoplay controlsList="nodownload">
                        <source src="{{ $video->getVideoUrl() }}">
                    </video>
                @elseif($video->video_type === 'youtube')
                    <div id="youtube-player" class="h-full w-full"></div>
                @elseif($video->video_type === 'vimeo')
                    <iframe id="vimeo-player" class="h-full w-full" src="{{ $video->getVideoUrl() }}?autoplay=1"
                        allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                @else
                    <iframe class="h-full w-full" src="{{ $video->getVideoUrl() }}" allow="autoplay; fullscreen" allowfullscreen></iframe>
                @endif
            </div>

            <div class="mt-4 rounded-xl bg-white p-5 shadow">
                <h2 class="font-semibold text-gray-900">{{ $video->title }}</h2>
                @if($video->description)<p class="mt-2 text-gray-600">{{ $video->description }}</p>@endif
                <div id="advance-status" class="mt-4 hidden rounded-lg bg-green-50 p-3 text-sm text-green-800"></div>
                @if($nextVideo)
                    <a href="{{ route('student.course.video', [$course, $nextVideo]) }}" class="mt-4 inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                        Next: {{ $nextVideo->title }} →
                    </a>
                @endif
            </div>
        </main>

        <aside class="rounded-xl bg-white shadow">
            <div class="border-b p-4"><h2 class="font-semibold">Course lessons</h2></div>
            <div class="max-h-[70vh] overflow-y-auto divide-y">
                @foreach($videos as $index => $lesson)
                    @php($done = ($progress->get($lesson->id)?->completed ?? false))
                    <a href="{{ route('student.course.video', [$course, $lesson]) }}"
                       class="flex gap-3 p-4 hover:bg-gray-50 {{ $lesson->id === $video->id ? 'bg-indigo-50 border-l-4 border-indigo-600' : '' }}">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $done ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $done ? '✓' : $index + 1 }}
                        </span>
                        <span class="text-sm font-medium text-gray-800">{{ $lesson->title }}</span>
                    </a>
                @endforeach
            </div>
        </aside>
    </div>
</div>

@push('scripts')
<script src="https://player.vimeo.com/api/player.js"></script>
<script>
(() => {
    let completing = false;
    const completeUrl = @json(route('student.course.video.complete', [$course, $video]));
    const csrf = @json(csrf_token());

    async function completeAndAdvance(watchedSeconds = 0) {
        if (completing) return;
        completing = true;
        const status = document.getElementById('advance-status');
        status.textContent = 'Lesson complete. Loading the next lesson…';
        status.classList.remove('hidden');

        try {
            const response = await fetch(completeUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({watched_seconds: Math.floor(watchedSeconds || 0)})
            });
            if (!response.ok) throw new Error('Unable to save completion');
            const result = await response.json();
            if (result.next_url) window.location.assign(result.next_url);
            else if (result.certificate_url) {
                status.innerHTML = `Course complete — <a class="font-semibold underline" href="${result.certificate_url}">view your certificate</a>.`;
            } else status.textContent = 'Course complete — great work!';
        } catch (error) {
            completing = false;
            status.textContent = 'The lesson ended, but completion could not be saved. Please use Next and try again.';
            status.className = 'mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-800';
        }
    }

    const localVideo = document.getElementById('course-video');
    if (localVideo) localVideo.addEventListener('ended', () => completeAndAdvance(localVideo.duration));

    const vimeoFrame = document.getElementById('vimeo-player');
    if (vimeoFrame && window.Vimeo) {
        const player = new Vimeo.Player(vimeoFrame);
        player.on('ended', () => player.getDuration().then(completeAndAdvance));
    }

    window.onYouTubeIframeAPIReady = function () {
        new YT.Player('youtube-player', {
            videoId: @json($video->video_type === 'youtube' ? $video->external_id : ''),
            playerVars: {autoplay: 1, rel: 0},
            events: {onStateChange: event => {
                if (event.data === YT.PlayerState.ENDED) completeAndAdvance(event.target.getDuration());
            }}
        });
    };

    @if($video->video_type === 'youtube')
        const youtubeApi = document.createElement('script');
        youtubeApi.src = 'https://www.youtube.com/iframe_api';
        document.head.appendChild(youtubeApi);
    @endif
})();
</script>
@endpush
@endsection
