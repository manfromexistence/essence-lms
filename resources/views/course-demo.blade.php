@extends('layouts.frontend')

@section('title', $course->name . ' Demo Class')

@section('content')
<section class="bg-gray-950 py-14 text-white">
    <div class="mx-auto max-w-6xl px-4">
        <a href="{{ route('courses') }}" class="text-sm font-semibold text-green-400 hover:text-green-300">← Back to courses</a>
        <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_340px]">
            <div>
                <div class="aspect-video overflow-hidden rounded-2xl bg-black shadow-2xl ring-1 ring-white/10">
                    @if($video->video_type === 'youtube')
                        <iframe class="h-full w-full" src="https://www.youtube-nocookie.com/embed/{{ $video->external_id }}" title="{{ $video->title }}" allowfullscreen></iframe>
                    @elseif($video->video_type === 'vimeo')
                        <iframe class="h-full w-full" src="https://player.vimeo.com/video/{{ $video->external_id }}" title="{{ $video->title }}" allowfullscreen></iframe>
                    @elseif($video->video_type === 'facebook')
                        <iframe class="h-full w-full" src="https://www.facebook.com/plugins/video.php?href={{ urlencode($video->external_id) }}" title="{{ $video->title }}" allowfullscreen></iframe>
                    @else
                        <video class="h-full w-full" controls controlsList="nodownload" preload="metadata"><source src="{{ route('courses.demo.stream', [$course, $video]) }}"></video>
                    @endif
                </div>
                <h1 class="mt-6 text-3xl font-black">{{ $video->title }}</h1>
                <p class="mt-3 leading-7 text-gray-300">{{ $video->description ?: 'Watch this free demo lesson to understand the course teaching style and practical learning approach.' }}</p>
            </div>
            <aside class="rounded-2xl bg-white p-6 text-gray-900 shadow-xl">
                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold uppercase text-green-800">Free demo class</span>
                <h2 class="mt-4 text-2xl font-black">{{ $course->name }}</h2>
                <p class="mt-3 text-sm leading-6 text-gray-600">{{ Str::limit($course->description, 180) }}</p>
                <div class="mt-6 border-t pt-5"><p class="text-sm text-gray-500">Course fee</p><p class="text-3xl font-black text-green-800">৳{{ number_format($course->price) }}</p></div>
                <a href="{{ route('admission.create', ['mode' => $course->delivery_mode]) }}" class="mt-6 flex w-full justify-center rounded-xl bg-green-800 px-5 py-3 font-bold text-white transition hover:bg-black">Apply for this course</a>
            </aside>
        </div>
    </div>
</section>
@endsection
