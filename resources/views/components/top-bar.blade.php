@php
    $settingsService = app(\App\Services\SettingsService::class);
    $primaryColor = $settingsService->get('theme_primary_color', '#3d59f9');
@endphp

<div class="text-white py-2 px-4 text-xs md:text-sm font-medium bg-primary">
    <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-2 md:gap-4">
        <div class="max-w-xl overflow-hidden w-full md:w-auto">
            <div class="ticker">
                <span>মিরপুর-১০, ঢাকায় অনলাইন ও অফলাইন IT এবং freelancing training.</span>
                @if(Request::is('/'))
                    <span class="mx-4">★</span>
                    <span>২০২৬ সালের এসএসসি পরীক্ষার্থীদের জন্য বিশেষ নোটিশ।</span>
                    <span class="mx-4">★</span>
                    <span>নতুন শিক্ষাবর্ষে ভর্তি চলছে।</span>
                    <span class="mx-4">★</span>
                @endif
                <!-- Duplicate content for seamless loop -->
                <span>মিরপুর-১০, ঢাকায় অনলাইন ও অফলাইন IT এবং freelancing training.</span>
                @if(Request::is('/'))
                    <span class="mx-4">★</span>
                    <span>২০২৬ সালের এসএসসি পরীক্ষার্থীদের জন্য বিশেষ নোটিশ।</span>
                    <span class="mx-4">★</span>
                    <span>নতুন শিক্ষাবর্ষে ভর্তি চলছে।</span>
                    <span class="mx-4">★</span>
                @endif
            </div>
        </div>
        <div class="shrink-0 flex flex-wrap items-center justify-center gap-2 md:gap-4 text-xs">
            <span>EIIN: <strong>123354</strong></span>
            <span class="hidden sm:inline">School code: <strong>123456</strong></span>
            <span class="hidden lg:inline">Reg. No: <strong>12334455617</strong></span>
        </div>
    </div>
</div>
