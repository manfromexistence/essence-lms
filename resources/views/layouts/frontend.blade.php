<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dhaka IT Institute')</title>

    @php
        $settingsService = app(\App\Services\SettingsService::class);
        $faviconUrl = $settingsService->getFavicon();
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    @php
        $settingsService = app(\App\Services\SettingsService::class);
        $primaryColor = $settingsService->get('theme_primary_color', '#3d59f9');
        $primaryForeground = $settingsService->get('theme_primary_foreground', '#ffffff');
        $secondaryColor = $settingsService->get('theme_secondary_color', '#8b5cf6');
        $secondaryForeground = $settingsService->get('theme_secondary_foreground', '#ffffff');
    @endphp

    <style>
        :root {
            --color-primary: {{ $primaryColor }};
            --color-primary-foreground: {{ $primaryForeground }};
            --color-secondary: {{ $secondaryColor }};
            --color-secondary-foreground: {{ $secondaryForeground }};
        }

        @php
            // expose RGB components so we can produce accessible gradients/tints reliably
            [$pr, $pg, $pb] = sscanf($primaryColor, '#%02x%02x%02x');
            [$sr, $sg, $sb] = sscanf($secondaryColor, '#%02x%02x%02x');
        @endphp

        :root {
            --rgb-primary: {{ $pr }}, {{ $pg }}, {{ $pb }};
            --rgb-secondary: {{ $sr }}, {{ $sg }}, {{ $sb }};
        }

        body {
            font-family: 'Noto Sans Bengali', sans-serif;
        }

        /* HERO / branding utilities (consistent, accessible color combinations) */
        .hero {
            position: relative;
            padding: 2rem 0;
            display: flex;
            align-items: center;
            background-size: cover;
            background-position: center;
        }

        @media (min-width: 768px) {
            .hero {
                padding: 4.5rem 0;
            }
        }

        .hero-inner {
            width: 100%;
            max-width: 72rem;
            margin: 0 auto;
            padding: 0 1rem;
            position: relative;
            z-index: 2;
        }

        .hero--dark { color: #ffffff; }
        .hero--light { color: #0f172a; background-color: #f8fafc; }

        /* solid / gradient hero variants */
        .hero--solid { background: linear-gradient(180deg, rgba(var(--rgb-primary),1) 0%, rgba(var(--rgb-primary),1) 100%); }
        .hero--gradient { background: linear-gradient(135deg, rgba(var(--rgb-primary),0.95) 0%, rgba(var(--rgb-secondary),0.9) 100%); }
        .hero--image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(2,6,23,0.28), rgba(2,6,23,0.36));
            pointer-events: none;
            z-index: 1;
        }

        .hero__overlay {
            background: linear-gradient(180deg, rgba(var(--rgb-primary),0.14), rgba(0,0,0,0.36));
            mix-blend-mode: multiply;
        }

        .hero .hero-title { 
            font-weight: 800; 
            letter-spacing: -0.02em;
            font-size: 1.875rem;
        }

        @media (min-width: 768px) {
            .hero .hero-title {
                font-size: 2.5rem;
            }
        }

        @media (min-width: 1024px) {
            .hero .hero-title {
                font-size: 3rem;
            }
        }

        .hero .hero-subtitle { opacity: .95; }
        .hero .hero-cta { box-shadow: 0 8px 24px rgba(var(--rgb-primary), .18); }

        /* ensure accessible focus state for primary actions */
        .hero a:focus, .hero button:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(var(--rgb-primary), .14), 0 6px 18px rgba(2,6,23,0.08);
        }

        @keyframes scroll {
            0% { transform: translateX(0%); }
            100% { transform: translateX(-50%); }
        }

        .ticker { 
            display: inline-block; 
            white-space: nowrap; 
            animation: scroll 10s linear infinite; 
        }

        /* Faster animation on mobile */
        @media (max-width: 768px) {
            .ticker {
                animation: scroll 15s linear infinite;
            }
        }

        .bg-primary { background-color: var(--color-primary) !important; }
        .text-primary { color: var(--color-primary) !important; }
        .border-primary { border-color: var(--color-primary) !important; }
        .hover\:bg-primary:hover { background-color: var(--color-primary) !important; }
        .hover\:text-primary:hover { color: var(--color-primary) !important; }
        .from-primary { --tw-gradient-from: var(--color-primary) !important; }
        .to-primary { --tw-gradient-to: var(--color-primary) !important; }
    </style>
    @stack('styles')
</head>

<body class="bg-white">
    <x-top-bar />
    <x-header />

    <main>
        @yield('content')
    </main>

    <x-footer />
    @stack('scripts')
</body>

</html>
