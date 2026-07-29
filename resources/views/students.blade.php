@extends('layouts.frontend')

@section('title', ($page ? $page->getContent('page_title', 'শিক্ষার্থী') : 'শিক্ষার্থী') . ' - Dhaka IT Institute')

@section('content')
    @php
        // Provide a local fallback so the view never throws if controller doesn't pass $primaryColor
        $primaryColor = $primaryColor ?? '#3d59f9';
    @endphp

    <!-- Page Header -->
    <section class="hero hero--solid hero--dark">
        <div class="hero-inner max-w-7xl mx-auto px-4">
            <h1 class="text-3xl md:text-4xl hero-title text-white text-center mb-2">{{ $page ? $page->getContent('page_title', 'শিক্ষার্থী তথ্য') : 'শিক্ষার্থী তথ্য' }}</h1>
            <p class="text-white text-center opacity-90">{{ $page ? $page->getContent('page_subtitle', 'আমাদের শিক্ষার্থীদের সম্পর্কিত তথ্য ও পরিসংখ্যান') : 'আমাদের শিক্ষার্থীদের সম্পর্কিত তথ্য ও পরিসংখ্যান' }}</p>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">{{ $page ? $page->getContent('stats_title', 'শিক্ষার্থী পরিসংখ্যান') : 'শিক্ষার্থী পরিসংখ্যান' }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
                <!-- Stat 1 -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center hover:shadow-xl transition-all">
                    <div class="text-5xl font-bold text-[{{ $primaryColor }}] mb-2">{{ $totalStudents > 0 ? $totalStudents . '+' : '০' }}</div>
                    <p class="text-gray-600 font-semibold">{{ $page ? $page->getContent('total_students_label', 'মোট শিক্ষার্থী') : 'মোট শিক্ষার্থী' }}</p>
                </div>

                <!-- Stat 2 -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center hover:shadow-xl transition-all">
                    <div class="text-5xl font-bold text-[{{ $primaryColor }}] mb-2">{{ $maleStudents }}</div>
                    <p class="text-gray-600 font-semibold">{{ $page ? $page->getContent('male_students_label', 'ছাত্র') : 'ছাত্র' }}</p>
                </div>

                <!-- Stat 3 -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center hover:shadow-xl transition-all">
                    <div class="text-5xl font-bold text-[{{ $primaryColor }}] mb-2">{{ $femaleStudents }}</div>
                    <p class="text-gray-600 font-semibold">{{ $page ? $page->getContent('female_students_label', 'ছাত্রী') : 'ছাত্রী' }}</p>
                </div>

                <!-- Stat 4 -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center hover:shadow-xl transition-all">
                    <div class="text-5xl font-bold text-[{{ $primaryColor }}] mb-2">{{ $attendanceRate }}%</div>
                    <p class="text-gray-600 font-semibold">{{ $page ? $page->getContent('attendance_rate_label', 'উপস্থিতি হার') : 'উপস্থিতি হার' }}</p>
                </div>
            </div>

            <!-- Class-wise Distribution -->
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">{{ $page ? $page->getContent('class_distribution_title', 'শ্রেণীভিত্তিক শিক্ষার্থী সংখ্যা') : 'শ্রেণীভিত্তিক শিক্ষার্থী সংখ্যা' }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                    $colorMap = [
                        'Class VI' => 'blue',
                        'Class VII' => 'green',
                        'Class VIII' => 'purple',
                        'Class IX' => 'yellow',
                        'Class X' => 'red',
                        'Class XI' => 'indigo',
                        'Class XII' => 'pink',
                    ];
                    $defaultColors = ['blue', 'green', 'purple', 'yellow', 'red', 'indigo', 'pink', 'emerald'];
                @endphp

                @forelse($classDistribution as $index => $classData)
                    @php
                        $color = $colorMap[$classData->class] ?? $defaultColors[$index % count($defaultColors)];
                    @endphp
                    <div class="bg-white rounded-lg p-6 border-l-4 border-{{ $color }}-500 shadow-md hover:shadow-lg transition-shadow">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">{{ $classData->class }}</h3>
                            </div>
                            <div class="text-3xl font-bold text-{{ $color }}-600">{{ $classData->count }}</div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <p class="text-gray-500 text-lg">কোনো শিক্ষার্থী তথ্য পাওয়া যায়নি</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Student Activities -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">{{ $page ? $page->getContent('activities_title', 'শিক্ষার্থীদের কার্যক্রম') : 'শিক্ষার্থীদের কার্যক্রম' }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Activity 1 -->
                <div class="border bg-linear-to-br from-blue-50 to-purple-50 rounded-lg p-6 shadow-md">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4 text-white bg-primary">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $page ? $page->getContent('activity1_title', 'একাডেমিক কার্যক্রম') : 'একাডেমিক কার্যক্রম' }}</h3>
                    <p class="text-gray-600">{{ $page ? $page->getContent('activity1_text', 'নিয়মিত ক্লাস, পরীক্ষা, এবং শিক্ষা সহায়ক কার্যক্রম।') : 'নিয়মিত ক্লাস, পরীক্ষা, এবং শিক্ষা সহায়ক কার্যক্রম।' }}</p>
                </div>

                <!-- Activity 2 -->
                <div class="bg-linear-to-br from-green-50 to-blue-50 rounded-lg p-6 shadow-md">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4 text-white bg-primary">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $page ? $page->getContent('activity2_title', 'সাংস্কৃতিক কার্যক্রম') : 'সাংস্কৃতিক কার্যক্রম' }}</h3>
                    <p class="text-gray-600">{{ $page ? $page->getContent('activity2_text', 'বিতর্ক, আবৃত্তি, নাটক, সংগীত ইত্যাদি।') : 'বিতর্ক, আবৃত্তি, নাটক, সংগীত ইত্যাদি।' }}</p>
                </div>

                <!-- Activity 3 -->
                <div class="bg-linear-to-br from-yellow-50 to-orange-50 rounded-lg p-6 shadow-md">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4 text-white bg-primary">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $page ? $page->getContent('activity3_title', 'ক্রীড়া কার্যক্রম') : 'ক্রীড়া কার্যক্রম' }}</h3>
                    <p class="text-gray-600">{{ $page ? $page->getContent('activity3_text', 'ফুটবল, ক্রিকেট, ব্যাডমিন্টন এবং অন্যান্য খেলা।') : 'ফুটবল, ক্রিকেট, ব্যাডমিন্টন এবং অন্যান্য খেলা।' }}</p>
                </div>
            </div>
        </div>
    </section>
@endsection
