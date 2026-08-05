@extends('layouts.frontend')

@section('title', 'Our Team - Dhaka IT Institute')

@section('content')
<section class="bg-black py-20 text-white">
    <div class="mx-auto max-w-7xl px-4 text-center">
        <p class="font-semibold uppercase tracking-[0.25em] text-green-400">People behind your progress</p>
        <h1 class="mt-4 text-4xl font-black md:text-6xl">Meet our training team</h1>
        <p class="mx-auto mt-5 max-w-2xl text-lg text-gray-300">Practical instructors and support professionals committed to skills, projects and career readiness.</p>
    </div>
</section>

<section class="bg-gray-50 py-16">
    <div class="mx-auto max-w-7xl px-4">
        @if($team->isNotEmpty())
            <div class="grid gap-7 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($team as $member)
                    @php
                        $name = $member->user?->name ?? 'Dhaka IT Institute Trainer';
                        $photo = $member->profile_image
                            ? (filter_var($member->profile_image, FILTER_VALIDATE_URL) ? $member->profile_image : asset('storage/' . ltrim($member->profile_image, '/')))
                            : 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=168536&color=fff&size=512&bold=true';
                    @endphp
                    <article class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                        <div class="relative h-72 overflow-hidden bg-green-900"><img src="{{ $photo }}" alt="{{ $name }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($name) }}&background=168536&color=fff&size=512&bold=true';"><div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/60 to-transparent"></div></div>
                        <div class="p-6">
                            <h2 class="text-xl font-bold text-gray-900">{{ $name }}</h2>
                            <p class="mt-1 font-semibold text-green-700">{{ $member->department ?: ($member->category?->name ?? 'Professional Instructor') }}</p>
                            @if($member->subjects)
                                <p class="mt-3 text-sm leading-6 text-gray-600">{{ collect($member->subjects)->take(3)->join(' • ') }}</p>
                            @endif
                            <a href="{{ route('contact') }}" class="mt-5 inline-flex text-sm font-bold text-green-800 hover:text-black">Connect with our team →</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="grid gap-7 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['name' => 'Lead Web Development Instructor', 'role' => 'Web Development & Freelancing', 'icon' => 'fa-code', 'photo' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=600&q=85'],
                    ['name' => 'Digital Skills Instructor', 'role' => 'Office Applications & Design', 'icon' => 'fa-laptop', 'photo' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=600&q=85'],
                    ['name' => 'Student Success Team', 'role' => 'Admission, Support & Career Guidance', 'icon' => 'fa-headset', 'photo' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600&q=85'],
                ] as $member)
                    <article class="group overflow-hidden rounded-2xl border border-gray-100 bg-white text-center shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                        <div class="relative h-64 overflow-hidden bg-green-900"><img src="{{ $member['photo'] }}" alt="{{ $member['name'] }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy"><div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div><span class="absolute bottom-4 left-1/2 flex h-12 w-12 -translate-x-1/2 items-center justify-center rounded-full bg-white text-xl text-green-800 shadow"><i class="fa-solid {{ $member['icon'] }}"></i></span></div>
                        <div class="p-7">
                        <h2 class="mt-5 text-xl font-bold text-gray-900">{{ $member['name'] }}</h2>
                        <p class="mt-2 font-semibold text-green-700">{{ $member['role'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
