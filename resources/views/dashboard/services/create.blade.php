@extends('layouts.admin')
@section('title','New Service')
@section('page-title','New Service')
@section('page-description','Add an ecommerce-style service/section')
@section('content')
<div class="max-w-4xl mx-auto">
<form action="{{ route('dashboard.services.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
@csrf
@if($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="rounded-xl border bg-white p-6 space-y-4">
    <h3 class="font-semibold">Basic Info</h3>
    <div class="grid md:grid-cols-2 gap-4">
        <label class="space-y-1"><span class="text-sm font-medium">Title *</span><input name="title" value="{{ old('title') }}" required class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-bd-green outline-none" /></label>
        <label class="space-y-1"><span class="text-sm font-medium">Slug (auto)</span><input name="slug" value="{{ old('slug') }}" placeholder="auto from title" class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-bd-green outline-none" /></label>
        <label class="space-y-1"><span class="text-sm font-medium">Icon (fa-*)</span><input name="icon" value="{{ old('icon','fa-briefcase') }}" class="w-full rounded-lg border px-3 py-2 text-sm" /></label>
        <label class="space-y-1"><span class="text-sm font-medium">Badge</span><input name="badge" value="{{ old('badge') }}" placeholder="Popular, New, etc" class="w-full rounded-lg border px-3 py-2 text-sm" /></label>
    </div>
    <label class="space-y-1 block"><span class="text-sm font-medium">Short description</span><input name="short_description" value="{{ old('short_description') }}" class="w-full rounded-lg border px-3 py-2 text-sm" /></label>
    <label class="space-y-1 block"><span class="text-sm font-medium">Full details</span><textarea name="description" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-bd-green outline-none">{{ old('description') }}</textarea></label>
    <div class="grid md:grid-cols-2 gap-4">
        <label class="space-y-1"><span class="text-sm font-medium">Price (৳) *</span><input name="price" type="number" step="0.01" min="0" value="{{ old('price',0) }}" required class="w-full rounded-lg border px-3 py-2 text-sm" /></label>
        <label class="space-y-1"><span class="text-sm font-medium">Compare price (৳)</span><input name="compare_price" type="number" step="0.01" min="0" value="{{ old('compare_price') }}" class="w-full rounded-lg border px-3 py-2 text-sm" /></label>
        <label class="space-y-1"><span class="text-sm font-medium">Display order</span><input name="display_order" type="number" value="{{ old('display_order',0) }}" class="w-full rounded-lg border px-3 py-2 text-sm" /></label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured')) class="rounded" /> Featured</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',true)) class="rounded" /> Active</label>
    </div>
    <div><x-ui.image-input name="image" label="Cover Image" helperText="Upload or paste Unsplash URL" persist /></div>
    <div class="grid md:grid-cols-2 gap-4">
        <div class="space-y-2">
            <label class="text-sm font-semibold">Features (one per line)</label>
            @php $features = old('features', ['']); @endphp
            <div id="features-wrap" class="space-y-2">
                @foreach($features as $f)<input name="features[]" value="{{ $f }}" placeholder="e.g. Lifetime access" class="w-full rounded-lg border px-3 py-2 text-sm" />@endforeach
            </div>
            <button type="button" onclick="addFeature()" class="text-xs font-semibold text-bd-green">+ Add feature</button>
        </div>
        <div class="space-y-2">
            <label class="text-sm font-semibold">FAQs</label>
            <div id="faqs-wrap" class="space-y-2">
                @php $faqs = old('faqs', [['q'=>'','a'=>'']]); @endphp
                @foreach($faqs as $idx=>$faq)
                <div class="rounded-lg border p-3 space-y-2 bg-gray-50">
                    <input name="faqs[{{ $idx }}][q]" value="{{ $faq['q'] ?? '' }}" placeholder="Question" class="w-full rounded border px-3 py-2 text-sm" />
                    <input name="faqs[{{ $idx }}][a]" value="{{ $faq['a'] ?? '' }}" placeholder="Answer" class="w-full rounded border px-3 py-2 text-sm" />
                </div>
                @endforeach
            </div>
            <button type="button" onclick="addFaq()" class="text-xs font-semibold text-bd-green">+ Add FAQ</button>
        </div>
    </div>
</div>

<div class="flex justify-end gap-3">
    <a href="{{ route('dashboard.services.index') }}" class="rounded-lg bg-gray-100 px-6 py-2.5 text-sm font-medium">Cancel</a>
    <button class="rounded-lg bg-bd-green px-6 py-2.5 text-sm font-bold text-white hover:bg-bd-green-dark">Create Service</button>
</div>
</form>
</div>
@push('scripts')
<script>
function addFeature(){ const w=document.getElementById('features-wrap'); const i=document.createElement('input'); i.name='features[]'; i.placeholder='Feature'; i.className='w-full rounded-lg border px-3 py-2 text-sm'; w.appendChild(i); }
function addFaq(){ const w=document.getElementById('faqs-wrap'); const idx=w.children.length; w.insertAdjacentHTML('beforeend', `<div class="rounded-lg border p-3 space-y-2 bg-gray-50"><input name="faqs[${idx}][q]" placeholder="Question" class="w-full rounded border px-3 py-2 text-sm" /><input name="faqs[${idx}][a]" placeholder="Answer" class="w-full rounded border px-3 py-2 text-sm" /></div>`); }
</script>
@endpush
@endsection
