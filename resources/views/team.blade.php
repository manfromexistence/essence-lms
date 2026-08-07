@extends('layouts.frontend')

@section('title', ($page ? $page->getContent('page_title', 'আমাদের টিম') : 'আমাদের টিম') . ' - Dhaka IT Institute')

@section('content')
<section class="hero hero--solid hero--dark">
    <div class="hero-inner text-center">
        <h1 class="hero-title">{{ $page ? $page->getContent('page_title', 'Meet our team') : 'Meet our team' }}</h1>
        <p class="hero-subtitle mx-auto mt-4 max-w-2xl text-lg md:text-xl">{{ $page ? $page->getContent('page_subtitle', 'Expert instructors and support professionals committed to hands-on mentorship and student outcomes.') : 'Expert instructors and support professionals committed to hands-on mentorship and student outcomes.' }}</p>
    </div>
</section>

@if($team->isNotEmpty())
@php
    $departments = $team->map(fn($m) => $m->designation ?: ($m->department ?: ($m->category?->name ?? 'Other')))->unique()->values();
    $featured = $team->where('is_featured', true);
    $featuredIds = $featured->pluck('id')->toArray();
@endphp

@if($featured->isNotEmpty())
<section class="bg-white py-12">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="mb-8 text-center text-2xl font-black text-gray-900">Featured team</h2>
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            @foreach($featured as $member)
                @include('partials.team-card', ['member' => $member, 'featured' => true])
            @endforeach
        </div>
    </div>
</section>
@endif

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
            @foreach($team as $member)
                @if(!in_array($member->id, $featuredIds))
                    @include('partials.team-card', ['member' => $member, 'featured' => false])
                @endif
            @endforeach
        </div>
    </div>
</section>

<div id="team-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4" onclick="closeTeamModal(event)">
    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white shadow-2xl" onclick="event.stopPropagation()">
        <button onclick="closeTeamModal()" class="absolute right-4 top-4 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-gray-600 shadow hover:bg-white">✕</button>
        <div id="team-modal-content"></div>
    </div>
</div>
@else
<section class="bg-gray-50 py-24 text-center">
    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10"><i class="fa-solid fa-users text-3xl text-primary"></i></div>
    <h2 class="text-2xl font-bold text-gray-900">No team members yet</h2>
    <p class="mt-2 max-w-md mx-auto text-gray-600">Our training team is assembling. Check back soon.</p>
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
function openTeamModal(el){
    const id=el.dataset.id;
    const content=document.getElementById('tm-'+id)?.innerHTML||'';
    document.getElementById('team-modal-content').innerHTML=content;
    document.getElementById('team-modal').classList.remove('hidden');
    document.getElementById('team-modal').classList.add('flex');
    document.body.style.overflow='hidden';
}
function closeTeamModal(e){
    if(e&&e.target!==e.currentTarget)return;
    document.getElementById('team-modal').classList.add('hidden');
    document.getElementById('team-modal').classList.remove('flex');
    document.body.style.overflow='';
}
</script>
@endpush
