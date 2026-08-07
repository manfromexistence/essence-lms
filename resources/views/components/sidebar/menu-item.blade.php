@props(['item', 'level' => 0])

@php
    $hasChildren = isset($item['children']) && count($item['children']) > 0;
    $submenuId = $hasChildren ? Str::slug($item['title']) . 'Submenu' : null;
    
    // Handle route with parameters
    $routeUrl = null;
    $routeExists = false;
    
    if (isset($item['route']) && $item['route']) {
        // Strip all whitespace including non-breaking spaces
        $routeName = preg_replace('/\s+/u', '', $item['route']);
        $routeExists = Route::has($routeName);
        
        if ($routeExists) {
            if (isset($item['route_params'])) {
                $routeUrl = route($routeName, $item['route_params']);
            } else {
                $routeUrl = route($routeName);
            }
        }
    }
    
    // Check if current route matches the item's route or any of its active routes
    $currentRoute = Route::currentRouteName();
    $isActive = false;
    
    // Strip whitespace from route name for comparison
    $itemRoute = isset($item['route']) && $item['route'] ? preg_replace('/\s+/u', '', $item['route']) : null;
    
    // Check main route
    if ($itemRoute && $currentRoute === $itemRoute) {
        $isActive = true;
    }
    
    // Check activeRoutes array
    if (!$isActive && isset($item['activeRoutes']) && is_array($item['activeRoutes'])) {
        foreach ($item['activeRoutes'] as $activeRoute) {
            $cleanRoute = preg_replace('/\s+/u', '', $activeRoute);
            if ($currentRoute === $cleanRoute) {
                $isActive = true;
                break;
            }
        }
    }
    
    // Check if any child is active
    $hasActiveChild = false;
    if ($hasChildren) {
        foreach ($item['children'] as $child) {
            if (isset($child['route']) && $child['route'] && Route::currentRouteName() === $child['route']) {
                $hasActiveChild = true;
                break;
            }
        }
    }
@endphp

@if($hasChildren)
    {{-- Menu item with children (submenu) --}}
    <div class="space-y-1 {{ $hasActiveChild ? 'rounded-lg bg-primary/10' : '' }}">
        <button onclick="toggleSubmenu('{{ $submenuId }}')"
            class="flex items-center justify-between w-full px-3 py-2.5 rounded-lg font-medium text-sm transition-colors
                {{ $hasActiveChild ? 'text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
            <div class="flex items-center space-x-2">
                <x-sidebar.menu-icon :icon="$item['icon'] ?? 'default'" />
                <span class="truncate">{{ $item['title'] }}</span>
            </div>
            <svg id="{{ $submenuId }}Icon" class="w-4 h-4 transition-transform {{ $hasActiveChild ? 'rotate-180' : '' }}" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        {{-- Submenu --}}
        <div id="{{ $submenuId }}" class="submenu pl-3 pr-1 space-y-1 {{ $hasActiveChild ? 'open' : '' }}">
            @foreach($item['children'] as $child)
                <x-sidebar.menu-item :item="$child" :level="$level + 1" />
            @endforeach
        </div>
    </div>
@else
    {{-- Single menu item (no children) --}}
    @if(isset($item['route']) && $item['route'])
        @if($routeExists)
            <a href="{{ $routeUrl }}"
                class="flex items-center space-x-{{ $level > 0 ? '3' : '2' }} px-{{ $level > 0 ? '3' : '3' }} py-{{ $level > 0 ? '2' : '2.5' }} text-sm rounded-lg font-medium transition-colors
                    {{ $isActive 
                        ? 'bg-primary text-primary-foreground font-semibold shadow-sm' 
                        : ($level > 0 ? 'text-gray-600 hover:bg-gray-100' : 'text-gray-700 hover:bg-gray-100') 
                    }}">
                <x-sidebar.menu-icon :icon="$item['icon'] ?? 'default'" />
                <span class="truncate">{{ $item['title'] }}</span>
            </a>
        @else
            {{-- Route doesn't exist yet, show disabled state --}}
            <span class="flex items-center space-x-{{ $level > 0 ? '3' : '2' }} px-{{ $level > 0 ? '3' : '3' }} py-{{ $level > 0 ? '2' : '2.5' }} text-sm text-gray-400 rounded-lg font-medium cursor-not-allowed" title="Route not defined: {{ $item['route'] }}">
                <x-sidebar.menu-icon :icon="$item['icon'] ?? 'default'" />
                <span class="truncate">{{ $item['title'] }}</span>
            </span>
        @endif
    @endif
@endif
