<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Dhaka IT Institute</title>

    @php
        $settingsService = app(\App\Services\SettingsService::class);
        $faviconUrl = $settingsService->getFavicon();
    @endphp
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=20260802">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js for interactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    
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
            --color-success: #10b981;
            --color-success-foreground: #ffffff;
            --color-warning: #f59e0b;
            --color-warning-foreground: #ffffff;
            --color-info: #3b82f6;
            --color-info-foreground: #ffffff;
            --color-destructive: #ef4444;
            --color-destructive-foreground: #ffffff;
            --color-background: #ffffff;
            --color-foreground: #111827;
            --color-muted: #f3f4f6;
            --color-muted-foreground: #6b7280;
            --color-accent: #f3f4f6;
            --color-accent-foreground: #111827;
            --color-input: #d1d5db;
            --color-ring: {{ $primaryColor }};
        }
        
        /* 1. Standard approach for Firefox */
        * {
            scrollbar-width: thin;
            scrollbar-color: var(--color-primary) #e8f5e9;
        }

        /* 2. WebKit approach for Chrome, Edge, and Safari */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: #e8f5e9;
        }

        ::-webkit-scrollbar-thumb {
            background-color: var(--color-primary);
            border-radius: 10px;
            border: 3px solid #e8f5e9;
        }

        ::-webkit-scrollbar-thumb:hover {
            filter: brightness(0.9);
        }

        body {
            font-family: 'Inter', 'Noto Sans Bengali', sans-serif;
        }

        .bengali-text {
            font-family: 'Noto Sans Bengali', sans-serif;
        }
        
        /* Theme color utilities */
        .bg-primary {
            background-color: var(--color-primary) !important;
        }
        
        .text-primary {
            color: var(--color-primary) !important;
        }
        
        .text-primary-foreground {
            color: var(--color-primary-foreground) !important;
        }

        /* Semantic color utilities (used by badge/button/alert components) */
        .bg-success { background-color: var(--color-success) !important; }
        .text-success { color: var(--color-success) !important; }
        .text-success-foreground { color: var(--color-success-foreground) !important; }
        .bg-success\/10 { background-color: rgba(16, 185, 129, 0.1) !important; }
        .border-success\/50 { border-color: rgba(16, 185, 129, 0.5) !important; }
        .bg-warning { background-color: var(--color-warning) !important; }
        .text-warning { color: var(--color-warning) !important; }
        .text-warning-foreground { color: var(--color-warning-foreground) !important; }
        .bg-warning\/10 { background-color: rgba(245, 158, 11, 0.1) !important; }
        .border-warning\/50 { border-color: rgba(245, 158, 11, 0.5) !important; }
        .bg-info { background-color: var(--color-info) !important; }
        .text-info { color: var(--color-info) !important; }
        .text-info-foreground { color: var(--color-info-foreground) !important; }
        .bg-info\/10 { background-color: rgba(59, 130, 246, 0.1) !important; }
        .border-info\/50 { border-color: rgba(59, 130, 246, 0.5) !important; }
        .bg-destructive { background-color: var(--color-destructive) !important; }
        .text-destructive { color: var(--color-destructive) !important; }
        .text-destructive-foreground { color: var(--color-destructive-foreground) !important; }
        .border-destructive\/50 { border-color: rgba(239, 68, 68, 0.5) !important; }
        .bg-background { background-color: var(--color-background) !important; }
        .text-foreground { color: var(--color-foreground) !important; }
        .bg-muted { background-color: var(--color-muted) !important; }
        .text-muted-foreground { color: var(--color-muted-foreground) !important; }
        .bg-accent { background-color: var(--color-accent) !important; }
        .text-accent-foreground { color: var(--color-accent-foreground) !important; }
        .border-input { border-color: var(--color-input) !important; }
        .ring-ring { --tw-ring-color: var(--color-ring) !important; }
        .border-destructive { border-color: var(--color-destructive) !important; }
        .hover\:bg-destructive\/10:hover { background-color: rgba(239, 68, 68, 0.1) !important; }
        .hover\:bg-destructive\/80:hover { background-color: var(--color-destructive) !important; opacity: 0.8; }
        .hover\:bg-success\/80:hover { background-color: var(--color-success) !important; opacity: 0.8; }
        .hover\:bg-warning\/80:hover { background-color: var(--color-warning) !important; opacity: 0.8; }
        .hover\:bg-info\/80:hover { background-color: var(--color-info) !important; opacity: 0.8; }
        .hover\:bg-primary\/80:hover { background-color: var(--color-primary) !important; opacity: 0.8; }
        .hover\:bg-accent:hover { background-color: var(--color-accent) !important; }
        .hover\:text-accent-foreground:hover { color: var(--color-accent-foreground) !important; }
        
        .bg-secondary {
            background-color: var(--color-secondary) !important;
        }
        
        .text-secondary {
            color: var(--color-secondary) !important;
        }
        
        .text-secondary-foreground {
            color: var(--color-secondary-foreground) !important;
        }
        
        .border-primary {
            border-color: var(--color-primary) !important;
        }
        
        .hover\:bg-primary\/90:hover {
            background-color: var(--color-primary) !important;
            opacity: 0.9;
        }
        
        .hover\:bg-secondary\/80:hover {
            background-color: var(--color-secondary) !important;
            opacity: 0.8;
        }
        
        .bg-primary\/10 { background-color: color-mix(in srgb, var(--color-primary) 12%, white) !important; }
        .hover\:bg-primary\/10:hover {
            background-color: color-mix(in srgb, var(--color-primary) 12%, white) !important;
            opacity: 1;
        }
        
        .hover\:text-primary:hover {
            color: var(--color-primary) !important;
        }
        
        .focus\:border-primary:focus {
            border-color: var(--color-primary) !important;
        }
        
        .focus\:ring-primary:focus {
            --tw-ring-color: var(--color-primary) !important;
        }
        
        .checked\:bg-primary:checked {
            background-color: var(--color-primary) !important;
        }
        
        .checked\:border-primary:checked {
            border-color: var(--color-primary) !important;
        }
        
        .checked\:text-primary-foreground:checked {
            color: var(--color-primary-foreground) !important;
        }
        
        .accent-primary {
            accent-color: var(--color-primary) !important;
        }
        
        .hover\:bg-primary\/80:hover {
            background-color: var(--color-primary) !important;
            opacity: 0.8;
        }

        .no-transition {
            transition: none !important;
        }

        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
            will-change: max-height;
        }

        .submenu.open {
            max-height: 3000px;
        }

        .rotate-180 {
            transform: rotate(180deg);
        }

        .transition-transform {
            transition: transform 0.3s ease-in-out;
        }

        /* Custom Scrollbar Styling */
        .sidebar-nav {
            scrollbar-width: thin;
            scrollbar-color: var(--color-primary) #e5e7eb;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 3px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: var(--color-primary);
            border-radius: 3px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            filter: brightness(0.9);
        }

        /* Sidebar Layout */
        .sidebar-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
            width: 16rem;
        }

        .sidebar-header {
            flex-shrink: 0;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: 80px;
        }

        .sidebar-footer {
            flex-shrink: 0;
            position: fixed;
            bottom: 0;
            width: 16rem;
            background: white;
            z-index: 10;
        }
    </style>
    @stack('styles')
