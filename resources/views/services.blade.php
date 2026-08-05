@extends('layouts.frontend')

@section('title', 'Services - Dhaka IT Institute')

@section('content')
<section class="bg-gradient-to-br from-green-900 via-green-800 to-black py-20 text-white">
    <div class="mx-auto max-w-7xl px-4 text-center">
        <span class="rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold">Learn. Build. Grow.</span>
        <h1 class="mt-6 text-4xl font-black md:text-6xl">Services that turn skills into outcomes</h1>
        <p class="mx-auto mt-5 max-w-3xl text-lg text-green-50">Training, digital solutions and practical support for students, freelancers and growing businesses.</p>
    </div>
</section>

<section class="bg-gray-50 py-16">
    <div class="mx-auto max-w-7xl px-4">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($services as $service)
                <article class="group rounded-2xl border border-gray-100 bg-white p-7 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-green-200 hover:shadow-xl">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-green-100 text-2xl text-green-800 transition group-hover:bg-green-800 group-hover:text-white">
                        <i class="fa-solid {{ $service['icon'] ?? 'fa-check' }}"></i>
                    </div>
                    <h2 class="mt-6 text-xl font-bold text-gray-900">{{ $service['title'] }}</h2>
                    <p class="mt-3 leading-7 text-gray-600">{{ $service['description'] }}</p>
                </article>
            @endforeach
        </div>
        <div class="mt-12 rounded-3xl bg-black px-7 py-10 text-center text-white md:px-12">
            <h2 class="text-3xl font-black">Need a course or digital solution?</h2>
            <p class="mx-auto mt-3 max-w-2xl text-gray-300">Tell us your goal and our team will recommend the right training or service.</p>
            <a href="{{ route('contact') }}" class="mt-6 inline-flex rounded-xl bg-green-700 px-6 py-3 font-bold transition hover:bg-green-600">Talk to our team</a>
        </div>
    </div>
</section>
@endsection
