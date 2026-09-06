<footer class="text-gray-800" style="background-color: #e9e9e9;">
    <div class="max-w-7xl mx-auto px-4 py-8 md:py-12">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
            <div>
                @php
                    $settingsService = app(\App\Services\SettingsService::class);
                    $logoUrl = $settingsService->getLogo();
                    $institutionName = $settingsService->get('institution_name', 'Dhaka IT Institute');
                @endphp
                <div class="flex items-center gap-3 mb-4">
                    <img src="{{ $logoUrl }}" alt="{{ $institutionName }}" class="h-16 md:h-20 w-auto object-contain">
                </div>
                <p class="text-primary text-sm leading-relaxed mb-4">
                    প্র্যাকটিক্যাল IT, web development, digital marketing ও freelancing training—অনলাইন এবং অফলাইন।
                </p>
                <div class="flex gap-3">
                    <a href="{{ \App\Models\Setting::getValue('facebook_url', 'https://facebook.com/dhakaitinstitute') }}" target="_blank" rel="noopener"
                        class="w-10 h-10 bg-primary hover:opacity-80 rounded-full flex items-center justify-center transition-all text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                    </a>
                    <a href="{{ \App\Models\Setting::getValue('youtube_url', 'https://youtube.com/@dhakaitinstitute') }}" target="_blank" rel="noopener" aria-label="YouTube"
                        class="w-10 h-10 bg-primary hover:opacity-80 rounded-full flex items-center justify-center transition-all text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                        </svg>
                    </a>
                </div>
            </div>

            <div>
                <h3 class="text-base md:text-lg font-bold mb-4 text-primary">দ্রুত লিংক</h3>
                <ul class="space-y-2 md:space-y-3">
                    <li><a href="{{ url('/') }}"
                            class="text-primary hover:opacity-80 transition-colors text-sm">প্রচ্ছদ</a></li>
                    <li><a href="{{ route('about') }}"
                            class="text-primary hover:opacity-80 transition-colors text-sm">পরিচিতি</a></li>
                    <li><a href="{{ route('courses') }}"
                            class="text-primary hover:opacity-80 transition-colors text-sm">কোর্স</a></li>
                    <li><a href="{{ route('services') }}"
                            class="text-primary hover:opacity-80 transition-colors text-sm">সার্ভিসেস</a></li>
                    <li><a href="{{ route('team') }}"
                            class="text-primary hover:opacity-80 transition-colors text-sm">আমাদের টিম</a></li>
                    <li><a href="{{ route('teachers') }}"
                            class="text-primary hover:opacity-80 transition-colors text-sm">শিক্ষকমণ্ডলী</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-base md:text-lg font-bold mb-4 text-primary">গুরুত্বপূর্ণ লিংক</h3>
                <ul class="space-y-2 md:space-y-3">
                    <li><a href="{{ route('certificates.verify') }}"
                            class="text-primary hover:opacity-80 transition-colors text-sm">সার্টিফিকেট যাচাই</a></li>
                    <li><a href="{{ route('contact') }}"
                            class="text-primary hover:opacity-80 transition-colors text-sm">যোগাযোগ</a></li>
                    <li><a href="{{ route('login') }}"
                            class="text-primary hover:opacity-80 transition-colors text-sm">লগইন</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-base md:text-lg font-bold mb-4 text-primary">যোগাযোগ</h3>
                <ul class="space-y-2 md:space-y-3 text-sm text-primary">
                    <li>ঠিকানা: House #5, Road #8, Block-C, Section-10, Mirpur-10, Dhaka-1216</li>
                    <li>ফোন: +880 1682-715570</li>
                    <li>ইমেইল: dhakaitinstitute@gmail.com</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t border-primary">
        <div class="max-w-7xl mx-auto px-4 py-4 md:py-6 text-center">
            <p class="text-primary text-xs md:text-sm">© {{ date('Y') }} Dhaka IT Institute। সর্বস্বত্ব সংরক্ষিত।</p>
            <p class="text-primary text-xs md:text-sm mt-1">{{ \App\Models\Setting::getValue('footer_text', 'Dhaka IT Institute — Let’s Build Your Dream') }}</p>
        </div>
    </div>
</footer>