</head>

<body class="bg-white">
    <!-- Mobile Menu Overlay -->
    <div id="mobileMenuOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleMobileSidebar()"></div>

    <!-- Sidebar -->
    <div id="adminSidebar" class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <div class="sidebar-container">
            <!-- Logo -->
            <div class="sidebar-header flex items-center justify-between px-4 py-4 border-b border-gray-200">
                @php
                    $settingsService = app(\App\Services\SettingsService::class);
                    $logoUrl = $settingsService->getLogo();
                    $institutionName = $settingsService->get('institution_name', 'Dhaka IT Institute');
                @endphp
                <img src="{{ $logoUrl }}" alt="{{ $institutionName }}" class="h-10 w-auto object-contain">
                
                <!-- Close button for mobile -->
                <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                
                <!-- Collapse/Expand All Buttons (Desktop) -->
                <div class="hidden lg:flex items-center space-x-1">
                    <button type="button" onclick="collapseAllSubmenus()" 
                            class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition-colors" 
                            title="Collapse All">
<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevrons-down-up-icon lucide-chevrons-down-up"><path d="m7 20 5-5 5 5"/><path d="m7 4 5 5 5-5"/></svg>
                    </button>
                    <button type="button" onclick="expandAllSubmenus()" 
                            class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition-colors" 
                            title="Expand All">
