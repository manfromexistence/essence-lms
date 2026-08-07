@php
    $settingsService = app(\App\Services\SettingsService::class);
    $primaryColor = $settingsService->get('theme_primary_color', '#3d59f9');
    $primaryForeground = $settingsService->get('theme_primary_foreground', '#ffffff');
@endphp

<header class="bg-white border-b sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                @php
                    $settingsService = app(\App\Services\SettingsService::class);
                    $logoUrl = $settingsService->getLogo();
                    $institutionName = $settingsService->get('institution_name', 'Dhaka IT Institute');
                @endphp
                <a href="{{ url('/') }}">
                    <img src="{{ $logoUrl }}" alt="{{ $institutionName }}"
                        class="h-10 md:h-12 w-auto object-contain cursor-pointer hover:opacity-80 transition">
                </a>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center gap-0.5">
                <a href="{{ url('/') }}"
                    class="px-4 py-2 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::is('/') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">
                    হোম
                </a>
                <a href="{{ route('courses') }}"
                    class="px-4 py-2 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::routeIs('courses') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">কোর্স</a>
                <a href="{{ route('services') }}"
                    class="px-4 py-2 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::routeIs('services') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">সার্ভিসেস</a>
                <a href="{{ route('team') }}"
                    class="px-4 py-2 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::routeIs('team') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">টিম</a>
                {{-- Teacher link hidden for now
                <a href="{{ route('teachers') }}"
                    class="px-4 py-2 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::routeIs('teachers') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">শিক্ষক</a>
                --}}
                <a href="{{ route('about') }}"
                    class="px-4 py-2 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::routeIs('about') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">পরিচিতি</a>
                <a href="{{ route('contact') }}"
                    class="px-4 py-2 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::routeIs('contact') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">যোগাযোগ</a>
                @auth
                    @if(Auth::user()->isStudent())
                        <a href="{{ route('student.courses') }}"
                            class="ml-2 inline-flex min-h-10 items-center justify-center rounded-md bg-black px-4 py-2 text-center text-sm font-semibold leading-none text-white transition hover:bg-green-800">
                            Enroll in Course
                        </a>
                    @endif
                @else
                    <a href="{{ route('admission.create') }}"
                        class="ml-2 inline-flex h-10 items-center justify-center rounded-md bg-black px-5 text-sm font-semibold leading-none text-white transition hover:bg-green-800">
                        Admission
                    </a>
                @endauth
            </nav>

            <!-- Desktop Auth Button -->
            <div class="hidden md:block">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="px-4 py-2 rounded-md text-sm font-medium hover:opacity-90 transition text-center"
                        style="background-color: {{ $primaryColor }}; color: {{ $primaryForeground }}">
                        View Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="px-4 py-2 rounded-md text-sm font-medium hover:opacity-90 transition text-center"
                        style="background-color: {{ $primaryColor }}; color: {{ $primaryForeground }}">
                        Login
                    </a>
                @endauth
            </div>

            <!-- Mobile Hamburger Button -->
            <button id="mobileMenuBtn" class="md:hidden p-2 rounded-md hover:bg-gray-100 transition"
                style="color: {{ $primaryColor }}" onclick="toggleMobileMenu()">
                <svg id="hamburgerIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg id="closeIcon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Mobile Navigation Menu -->
        <div id="mobileMenu" class="hidden md:hidden mt-4 pb-4 border-t pt-4">
            <nav class="flex flex-col space-y-2">
                <a href="{{ url('/') }}"
                    class="px-4 py-3 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::is('/') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">
                    হোম
                </a>
                <a href="{{ route('courses') }}"
                    class="px-4 py-3 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::routeIs('courses') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">কোর্স</a>
                <a href="{{ route('services') }}"
                    class="px-4 py-3 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::routeIs('services') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">সার্ভিসেস</a>
                <a href="{{ route('team') }}"
                    class="px-4 py-3 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::routeIs('team') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">টিম</a>
                {{-- Teacher link hidden for now
                <a href="{{ route('teachers') }}"
                    class="px-4 py-3 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::routeIs('teachers') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">শিক্ষক</a>
                --}}
                <a href="{{ route('about') }}"
                    class="px-4 py-3 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::routeIs('about') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">পরিচিতি</a>
                <a href="{{ route('contact') }}"
                    class="px-4 py-3 rounded-md text-md font-medium hover:opacity-80 transition"
                    style="{{ Request::routeIs('contact') ? 'background-color: ' . $primaryColor . '; color: ' . $primaryForeground : 'color: ' . $primaryColor }}">যোগাযোগ</a>
                @auth
                    @if(Auth::user()->isStudent())
                        <a href="{{ route('student.courses') }}"
                            class="inline-flex min-h-11 items-center justify-center rounded-md bg-black px-4 py-3 text-center text-md font-semibold leading-none text-white transition hover:bg-green-800">
                            Enroll in Course
                        </a>
                    @endif
                @else
                    <a href="{{ route('admission.create') }}"
                        class="inline-flex h-11 items-center justify-center rounded-md bg-black px-5 text-center text-md font-semibold leading-none text-white transition hover:bg-green-800">
                        Admission
                    </a>
                @endauth
                
                <!-- Mobile Auth Button -->
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="px-4 py-3 rounded-md text-md font-medium hover:opacity-90 transition text-center mt-2"
                        style="background-color: {{ $primaryColor }}; color: {{ $primaryForeground }}">
                        View Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="px-4 py-3 rounded-md text-md font-medium hover:opacity-90 transition text-center mt-2"
                        style="background-color: {{ $primaryColor }}; color: {{ $primaryForeground }}">
                        Login
                    </a>
                @endauth
            </nav>
        </div>
    </div>
</header>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const hamburgerIcon = document.getElementById('hamburgerIcon');
    const closeIcon = document.getElementById('closeIcon');
    
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        hamburgerIcon.classList.add('hidden');
        closeIcon.classList.remove('hidden');
    } else {
        menu.classList.add('hidden');
        hamburgerIcon.classList.remove('hidden');
        closeIcon.classList.add('hidden');
    }
}
</script>

