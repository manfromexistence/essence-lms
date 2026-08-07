@extends('layouts.frontend')

@section('title', ($page ? $page->getContent('page_title', 'যোগাযোগ করুন') : 'যোগাযোগ করুন') . ' - Dhaka IT Institute')

@section('content')
    <!-- Page Header -->
    <section class="hero hero--solid hero--dark">
        <div class="hero-inner max-w-7xl mx-auto px-4">
            <h1 class="text-3xl md:text-4xl hero-title text-white text-center mb-2">{{ $page ? $page->getContent('page_title', 'যোগাযোগ করুন') : 'যোগাযোগ করুন' }}</h1>
            <p class="text-white text-center opacity-90">{{ $page ? $page->getContent('page_subtitle', 'আমাদের সাথে যোগাযোগ করার বিভিন্ন মাধ্যম') : 'আমাদের সাথে যোগাযোগ করার বিভিন্ন মাধ্যম' }}</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Contact Form -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ $page ? $page->getContent('form_title', 'বার্তা পাঠান') : 'বার্তা পাঠান' }}</h2>
                    @if(session('success'))
                        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('contact.submit') }}" method="POST" class="space-y-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">আপনার নাম</label>
                                <input type="text" name="name" value="{{ old('name') }}" placeholder="নাম লিখুন" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[{{ $primaryColor ?? '#3d59f9' }}] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">ইমেইল ঠিকানা</label>
                                <input type="email" name="email" value="{{ old('email') }}" placeholder="ইমেইল লিখুন" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[{{ $primaryColor ?? '#3d59f9' }}] focus:border-transparent">
                            </div>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">বিষয়</label>
                            <input type="text" name="subject" value="{{ old('subject') }}" placeholder="বিষয় লিখুন" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[{{ $primaryColor ?? '#3d59f9' }}] focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">বার্তা</label>
                            <textarea name="message" rows="5" placeholder="আপনার বার্তাটি এখানে লিখুন" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[{{ $primaryColor ?? '#3d59f9' }}] focus:border-transparent">{{ old('message') }}</textarea>
                        </div>
                        <button type="submit"
                            class="w-full bg-primary hover:opacity-90 text-white font-bold py-3 rounded-lg transition-all shadow-lg hover:shadow-xl">
                            বার্তা পাঠান
                        </button>
                    </form>
                </div>

                <!-- Contact Info & Map -->
                <div class="space-y-8">
                    <!-- Info Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-primary">
                            <div
                                class="w-12 h-12 bg-primary bg-opacity-10 rounded-full flex items-center justify-center mb-4 text-primary">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 mb-2">ঠিকানা</h3>
                            <p class="text-gray-600">{{ $page ? $page->getContent('address', 'House #5 (2nd floor), Road #8, Block-C, Section-10, Mirpur-10, Dhaka-1216') : 'House #5 (2nd floor), Road #8, Block-C, Section-10, Mirpur-10, Dhaka-1216' }}</p>
                        </div>

                        <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-primary">
                            <div
                                class="w-12 h-12 bg-primary bg-opacity-10 rounded-full flex items-center justify-center mb-4 text-primary">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 mb-2">ফোন</h3>
                            <p class="text-gray-600">{!! nl2br(e($page ? $page->getContent('phone', '+880 1682-715576') : '+880 1682-715576')) !!}</p>
                        </div>
                    </div>

                    <!-- Email Card -->
                    @if($page && $page->getContent('email'))
                    <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-blue-500">
                        <div class="w-12 h-12 bg-blue-500 bg-opacity-10 rounded-full flex items-center justify-center mb-4 text-blue-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">ইমেইল</h3>
                        <p class="text-gray-600">{{ $page->getContent('email') }}</p>
                    </div>
                    @endif

                    <!-- Google Map -->
                    <div class="bg-white p-2 rounded-lg shadow-lg aspect-video overflow-hidden border">
                        <iframe
                            src="{{ $page ? $page->getContent('map_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d116347.16843475968!2d89.9238384!3d24.9193214!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39fdfe6e1476f535%3A0xe5a1c31276a66d0b!2sJamalpur%20Sadar%20Upazila!5e0!3m2!1sen!2sbd!4v1705220000000!5m2!1sen!2sbd') : 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d116347.16843475968!2d89.9238384!3d24.9193214!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39fdfe6e1476f535%3A0xe5a1c31276a66d0b!2sJamalpur%20Sadar%20Upazila!5e0!3m2!1sen!2sbd!4v1705220000000!5m2!1sen!2sbd' }}"
                            class="w-full h-full border-0" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
