@extends('layouts.frontend')

@section('title', ($page ? $page->getContent('page_title', 'Services') : 'Services') . ' - Dhaka IT Institute')

@section('content')
<section class="hero hero--solid hero--dark">
    <div class="hero-inner text-center">
        <h1 class="hero-title">{{ $page ? $page->getContent('page_title', 'Our services') : 'Our services' }}</h1>
        <p class="hero-subtitle mx-auto mt-4 max-w-2xl text-lg md:text-xl">{{ $page ? $page->getContent('page_subtitle', 'Training, digital solutions and practical support for students, freelancers and growing businesses.') : 'Training, digital solutions and practical support for students, freelancers and growing businesses.' }}</p>
    </div>
</section>

@if($services->isNotEmpty())
<section class="bg-white py-10 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <div class="flex gap-4 justify-center text-sm font-semibold text-gray-500">
            <span><strong class="text-gray-900">{{ $services->count() }}</strong> services available</span>
            <span>•</span>
            <span><strong class="text-gray-900">Instant</strong> enrollment</span>
            <span>•</span>
            <span><strong class="text-gray-900">Lifetime</strong> support</span>
        </div>
    </div>
</section>

<section class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($services as $service)
            <article class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl flex flex-col">
                <div class="relative h-52 overflow-hidden bg-gray-100">
                    <img src="{{ $service->image_url }}" alt="{{ $service->title }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105" loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                    @if($service->badge)<span class="absolute left-4 top-4 rounded-full bg-black px-3 py-1 text-xs font-bold text-white shadow">{{ $service->badge }}</span>@endif
                    @if($service->discount_percent)<span class="absolute right-4 top-4 rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white shadow">-{{ $service->discount_percent }}%</span>@endif
                    @if($service->icon)<span class="absolute bottom-4 left-4 flex h-11 w-11 items-center justify-center rounded-xl bg-white text-xl text-primary shadow"><i class="fa-solid {{ $service->icon }}"></i></span>@endif
                </div>
                <div class="flex-1 p-5">
                    <h2 class="text-xl font-bold text-gray-900">{{ $service->title }}</h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600">{{ $service->short_description ?: Str::limit(strip_tags($service->description), 120) }}</p>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-gray-900">৳{{ number_format($service->price, 0) }}</span>
                        @if($service->compare_price && $service->compare_price > $service->price)
                            <span class="text-sm text-gray-400 line-through">৳{{ number_format($service->compare_price, 0) }}</span>
                        @endif
                    </div>
                    @if($service->features)
                    <ul class="mt-3 space-y-1">
                        @foreach(array_slice($service->features, 0, 4) as $feature)
                        <li class="flex items-start gap-2 text-sm text-gray-600"><i class="fa-solid fa-check mt-0.5 text-xs text-green-600"></i> {{ $feature }}</li>
                        @endforeach
                    </ul>
                    @endif
                </div>
                <div class="p-5 pt-0 flex gap-2">
                    <button type="button" onclick="openServiceModal({{ $service->id }})" class="flex-1 rounded-xl border border-gray-200 px-4 py-2.5 text-center text-sm font-bold text-gray-700 transition hover:bg-gray-50">Details</button>
                    <button type="button" onclick="addToCart('{{ addslashes($service->title) }}', {{ $service->price }})" class="flex-1 rounded-xl bg-primary px-4 py-2.5 text-center text-sm font-bold text-white transition hover:opacity-90">Buy / Enrol</button>
                </div>

                {{-- Hidden modal content --}}
                <div id="sm-{{ $service->id }}" class="hidden">
                    <div class="relative h-48 overflow-hidden rounded-t-2xl bg-gray-100">
                        <img src="{{ $service->image_url }}" alt="{{ $service->title }}" class="h-full w-full object-cover">
                        <div class="absolute bottom-4 left-5 flex h-11 w-11 items-center justify-center rounded-xl bg-white text-xl text-primary shadow"><i class="fa-solid {{ $service->icon ?? 'fa-check' }}"></i></div>
                    </div>
                    <div class="p-6">
                        <h2 class="text-2xl font-black text-gray-900">{{ $service->title }}</h2>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="text-3xl font-black text-gray-900">৳{{ number_format($service->price, 0) }}</span>
                            @if($service->compare_price && $service->compare_price > $service->price)<span class="text-lg text-gray-400 line-through">৳{{ number_format($service->compare_price, 0) }}</span>@endif
                        </div>
                        @if($service->description)<div class="mt-4 prose prose-sm max-w-none text-gray-700">{!! nl2br(e($service->description)) !!}</div>@endif
                        @if($service->features)
                        <div class="mt-5">
                            <h4 class="font-semibold text-gray-900">What you get</h4>
                            <ul class="mt-2 grid grid-cols-2 gap-2">
                                @foreach($service->features as $feature)
                                <li class="flex items-center gap-2 text-sm text-gray-600"><i class="fa-solid fa-check text-xs text-green-600"></i> {{ $feature }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        @if($service->faqs)
                        <div class="mt-5">
                            <h4 class="font-semibold text-gray-900">FAQs</h4>
                            <div class="mt-2 space-y-3">
                                @foreach($service->faqs as $faq)
                                <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
                                    <p class="text-sm font-semibold text-gray-900">{{ $faq['q'] ?? '' }}</p>
                                    <p class="mt-1 text-sm text-gray-600">{{ $faq['a'] ?? '' }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        <div class="mt-6 flex gap-3">
                            <button type="button" onclick="addToCart('{{ addslashes($service->title) }}', {{ $service->price }}); closeServiceModal()" class="flex-1 rounded-xl bg-primary px-6 py-3 text-center font-bold text-white transition hover:opacity-90">Add to Cart – ৳{{ number_format($service->price, 0) }}</button>
                            <a href="{{ route('admission.create') }}" class="flex-1 rounded-xl border border-gray-200 px-6 py-3 text-center font-bold text-gray-700 transition hover:bg-gray-50">Direct Enrol</a>
                        </div>
                    </div>
                </div>
            </article>
            @endforeach
        </div>
    </div>
</section>

{{-- Service detail modal --}}
<div id="service-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4" onclick="closeServiceModal(event)">
    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white shadow-2xl" onclick="event.stopPropagation()">
        <button onclick="closeServiceModal()" class="absolute right-4 top-4 z-20 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-gray-600 shadow hover:bg-white">✕</button>
        <div id="service-modal-content"></div>
    </div>
</div>

{{-- Cart sidebar --}}
<div id="cart-overlay" class="fixed inset-0 z-50 hidden bg-black/30" onclick="closeCart()"></div>
<div id="cart-panel" class="fixed right-0 top-0 z-50 h-full w-full max-w-sm translate-x-full overflow-y-auto bg-white shadow-2xl transition-transform duration-300">
    <div class="flex items-center justify-between border-b p-5">
        <h3 class="text-lg font-black text-gray-900"><i class="fa-solid fa-cart-shopping mr-2 text-primary"></i>Your Cart</h3>
        <button onclick="closeCart()" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100">✕</button>
    </div>
    <div id="cart-items" class="p-5 space-y-3"></div>
    <div id="cart-empty" class="p-5 text-center text-sm text-gray-400">Your cart is empty</div>
    <div id="cart-footer" class="hidden border-t p-5">
        <div class="flex items-baseline justify-between mb-4">
            <span class="text-sm text-gray-500">Total</span>
            <span id="cart-total" class="text-2xl font-black text-gray-900">৳0</span>
        </div>
        <a href="{{ route('admission.create') }}" class="block rounded-xl bg-primary px-6 py-3 text-center font-bold text-white transition hover:opacity-90">Proceed to Enrol</a>
    </div>
</div>
@else
<section class="bg-gray-50 py-24 text-center">
    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10"><i class="fa-solid fa-briefcase text-3xl text-primary"></i></div>
    <h2 class="text-2xl font-bold text-gray-900">No services available</h2>
    <p class="mt-2 max-w-md mx-auto text-gray-600">We are updating our service catalog. Check back soon!</p>
</section>
@endif
@endsection

@push('scripts')
<script>
let cart = JSON.parse(localStorage.getItem('dii_cart') || '[]');

function addToCart(name, price) {
    const existing = cart.find(i => i.name === name);
    if (existing) { existing.qty = (existing.qty||1)+1; }
    else { cart.push({name, price: parseFloat(price), qty:1}); }
    saveCart(); renderCart(); openCart();
    showToast(name + ' added to cart');
}

function removeFromCart(name) { cart = cart.filter(i => i.name !== name); saveCart(); renderCart(); }

function saveCart() { localStorage.setItem('dii_cart', JSON.stringify(cart)); }

function renderCart() {
    const wrap = document.getElementById('cart-items');
    const empty = document.getElementById('cart-empty');
    const footer = document.getElementById('cart-footer');
    const totalEl = document.getElementById('cart-total');
    if (!cart.length) {
        wrap.innerHTML = ''; empty.classList.remove('hidden'); footer.classList.add('hidden'); return;
    }
    empty.classList.add('hidden'); footer.classList.remove('hidden');
    let total = 0;
    wrap.innerHTML = cart.map(i => { total += i.price * (i.qty||1); return `<div class="flex items-start justify-between gap-3 rounded-xl border p-3"><div><p class="text-sm font-semibold text-gray-900">${i.name}</p><p class="text-xs text-gray-500">৳${i.price.toFixed(0)} x ${i.qty||1}</p></div><button onclick="removeFromCart('${i.name.replace(/'/g,"\\'")}')" class="text-xs text-red-500 hover:underline">Remove</button></div>`; }).join('');
    totalEl.textContent = '৳' + total.toLocaleString();
}

function openCart() { document.getElementById('cart-overlay').classList.remove('hidden'); document.getElementById('cart-panel').classList.remove('translate-x-full'); document.body.style.overflow='hidden'; }
function closeCart() { document.getElementById('cart-overlay').classList.add('hidden'); document.getElementById('cart-panel').classList.add('translate-x-full'); document.body.style.overflow=''; }

function openServiceModal(id) {
    const c = document.getElementById('sm-'+id)?.innerHTML || '';
    document.getElementById('service-modal-content').innerHTML = c;
    document.getElementById('service-modal').classList.remove('hidden');
    document.getElementById('service-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function closeServiceModal(e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('service-modal').classList.add('hidden');
    document.getElementById('service-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

function showToast(msg) {
    const t = document.createElement('div');
    t.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-50 rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-lg';
    t.textContent = msg; document.body.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity .3s'; setTimeout(() => t.remove(), 300); }, 1800);
}

document.addEventListener('DOMContentLoaded', renderCart);
</script>
@endpush
