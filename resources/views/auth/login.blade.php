<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dhaka IT Institute</title>
    @php
        $settingsService = app(\App\Services\SettingsService::class);
        $faviconUrl = $settingsService->getFavicon();
    @endphp
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=20260802">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @php
        $primaryColor = $settingsService->get('theme_primary_color', '#3d59f9');
        $primaryForeground = $settingsService->get('theme_primary_foreground', '#ffffff');
        $secondaryColor = $settingsService->get('theme_secondary_color', '#8b5cf6');
        $secondaryForeground = $settingsService->get('theme_secondary_foreground', '#ffffff');
        
        // Convert hex to RGB for gradient effects
        [$pr, $pg, $pb] = sscanf($primaryColor, '#%02x%02x%02x');
        [$sr, $sg, $sb] = sscanf($secondaryColor, '#%02x%02x%02x');
    @endphp
    <style>
        :root {
            --color-primary: {{ $primaryColor }};
            --color-primary-foreground: {{ $primaryForeground }};
            --color-secondary: {{ $secondaryColor }};
            --color-secondary-foreground: {{ $secondaryForeground }};
            --color-primary-rgb: {{ $pr }}, {{ $pg }}, {{ $pb }};
            --color-secondary-rgb: {{ $sr }}, {{ $sg }}, {{ $sb }};
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, 
                rgba(var(--color-primary-rgb), 0.05) 0%, 
                rgba(255, 255, 255, 1) 50%, 
                rgba(var(--color-secondary-rgb), 0.05) 100%);
        }
        
        .bg-primary {
            background-color: var(--color-primary);
        }
        
        .text-primary {
            color: var(--color-primary);
        }
        
        .text-primary-foreground {
            color: var(--color-primary-foreground);
        }
        
        .border-primary {
            border-color: var(--color-primary);
        }
        
        .hover\:bg-primary-dark:hover {
            filter: brightness(0.9);
        }
        
        .focus\:ring-primary:focus {
            --tw-ring-color: var(--color-primary);
        }
        
        .logo-container {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            padding: 1rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(var(--color-primary-rgb), 0.3);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            @php
                $settingsService = app(\App\Services\SettingsService::class);
                $logoUrl = $settingsService->getLogo();
                $institutionName = $settingsService->get('institution_name', 'Dhaka IT Institute');
            @endphp
            <div class="inline-flex items-center justify-center mb-4">
                <img src="{{ $logoUrl }}" alt="{{ $institutionName }} Logo" class="h-16 w-auto">
            </div>
            <!-- <h1 class="text-3xl font-bold text-gray-900">{{ $institutionName }}</h1> -->
            <p class="text-gray-600 mt-2">Sign in to your account</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <!-- <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Welcome Back</h2> -->

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-6">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if(session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                
                <!-- Email -->
                <div class="mb-5">
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                    <div class="relative">
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}"
                            class="w-full px-4 py-3 pr-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition"
                            required
                            autofocus
                        >
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition"
                            required
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
                            aria-label="Toggle password visibility"
                        >
                            <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg id="eye-off-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between mb-6">
                    <x-ui.checkbox name="remember" id="remember-me">
                        Remember me
                    </x-ui.checkbox>
                    <a href="{{ route('password.request') }}" class="text-sm text-primary hover:underline">Forgot password?</a>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit" 
                    class="w-full bg-primary text-primary-foreground font-semibold py-3 rounded-lg transition-all shadow-lg hover:shadow-xl hover:bg-primary-dark transform hover:scale-[1.02]"
                >
                    Sign In
                </button>
            </form>

            <!-- Back to Home -->
            <div class="mt-6 text-center">
                <a href="/" class="text-sm text-primary hover:underline font-medium">← Back to Homepage</a>
            </div>
        </div>

    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            const eyeOffIcon = document.getElementById('eye-off-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
            }
        }
    </script>
</body>
</html>

