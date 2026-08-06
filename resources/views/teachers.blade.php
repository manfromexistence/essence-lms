@extends('layouts.frontend')

@section('title', ($page ? $page->getContent('page_title', 'আমাদের শিক্ষকমণ্ডলী') : 'আমাদের শিক্ষকমণ্ডলী') . ' - Dhaka IT Institute')

@section('content')
<section class="hero hero--solid hero--dark">
    <div class="hero-inner text-center">
        <h1 class="hero-title">{{ $page ? $page->getContent('page_title', 'আমাদের শিক্ষকমণ্ডলী') : 'আমাদের শিক্ষকমণ্ডলী' }}</h1>
        <p class="hero-subtitle mt-4 max-w-2xl text-lg md:text-xl">{{ $page ? $page->getContent('page_subtitle', 'অভিজ্ঞ ও দক্ষ শিক্ষক যারা আপনার সফলতার পথে রাখিবে।') : '' }}</p>
    </div>
</section>

@if($teachers->isNotEmpty())
@php $departments = $teachers->map(fn($t) => $t->designation ?: ($t->department ?: ($t->category?->name ?? 'Other')))->unique()->values(); @endphp

<section class="bg-white py-10">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <div class="text-4xl font-black text-primary">{{ $teachers->count() }}</div>
        <p class="text-lg text-gray-600">Active teachers & trainers</p>
        <div class="mt-4 flex justify-center gap-4 text-sm font-semibold text-gray-400">
            <span>{{ $departments->count() }} departments</span>
            <span>•</span>
            <span>Expert-led training</span>
        </div>
    </div>
</section>

<section class="bg-white py-5 border-b border-gray-100 sticky top-0 z-20">
    <div class="max-w-7xl mx-auto px-4 flex flex-wrap gap-2 justify-center">
        <button type="button" data-filter="all" class="filter-chip rounded-full border border-transparent bg-primary/10 px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/20 transition">All</button>
        @foreach($departments as $dept)
        <button type="button" data-filter="{{ Str::slug($dept) }}" class="filter-chip rounded-full border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-primary/10 hover:text-primary transition">{{ $dept }}</button>
        @endforeach
    </div>
</section>

<section class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($teachers as $teacher)
                @php
                    $name = $teacher->user?->name ?? 'Teacher';
                    $photo = $teacher->avatar_url;
                    $dept = $teacher->designation ?: ($teacher->department ?: ($teacher->category?->name ?? 'Other'));
                    $links = $teacher->social_links ?? [];
                @endphp
                <article data-department="{{ Str::slug($dept) }}" class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                    <div class="relative h-56 overflow-hidden bg-gray-100">
                        <img src="{{ $photo }}" alt="{{ $name }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105" loading="lazy" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($name) }}&background=168536&color=fff&size=512&bold=true'">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                            <h3 class="text-lg font-black leading-tight">{{ $name }}</h3>
                            <p class="text-sm font-medium text-white/90">{{ $dept }}</p>
                        </div>
                    </div>
                    <div class="p-4">
                        @if($teacher->subjects)
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(collect($teacher->subjects)->take(4) as $subject)
                            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">{{ $subject }}</span>
                            @endforeach
                        </div>
                        @endif
                        @if($teacher->bio)<p class="mt-3 line-clamp-2 text-sm leading-6 text-gray-500">{{ $teacher->bio }}</p>@endif
                        @if(!empty(array_filter($links)))
                        <div class="mt-3 flex gap-2">
                            @foreach(array_filter($links) as $platform => $url)
                            @php $icons=['facebook'=>'fa-facebook','linkedin'=>'fa-linkedin','twitter'=>'fa-x-twitter','instagram'=>'fa-instagram','github'=>'fa-github','website'=>'fa-globe']; @endphp
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-xs text-gray-500 transition hover:bg-primary hover:text-white"><i class="fa-brands {{ $icons[$platform] ?? 'fa-link' }}"></i></a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@else
<section class="bg-gray-50 py-24 text-center">
    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10"><i class="fa-solid fa-chalkboard-user text-3xl text-primary"></i></div>
    <h2 class="text-2xl font-bold text-gray-900">No teachers added yet</h2>
    <p class="mt-2 max-w-md mx-auto text-gray-600">Our faculty team is assembling. Check back soon.</p>
    <a href="{{ route('contact') }}" class="mt-6 inline-flex items-center justify-center rounded-xl bg-primary px-6 py-3 font-bold text-white transition hover:opacity-90">Contact us</a>
</section>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const chips=document.querySelectorAll('.filter-chip');
    const cards=document.querySelectorAll('article[data-department]');
    chips.forEach(c=>c.addEventListener('click',function(){
        chips.forEach(x=>{x.classList.remove('bg-primary','text-white');x.classList.add('bg-gray-100','text-gray-700');});
        this.classList.add('bg-primary','text-white');this.classList.remove('bg-gray-100','text-gray-700');
        const f=this.dataset.filter;
        cards.forEach(card=>{card.style.display=(f==='all'||card.dataset.department===f)?'':'none';});
    }));
});
</script>
@endpush
