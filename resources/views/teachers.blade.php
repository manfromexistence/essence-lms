@extends('layouts.frontend')

@section('title', ($page ? $page->getContent('page_title', 'শিক্ষক') : 'শিক্ষক') . ' - Dhaka IT Institute')

@section('content')
    <!-- Page Header -->
    <section class="hero hero--gradient hero--dark">
        <div class="max-w-7xl mx-auto px-4">
            <h1 class="text-3xl md:text-4xl font-bold text-white text-center mb-2">{{ $page ? $page->getContent('page_title', 'আমাদের শিক্ষকমণ্ডলী') : 'আমাদের শিক্ষকমণ্ডলী' }}</h1>
            <p class="text-white text-center opacity-90">{{ $page ? $page->getContent('page_subtitle', 'অভিজ্ঞ ও দক্ষ শিক্ষকদের তালিকা') : 'অভিজ্ঞ ও দক্ষ শিক্ষকদের তালিকা' }}</p>
        </div>
    </section>

    <!-- Teachers Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Teacher 1 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all">
                    <div class="h-64 bg-gradient-to-br from-blue-200 to-blue-300">
                        <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400" alt="Teacher"
                            class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">মোঃ আব্দুল করিম</h3>
                        <p class="text-primary font-semibold mb-2">প্রধান শিক্ষক</p>
                        <p class="text-gray-600 text-sm mb-3">এম.এ (বাংলা), বি.এড</p>
                        <p class="text-gray-600 text-sm">শিক্ষকতার অভিজ্ঞতা: ২৫ বছর</p>
                    </div>
                </div>

                <!-- Teacher 2 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all">
                    <div class="h-64 bg-gradient-to-br from-purple-200 to-purple-300">
                        <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400" alt="Teacher"
                            class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">সুমাইয়া আক্তার</h3>
                        <p class="text-primary font-semibold mb-2">সহকারী শিক্ষক (ইংরেজি)</p>
                        <p class="text-gray-600 text-sm mb-3">এম.এ (ইংরেজি), বি.এড</p>
                        <p class="text-gray-600 text-sm">শিক্ষকতার অভিজ্ঞতা: ১৫ বছর</p>
                    </div>
                </div>

                <!-- Teacher 3 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all">
                    <div class="h-64 bg-gradient-to-br from-green-200 to-green-300">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400" alt="Teacher"
                            class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">ড. রাজিব হোসেন</h3>
                        <p class="text-primary font-semibold mb-2">সহকারী শিক্ষক (গণিত)</p>
                        <p class="text-gray-600 text-sm mb-3">এম.এস.সি (গণিত), পি.এইচ.ডি</p>
                        <p class="text-gray-600 text-sm">শিক্ষকতার অভিজ্ঞতা: ২০ বছর</p>
                    </div>
                </div>

                <!-- Teacher 4 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all">
                    <div class="h-64 bg-gradient-to-br from-yellow-200 to-yellow-300">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400" alt="Teacher"
                            class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">নাসরিন সুলতানা</h3>
                        <p class="text-primary font-semibold mb-2">সহকারী শিক্ষক (পদার্থবিজ্ঞান)</p>
                        <p class="text-gray-600 text-sm mb-3">এম.এস.সি (পদার্থবিজ্ঞান), বি.এড</p>
                        <p class="text-gray-600 text-sm">শিক্ষকতার অভিজ্ঞতা: ১২ বছর</p>
                    </div>
                </div>

                <!-- Teacher 5 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all">
                    <div class="h-64 bg-gradient-to-br from-red-200 to-red-300">
                        <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400" alt="Teacher"
                            class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">মোঃ জাহিদুল ইসলাম</h3>
                        <p class="text-primary font-semibold mb-2">সহকারী শিক্ষক (রসায়ন)</p>
                        <p class="text-gray-600 text-sm mb-3">এম.এস.সি (রসায়ন), বি.এড</p>
                        <p class="text-gray-600 text-sm">শিক্ষকতার অভিজ্ঞতা: ১৮ বছর</p>
                    </div>
                </div>

                <!-- Teacher 6 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all">
                    <div class="h-64 bg-gradient-to-br from-pink-200 to-pink-300">
                        <img src="https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=400" alt="Teacher"
                            class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">ফারহানা ইয়াসমিন</h3>
                        <p class="text-primary font-semibold mb-2">সহকারী শিক্ষক (জীববিজ্ঞান)</p>
                        <p class="text-gray-600 text-sm mb-3">এম.এস.সি (উদ্ভিদবিজ্ঞান), বি.এড</p>
                        <p class="text-gray-600 text-sm">শিক্ষকতার অভিজ্ঞতা: ১০ বছর</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
