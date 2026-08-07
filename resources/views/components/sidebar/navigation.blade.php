@props(['menuItems' => []])

<nav class="px-4 py-6 space-y-2 pb-4">
    @forelse($menuItems as $item)
        <x-sidebar.menu-item :item="$item" />
    @empty
        {{-- Fallback: Show dashboard link if no menu items --}}
        <a href="{{ route('dashboard') }}"
            class="flex items-center space-x-3 px-4 py-3 bg-primary text-primary-foreground rounded-lg font-medium">
            <x-sidebar.menu-icon icon="dashboard" />
            <span>Dashboard</span>
        </a>
    @endforelse
</nav>
