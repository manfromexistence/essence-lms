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
            @foreach($services as $index => $service)
                @php
                    $serviceImages = [
                        'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=85',
                        'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1200&q=85',
                        'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1200&q=85',
                        'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1200&q=85',
                        'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?auto=format&fit=crop&w=1200&q=85',
                        'https://images.unsplash.com/photo-1553877522-43269d4ea984?auto=format&fit=crop&w=1200&q=85',
                    ];
                    $serviceImage = $service['image'] ?? ($serviceImages[$index % count($serviceImages)]);
                @endphp
                <article class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-green-200 hover:shadow-xl">
                    <div class="relative h-48 overflow-hidden bg-green-900">
                        <img src="{{ $serviceImage }}" alt="{{ $service['title'] }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                        <span class="absolute bottom-4 left-5 flex h-11 w-11 items-center justify-center rounded-xl bg-white text-xl text-green-800 shadow"><i class="fa-solid {{ $service['icon'] ?? 'fa-check' }}"></i></span>
                    </div>
                    <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-900">{{ $service['title'] }}</h2>
                    <p class="mt-3 min-h-20 leading-7 text-gray-600">{{ $service['description'] }}</p>
                    <div class="mt-5 flex gap-3">
                        <a href="{{ route('admission.create') }}" class="flex-1 rounded-xl bg-green-800 px-4 py-3 text-center text-sm font-bold text-white transition hover:bg-black">Buy / Enrol</a>
                        <button type="button" onclick="addServiceToCart('{{ addslashes($service['title']) }}')" class="rounded-xl border border-green-800 px-4 py-3 text-sm font-bold text-green-800 transition hover:bg-green-50">Add to cart</button>
                    </div>
                    </div>
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

@push('scripts')
<script>
    function addServiceToCart(name) {
        const cart = JSON.parse(localStorage.getItem('dii_service_cart') || '[]');
        if (!cart.includes(name)) cart.push(name);
        localStorage.setItem('dii_service_cart', JSON.stringify(cart));
        alert(`${name} added to your shortlist.`);
    }
</script>
@endpush