<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-expand-icon lucide-expand"><path d="m15 15 6 6"/><path d="m15 9 6-6"/><path d="M21 16v5h-5"/><path d="M21 8V3h-5"/><path d="M3 16v5h5"/><path d="m3 21 6-6"/><path d="M3 8V3h5"/><path d="M9 9 3 3"/></svg>
                    </button>
                </div>
            </div>

            <!-- Dynamic Navigation based on user role -->
            <div class="sidebar-nav">
                <x-sidebar.navigation :menuItems="$sidebarMenuItems ?? []" />
            </div>

            <!-- Logout Button (Fixed at bottom) -->
            <div class="sidebar-footer p-4 border-t border-gray-100">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex items-center space-x-3 px-4 py-2.5 text-red-600 hover:bg-red-50 rounded-lg font-medium text-sm w-full transition-colors">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
        <script>
            // Sidebar localStorage keys
            const SIDEBAR_SCROLL_KEY = 'adminSidebarScroll';
            const SIDEBAR_SUBMENUS_KEY = 'adminSidebarSubmenus';

            // Active link styling classes
            const ACTIVE_CLASSES = ['bg-primary', 'text-primary-foreground', 'font-semibold'];
            const INACTIVE_CLASSES = ['text-gray-600', 'hover:bg-gray-100'];

            function toggleMobileSidebar() {
                const sidebar = document.getElementById('adminSidebar');
                const overlay = document.getElementById('mobileMenuOverlay');
                
                if (sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                } else {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            }

            function toggleSubmenu(submenuId) {
                const submenu = document.getElementById(submenuId);
                const icon = document.getElementById(submenuId + 'Icon');

                if (submenu) {
                    submenu.classList.toggle('open');
                    if (icon) icon.classList.toggle('rotate-180');
                    saveSubmenuStates();
                }
            }

            // Expand all submenus
            function expandAllSubmenus() {
                const submenus = document.querySelectorAll('.submenu');
                submenus.forEach(submenu => {
                    submenu.classList.add('open');
                    const icon = document.getElementById(submenu.id + 'Icon');
                    if (icon) icon.classList.add('rotate-180');
                });
                saveSubmenuStates();
            }

            // Collapse all submenus
            function collapseAllSubmenus() {
                const submenus = document.querySelectorAll('.submenu');
                submenus.forEach(submenu => {
                    submenu.classList.remove('open');
                    const icon = document.getElementById(submenu.id + 'Icon');
                    if (icon) icon.classList.remove('rotate-180');
                });
                saveSubmenuStates();
            }

            function saveSubmenuStates() {
                const submenus = document.querySelectorAll('.submenu');
                const states = {};
                submenus.forEach(submenu => {
                    states[submenu.id] = submenu.classList.contains('open');
                });
                localStorage.setItem(SIDEBAR_SUBMENUS_KEY, JSON.stringify(states));
            }

            function restoreSubmenuStates() {
                const saved = localStorage.getItem(SIDEBAR_SUBMENUS_KEY);
                if (saved) {
                    try {
                        const states = JSON.parse(saved);
                        Object.keys(states).forEach(submenuId => {
                            if (states[submenuId]) {
                                const submenu = document.getElementById(submenuId);
                                const icon = document.getElementById(submenuId + 'Icon');
                                if (submenu) {
                                    submenu.classList.add('open');
                                    if (icon) icon.classList.add('rotate-180');
                                }
                            }
                        });
                    } catch (e) {
                        console.error('Error restoring submenu states:', e);
                    }
                }
            }

            function saveSidebarScroll() {
                const scroller = document.querySelector('.sidebar-nav');
                if (scroller) {
                    localStorage.setItem(SIDEBAR_SCROLL_KEY, scroller.scrollTop);
                }
            }

            function restoreSidebarScroll() {
                const scroller = document.querySelector('.sidebar-nav');
                const savedScroll = localStorage.getItem(SIDEBAR_SCROLL_KEY);
                if (scroller && savedScroll) {
                    scroller.scrollTop = parseInt(savedScroll, 10);
                }
            }

            function highlightActiveLink() {
                const currentUrl = window.location.href.split('?')[0].split('#')[0];
                const links = document.querySelectorAll('.sidebar-nav a');

                // First, remove active classes from all links
                links.forEach(link => {
                    link.classList.remove(...ACTIVE_CLASSES);
                    link.classList.add(...INACTIVE_CLASSES);
                });

                // Then apply active class only to exact match
                links.forEach(link => {
                    const linkUrl = link.href.split('?')[0].split('#')[0];
                    if (linkUrl === currentUrl) {
                        link.classList.remove(...INACTIVE_CLASSES);
                        link.classList.add(...ACTIVE_CLASSES);

                        // Open parent submenu if exists
                        const parentSubmenu = link.closest('.submenu');
                        if (parentSubmenu) {
                            parentSubmenu.classList.add('open');
                            const submenuId = parentSubmenu.id;
                            const icon = document.getElementById(submenuId + 'Icon');
                            if (icon) icon.classList.add('rotate-180');
                            saveSubmenuStates();
                        }
                    }
                });
            }

            function toggleProfileDropdown() {
                const menu = document.getElementById('profileDropdownMenu');
                if (menu) {
                    if (menu.classList.contains('hidden')) {
                        menu.classList.remove('hidden');
                        requestAnimationFrame(() => {
                            menu.classList.remove('opacity-0', 'scale-95');
                            menu.classList.add('opacity-100', 'scale-100');
                        });
                    } else {
                        menu.classList.remove('opacity-100', 'scale-100');
                        menu.classList.add('opacity-0', 'scale-95');
                        setTimeout(() => {
                            menu.classList.add('hidden');
                        }, 100);
                    }
                }
            }

            window.addEventListener('click', function (e) {
                const btn = document.getElementById('profileDropdownBtn');
                const menu = document.getElementById('profileDropdownMenu');
                if (btn && menu && !btn.contains(e.target) && !menu.contains(e.target) && !menu.classList.contains('hidden')) {
                    toggleProfileDropdown();
                }
            });

            function initSidebarScrollPersistence() {
                const scroller = document.querySelector('.sidebar-nav');
                if (scroller) {
                    // Debounced save on scroll
                    let scrollTimeout;
                    scroller.addEventListener('scroll', function () {
                        clearTimeout(scrollTimeout);
                        scrollTimeout = setTimeout(saveSidebarScroll, 50); // Reduced delay
                    });

                    // Immediate save on any link click in sidebar
                    const links = scroller.querySelectorAll('a');
                    links.forEach(link => {
                        link.addEventListener('click', function() {
                            saveSidebarScroll();
                            // Close mobile sidebar on link click
                            if (window.innerWidth < 1024) {
                                toggleMobileSidebar();
                            }
                        });
                    });

                    // Final backup save before leaving page
                    window.addEventListener('beforeunload', function() {
                        saveSidebarScroll();
                    });
                }
            }

            (function () {
                document.body.classList.add('no-transition');
                const style = document.createElement('style');
                style.id = 'suppress-transitions';
                style.textContent = '* { transition: none !important; }';
                document.head.appendChild(style);

                restoreSubmenuStates();
                restoreSidebarScroll();
                highlightActiveLink();

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        document.body.classList.remove('no-transition');
                        const styleEl = document.getElementById('suppress-transitions');
                        if (styleEl) styleEl.remove();
                    });
                });
            })();

            window.addEventListener('DOMContentLoaded', function () {
                initSidebarScrollPersistence();
                initSidebarTextCopy();
            });

            // Copy sidebar text to clipboard on selection attempt
            function initSidebarTextCopy() {
                const sidebar = document.querySelector('.sidebar-container');
                if (!sidebar) return;

                let selectionTimeout;
                
                sidebar.addEventListener('mouseup', function(e) {
                    clearTimeout(selectionTimeout);
                    selectionTimeout = setTimeout(() => {
                        const selection = window.getSelection();
                        const selectedText = selection.toString().trim();
                        
                        if (selectedText && selectedText.length > 0) {
                            // Copy to clipboard
                            navigator.clipboard.writeText(selectedText).then(() => {
                                // Show toast notification
                                showCopyToast('Copied to clipboard!');
                                // Clear selection
                                selection.removeAllRanges();
                            }).catch(err => {
                                console.error('Failed to copy text:', err);
                            });
                        }
                    }, 100);
                });
            }

            function showCopyToast(message) {
                // Remove existing toast if any
                const existingToast = document.getElementById('sidebar-copy-toast');
                if (existingToast) {
                    existingToast.remove();
                }

                // Create toast element
                const toast = document.createElement('div');
                toast.id = 'sidebar-copy-toast';
                toast.className = 'fixed bottom-20 left-4 bg-gray-800 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm flex items-center space-x-2 animate-fade-in';
                toast.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>${message}</span>
                `;
                
                document.body.appendChild(toast);

                // Remove after 2 seconds
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(10px)';
                    setTimeout(() => toast.remove(), 300);
                }, 2000);
            }
        </script>
        <style>
            @keyframes fade-in {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .animate-fade-in {
                animation: fade-in 0.3s ease-out;
            }
            #sidebar-copy-toast {
                transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            }
        </style>
    </div>

    <!-- Main Content -->
    <div class="lg:pl-64 min-h-screen flex flex-col top-0 left-0 min-w-full">
        <!-- Site Header -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4">
                <!-- Mobile Menu Button -->
                <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-2 rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <div class="flex-1 lg:flex-none">
                    <h1 class="text-lg lg:text-2xl font-bold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                    <p class="text-xs lg:text-sm text-gray-500 mt-1 hidden sm:block">@yield('page-description', '')</p>
                </div>
                <div class="flex items-center space-x-2 lg:space-x-4">
                    @php
                        $courseMode = session('course_mode', 'online');
                    @endphp
                    <form method="POST" action="{{ route('dashboard.course-mode') }}" class="flex rounded-full bg-gray-100 p-1" aria-label="Course delivery mode">
                        @csrf
                        <button name="mode" value="online" class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $courseMode === 'online' ? 'bg-green-700 text-white shadow' : 'text-gray-600' }}">
                            <i class="fa-solid fa-wifi mr-1"></i> Online
                        </button>
                        <button name="mode" value="offline" class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $courseMode === 'offline' ? 'bg-black text-white shadow' : 'text-gray-600' }}">
                            <i class="fa-solid fa-building mr-1"></i> Offline
                        </button>
                    </form>
                    @php
                        $unreadPaymentNotifications = auth()->check()
                            ? \App\Models\Notification::where('user_id', auth()->id())->whereNull('read_at')->latest()->take(5)->get()
                            : collect();
                    @endphp
                    @if($unreadPaymentNotifications->isNotEmpty())
                        <a href="{{ $unreadPaymentNotifications->first()->action_url ?? route('student.payment.dashboard') }}"
                           class="relative rounded-full p-2 text-gray-600 hover:bg-gray-100" title="{{ $unreadPaymentNotifications->first()->title }}">
                            <i class="fa-solid fa-bell"></i>
                            <span class="absolute right-0 top-0 min-w-4 rounded-full bg-red-600 px-1 text-center text-[10px] text-white">{{ $unreadPaymentNotifications->count() }}</span>
                        </a>
                    @endif
                    <div class="text-right hidden sm:block">
                        <p class="text-xs lg:text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-500">
                            {{ Auth::user()->roles->first()->name ?? 'User' }}
                        </p>
                    </div>

                    <!-- Profile Dropdown -->
                    <div class="relative">
                        <button id="profileDropdownBtn" onclick="toggleProfileDropdown()"
                            class="w-8 h-8 lg:w-10 lg:h-10 bg-bd-green rounded-full flex items-center justify-center text-white font-bold cursor-pointer hover:opacity-90 transition-opacity focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-bd-green text-sm lg:text-base">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="profileDropdownMenu"
                            class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 border border-gray-200 z-50 origin-top-right transition-all duration-100 ease-out transform scale-95 opacity-0">
                            <a href="{{ url('/') }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fa-solid fa-house mr-2"></i> Home
                            </a>
                            @if(Auth::user()->isSuperAdmin())
                            <a href="{{ route('dashboard.settings.index') }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fa-solid fa-gear mr-2"></i> Settings
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="p-4 lg:p-6 min-h-screen overflow-y-auto overflow-x-hidden">
            <!-- Global Toast Notifications -->
            @if (session('success'))
                <x-ui.toast type="success" :message="session('success')" />
            @endif

            @if (session('error'))
                <x-ui.toast type="error" :message="session('error')" />
            @endif
            
            @if (session('warning'))
                <x-ui.toast type="warning" :message="session('warning')" />
            @endif

            @if (session('info'))
                <x-ui.toast type="info" :message="session('info')" />
            @endif

            @yield('content')
        </div>
    </div>

    {{-- Custom Confirmation Dialog --}}
    <x-ui.confirm-dialog />
    @stack('scripts')
</body>

</html>

