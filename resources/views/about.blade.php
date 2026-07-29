@extends('layouts.frontend')

@section('title', ($page ? $page->getContent('page_title', 'প্রতিষ্ঠান পরিচিতি') : 'প্রতিষ্ঠান পরিচিতি') . ' - Dhaka IT Institute')

@section('content')
    <!-- Page Header -->
    <section class="bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto px-4">
            <h1 class="text-4xl font-bold text-gray-900 text-center mb-4">{{ $page ? $page->getContent('page_title', 'প্রতিষ্ঠান পরিচিতি') : 'প্রতিষ্ঠান পরিচিতি' }}</h1>
            <div class="flex items-center justify-center gap-2 text-sm">
                <a href="{{ url('/') }}" class="text-gray-600 hover:text-primary flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                    মূলপাতা
                </a>
                <span class="text-gray-400">></span>
                <span class="text-primary font-medium">{{ $page ? $page->getContent('page_title', 'প্রতিষ্ঠান পরিচিতি') : 'প্রতিষ্ঠান পরিচিতি' }}</span>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-2 gap-8 items-center">
                <!-- Image -->
                <div>
                    <img src="{{ $page ? $page->getContent('about_image', 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=800&q=80') : 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=800&q=80' }}" alt="Dhaka IT Institute training"
                        class="rounded-lg shadow-lg w-full h-auto object-cover">
                </div>

                <!-- Content -->
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ $page ? $page->getContent('about_title', 'প্রতিষ্ঠান সম্পর্কে') : 'প্রতিষ্ঠান সম্পর্কে' }}</h2>
                    <div class="w-16 h-1 bg-primary mb-6"></div>

                    <div class="text-gray-700 leading-relaxed space-y-4">
                        {!! nl2br(e($page ? $page->getContent('about_text', 'Dhaka IT Institute মিরপুর-১০, ঢাকায় অবস্থিত একটি প্র্যাকটিক্যাল IT ও freelancing training center।') : 'Dhaka IT Institute মিরপুর-১০, ঢাকায় অবস্থিত একটি প্র্যাকটিক্যাল IT ও freelancing training center।')) !!}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">আমাদের প্রশিক্ষণের বৈশিষ্ট্য</h2>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-8">
                @php
                    $stats = [
                        ['val' => $page ? $page->getContent('stats_students', 'অনলাইন') : 'অনলাইন', 'label' => 'অনলাইন ক্লাস'],
                        ['val' => $page ? $page->getContent('stats_teachers', 'অফলাইন') : 'অফলাইন', 'label' => 'ক্লাসরুম ট্রেনিং'],
                        ['val' => $page ? $page->getContent('stats_staff', 'প্রজেক্ট') : 'প্রজেক্ট', 'label' => 'হাতে-কলমে কাজ'],
                        ['val' => $page ? $page->getContent('stats_rooms', 'সাপোর্ট') : 'সাপোর্ট', 'label' => 'দীর্ঘমেয়াদি সহায়তা'],
                        ['val' => $page ? $page->getContent('stats_buildings', 'ক্যারিয়ার') : 'ক্যারিয়ার', 'label' => 'মার্কেটপ্লেস গাইডলাইন'],
                    ];
                @endphp
                @foreach ($stats as $stat)
                    <div class="flex flex-col items-center">
                        <div
                            class="w-32 h-32 rounded-full border-4 border-gray-200 flex flex-col items-center justify-center mb-4 bg-white">
                            <span class="text-4xl font-bold text-primary">{{ $stat['val'] }}</span>
                        </div>
                        <p class="text-gray-700 font-medium text-center">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-2 gap-12 items-start">
                <!-- Mission -->
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ $page ? $page->getContent('mission_title', 'প্রতিষ্ঠানের মিশন') : 'প্রতিষ্ঠানের মিশন' }}</h2>
                    <div class="w-16 h-1 bg-primary mb-6"></div>
                    <div class="text-gray-700 leading-relaxed">
                        {!! nl2br(e($page ? $page->getContent('mission_text', 'শিক্ষার্থীদের বাস্তব কাজ, মার্কেটপ্লেস এবং সফল project delivery-এর জন্য প্রস্তুত করা।') : 'শিক্ষার্থীদের বাস্তব কাজ, মার্কেটপ্লেস এবং সফল project delivery-এর জন্য প্রস্তুত করা।')) !!}
                    </div>
                </div>

                <!-- Vision -->
                <div>
                    <div class="mb-8">
                        <img src="{{ $page ? $page->getContent('vision_image', 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=500&q=80') : 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=500&q=80' }}" alt="Students"
                            class="rounded-lg shadow-lg w-full h-auto object-cover">
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ $page ? $page->getContent('vision_title', 'প্রতিষ্ঠানের ভিশন') : 'প্রতিষ্ঠানের ভিশন' }}</h2>
                    <div class="w-16 h-1 bg-primary mb-6"></div>
                    <div class="text-gray-700 leading-relaxed">
                        {!! nl2br(e($page ? $page->getContent('vision_text', 'প্র্যাকটিক্যাল IT দক্ষতা ও পেশাদার মানসিকতার মাধ্যমে কর্মসংস্থান এবং freelancing-এর জন্য আত্মবিশ্বাসী মানুষ তৈরি করা।') : 'প্র্যাকটিক্যাল IT দক্ষতা ও পেশাদার মানসিকতার মাধ্যমে কর্মসংস্থান এবং freelancing-এর জন্য আত্মবিশ্বাসী মানুষ তৈরি করা।')) !!}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
