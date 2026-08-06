@extends('layouts.admin')
@section('title','Services')
@section('page-title','Services / Sections')
@section('page-description','Manage ecommerce-style services shown on the frontend')
@section('content')
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex gap-2">
            <input name="search" value="{{ request('search') }}" placeholder="Search services..." class="rounded-lg border border-gray-300 px-4 py-2 text-sm w-64 focus:ring-2 focus:ring-bd-green outline-none" />
            <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Search</button>
        </form>
        <a href="{{ route('dashboard.services.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-bd-green px-4 py-2 text-sm font-bold text-white hover:bg-bd-green-dark">+ New Service</a>
    </div>

    @if(session('success'))<div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($services as $s)
        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm flex flex-col">
            <div class="relative h-44 overflow-hidden bg-gray-100">
                <img src="{{ $s->image_url }}" alt="{{ $s->title }}" class="h-full w-full object-cover" loading="lazy">
                @if($s->badge)<span class="absolute left-3 top-3 rounded-full bg-black px-3 py-1 text-xs font-bold text-white">{{ $s->badge }}</span>@endif
                @if($s->discount_percent)<span class="absolute right-3 top-3 rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">-{{ $s->discount_percent }}%</span>@endif
                <span class="absolute bottom-3 left-3 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-gray-800 backdrop-blur">{{ $s->is_active ? 'Active' : 'Hidden' }} @if($s->is_featured)· Featured @endif</span>
            </div>
            <div class="flex-1 p-4">
                <h3 class="font-bold text-gray-900 line-clamp-1">{{ $s->title }}</h3>
                <p class="mt-1 text-sm text-gray-600 line-clamp-2">{{ $s->short_description ?: Str::limit(strip_tags($s->description),120) }}</p>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-xl font-black text-gray-900">৳{{ number_format($s->price,0) }}</span>
                    @if($s->compare_price && $s->compare_price > $s->price)<span class="text-sm text-gray-400 line-through">৳{{ number_format($s->compare_price,0) }}</span>@endif
                </div>
            </div>
            <div class="flex gap-2 p-4 pt-0">
                <a href="{{ route('dashboard.services.edit',$s) }}" class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-center text-sm font-semibold hover:bg-gray-50">Edit</a>
                <form action="{{ route('dashboard.services.destroy',$s) }}" method="POST" onsubmit="return confirm('Delete this service?')" class="flex-1">
                    @csrf @method('DELETE')
                    <button class="w-full rounded-lg bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">Delete</button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full rounded-2xl border border-dashed border-gray-200 bg-white p-10 text-center text-gray-500">No services yet. Create your first one.</div>
        @endforelse
    </div>
    <div>{{ $services->links() }}</div>
</div>
@endsection
