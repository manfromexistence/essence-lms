@props([
    'headers', 
    'rows',
    'searchable' => true,
    'sortable' => true,
    'route' => '',
    'id' => null,
])

@php
    // Use provided ID or generate a stable one based on headers to persist localStorage across reloads
    $tableId = $id ?? 'dt-' . substr(md5(json_encode($headers)), 0, 8);
    $cols = array_map(function($h) { 
        return ['key' => $h['key'], 'label' => $h['label'], 'visible' => true]; 
    }, $headers);
@endphp

<div class="bg-white rounded-xl shadow-md border border-gray-200 custom-data-table" id="{{ $tableId }}">
    <!-- Header Controls -->
    <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4 bg-gray-50/50">
        <!-- Search -->
        <div class="w-full sm:w-72">
            @if($searchable)
            <form action="{{ $route }}" method="GET" class="relative flex items-center">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       class="block w-full pl-9 pr-12 py-2 text-sm border border-gray-200 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-gray-900 focus:border-gray-900 transition duration-150 ease-in-out h-9" 
                       placeholder="Search...">
                
                @if(request('search'))
                    <a href="{{ $route }}" class="absolute right-9 text-gray-400 hover:text-gray-600">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </a>
                @endif

                <button type="submit" class="absolute right-0 top-0 bottom-0 px-3 text-gray-500 hover:text-gray-700">
                    <svg class="h-4 w-4 transform rotate-90" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
                
                @foreach(request()->except(['search', 'page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
            </form>
            @endif
        </div>

        <!-- Column Visibility & Actions -->
        <div class="flex items-center space-x-2">
            <div class="relative">
                <button type="button" 
                        id="{{ $tableId }}-view-btn"
                        class="inline-flex items-center px-4 py-2 border border-gray-200 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-bd-green">
                    <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View
                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>

                <!-- Dropdown -->
                <div id="{{ $tableId }}-col-menu" 
                     class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-lg shadow-lg bg-white border border-gray-200 z-20 focus:outline-none p-2 animate-in fade-in zoom-in-95 duration-200">
                    <div class="px-2 py-1.5 text-sm font-semibold text-gray-900 border-b border-gray-100 mb-1">Toggle Columns</div>
                    @foreach($headers as $index => $header)
                        <label class="flex items-center px-2 py-1.5 hover:bg-gray-100 rounded cursor-pointer group">
                             <input type="checkbox"
                                   class="h-4 w-4 shrink-0 cursor-pointer appearance-none rounded border border-gray-300 bg-white transition-colors checked:border-primary checked:bg-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 column-toggle"
                                   data-index="{{ $index }}"
                                   checked>
                            <span class="ml-2 text-sm text-gray-700 group-hover:text-gray-900 transition-colors">{{ $header['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            
            {{ $actions ?? '' }}
        </div>
    </div>

    <!-- Data Table -->
    <div class="overflow-x-auto rounded-b-xl border-t border-gray-200 drag-scroll-container" id="{{ $tableId }}-scroll-container">
        <table class="min-w-full divide-y divide-gray-200" id="{{ $tableId }}-table">
            <thead class="bg-gray-50/50">
                <tr>
                    @foreach($headers as $index => $header)
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider group relative" data-col-index="{{ $index }}">
                            <div class="flex items-center space-x-1">
                                <span>{{ $header['label'] }}</span>
                                @if($sortable)
                                    <button type="button" class="ml-1 p-1 rounded hover:bg-gray-200 text-gray-400 hover:text-gray-700 transition-colors focus:outline-none {{ request('sort') == $header['key'] ? 'text-bd-green bg-emerald-50' : '' }}" onclick="toggleHeaderMenu('{{ $tableId }}-header-menu-{{ $index }}')">
                                        @if(request('sort') == $header['key'])
                                            @if(request('direction') == 'asc')
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" /></svg>
                                            @else
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4" /></svg>
                                            @endif
                                        @else
                                             <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" /></svg>
                                        @endif
                                    </button>
                                    
                                    <!-- Header Menu -->
                                    <div id="{{ $tableId }}-header-menu-{{ $index }}" class="hidden absolute left-0 top-full mt-1 w-40 bg-white border border-gray-200 rounded-md shadow-lg z-30 py-1 header-menu">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => $header['key'], 'direction' => 'asc']) }}" class="px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center">
                                            <svg class="w-3 h-3 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" /></svg>
                                            Sort Asc
                                        </a>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => $header['key'], 'direction' => 'desc']) }}" class="px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center">
                                            <svg class="w-3 h-3 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4" /></svg>
                                            Sort Desc
                                        </a>
                                        <div class="border-t border-gray-100 my-1"></div>
                                        <button type="button" class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center" 
                                                onclick="hideColumn('{{ $tableId }}', {{ $index }})">
                                            <svg class="w-3 h-3 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                            Hide Column
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if(method_exists($rows, 'hasPages') && $rows->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
            {{ $rows->appends(request()->query())->links() }}
        </div>
    @endif
</div>

@once
    @push('styles')
    <style>
        .drag-scroll-container {
            cursor: grab;
            position: relative;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .drag-scroll-container.dragging {
            cursor: grabbing !important;
            scroll-behavior: auto !important;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        .drag-scroll-container.dragging * {
            cursor: grabbing !important;
            user-select: none !important;
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
        }
        
        .drag-scroll-container::-webkit-scrollbar {
            height: 10px;
        }
        
        .drag-scroll-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 5px;
        }
        
        .drag-scroll-container::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 5px;
            transition: background 0.2s;
        }
        
        .drag-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Smooth scroll for non-dragging */
        .drag-scroll-container:not(.dragging) {
            scroll-behavior: smooth;
        }

        /* Show grab cursor on table */
        .drag-scroll-container table,
        .drag-scroll-container td,
        .drag-scroll-container th {
            cursor: grab;
        }

        /* Interactive elements should still be clickable */
        .drag-scroll-container button,
        .drag-scroll-container a,
        .drag-scroll-container input,
        .drag-scroll-container select,
        .drag-scroll-container textarea,
        .drag-scroll-container label {
            cursor: pointer;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        function toggleHeaderMenu(menuId) {
            const menu = document.getElementById(menuId);
            const isHidden = menu.classList.contains('hidden');
            
            // Close all others
            document.querySelectorAll('.header-menu').forEach(el => el.classList.add('hidden'));
            
            if(isHidden) {
                menu.classList.remove('hidden');
            }
        }
        
        function hideColumn(tableId, index) {
            // Uncheck the toggle in the main menu
            const tableRoot = document.getElementById(tableId);
            const checkbox = tableRoot.querySelector(`.column-toggle[data-index="${index}"]`);
            if(checkbox) {
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change'));
            }
            // Close header menu
            document.querySelectorAll('.header-menu').forEach(el => el.classList.add('hidden'));
        }

        // Drag Scroll Functionality
        function initDragScroll(container) {
            let isDown = false;
            let startX;
            let scrollLeft;
            let velocity = 0;
            let lastX = 0;
            let lastTime = Date.now();
            let momentumID;
            let hasMoved = false;

            // Mouse Events
            container.addEventListener('mousedown', (e) => {
                // Don't start drag if clicking on interactive elements
                if (e.target.closest('button, a, input, select, textarea, label')) {
                    return;
                }
                
                isDown = true;
                hasMoved = false;
                container.classList.add('dragging');
                startX = e.pageX - container.offsetLeft;
                scrollLeft = container.scrollLeft;
                lastX = e.pageX;
                lastTime = Date.now();
                velocity = 0;
                cancelMomentumTracking();
                e.preventDefault();
            });

            container.addEventListener('mouseleave', () => {
                if (isDown) {
                    isDown = false;
                    container.classList.remove('dragging');
                    beginMomentumTracking();
                }
            });

            container.addEventListener('mouseup', (e) => {
                if (isDown) {
                    isDown = false;
                    container.classList.remove('dragging');
                    beginMomentumTracking();
                    
                    // Prevent click events if we actually dragged
                    if (hasMoved) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }
            });

            container.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                
                const x = e.pageX - container.offsetLeft;
                const walk = (x - startX) * 2; // Scroll speed multiplier
                const now = Date.now();
                const dt = now - lastTime;
                const dx = e.pageX - lastX;
                
                // Mark as moved if we've dragged more than 5px
                if (Math.abs(walk) > 5) {
                    hasMoved = true;
                }
                
                velocity = dx / dt;
                lastX = e.pageX;
                lastTime = now;
                
                container.scrollLeft = scrollLeft - walk;
            });

            // Touch Events
            let touchStartX = 0;
            let touchScrollLeft = 0;
            let touchHasMoved = false;

            container.addEventListener('touchstart', (e) => {
                // Don't start drag if touching interactive elements
                if (e.target.closest('button, a, input, select, textarea, label')) {
                    return;
                }
                
                touchHasMoved = false;
                touchStartX = e.touches[0].pageX - container.offsetLeft;
                touchScrollLeft = container.scrollLeft;
                lastX = e.touches[0].pageX;
                lastTime = Date.now();
                velocity = 0;
                cancelMomentumTracking();
            }, { passive: true });

            container.addEventListener('touchmove', (e) => {
                const x = e.touches[0].pageX - container.offsetLeft;
                const walk = (x - touchStartX) * 2;
                const now = Date.now();
                const dt = now - lastTime;
                const dx = e.touches[0].pageX - lastX;
                
                // Mark as moved if we've dragged more than 5px
                if (Math.abs(walk) > 5) {
                    touchHasMoved = true;
                }
                
                velocity = dx / dt;
                lastX = e.touches[0].pageX;
                lastTime = now;
                
                container.scrollLeft = touchScrollLeft - walk;
            }, { passive: true });

            container.addEventListener('touchend', () => {
                beginMomentumTracking();
            }, { passive: true });

            // Momentum scrolling
            function beginMomentumTracking() {
                cancelMomentumTracking();
                momentumID = requestAnimationFrame(momentumLoop);
            }

            function cancelMomentumTracking() {
                if (momentumID) {
                    cancelAnimationFrame(momentumID);
                }
            }

            function momentumLoop() {
                if (Math.abs(velocity) > 0.5) {
                    container.scrollLeft -= velocity * 15;
                    velocity *= 0.92; // Friction
                    momentumID = requestAnimationFrame(momentumLoop);
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.custom-data-table').forEach(tableRoot => {
                const id = tableRoot.id;
                const viewBtn = document.getElementById(`${id}-view-btn`);
                const colMenu = document.getElementById(`${id}-col-menu`);
                const table = document.getElementById(`${id}-table`);
                const scrollContainer = document.getElementById(`${id}-scroll-container`);
                
                // Initialize drag scroll
                if (scrollContainer) {
                    initDragScroll(scrollContainer);
                }
                
                // Persistence Key: Use window.location.pathname + table ID to make it unique per page/table
                const storageKey = `dt_visibility_${window.location.pathname}_${id}`;
                
                // Toggle Menu
                viewBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    colMenu.classList.toggle('hidden');
                });

                // Close Menus on Click Outside
                document.addEventListener('click', (e) => {
                    if (!tableRoot.contains(e.target)) {
                        colMenu.classList.add('hidden');
                    }
                    if(!e.target.closest('th')) {
                         document.querySelectorAll('.header-menu').forEach(el => el.classList.add('hidden'));
                    }
                });

                // Toggle Columns Logic
                const toggles = colMenu.querySelectorAll('.column-toggle');
                
                function saveVisibilityState() {
                    const state = {};
                    toggles.forEach(toggle => {
                        state[toggle.dataset.index] = toggle.checked;
                    });
                    localStorage.setItem(storageKey, JSON.stringify(state));
                }

                function loadVisibilityState() {
                    const saved = localStorage.getItem(storageKey);
                    if (saved) {
                        try {
                            const state = JSON.parse(saved);
                            toggles.forEach(toggle => {
                                if (state.hasOwnProperty(toggle.dataset.index)) {
                                    toggle.checked = state[toggle.dataset.index];
                                    // Trigger the UI update
                                    applyVisibility(toggle.dataset.index, toggle.checked);
                                }
                            });
                        } catch (e) {
                            console.error('Error loading table visibility state:', e);
                        }
                    }
                }

                function applyVisibility(index, isVisible) {
                    // Toggle Header
                    const th = table.querySelector(`th[data-col-index="${index}"]`);
                    if(th) th.style.display = isVisible ? '' : 'none';
                    
                    // Toggle Cells (td)
                    const rows = table.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        const cells = row.children;
                        if(cells[index]) cells[index].style.display = isVisible ? '' : 'none';
                    });
                }

                toggles.forEach(toggle => {
                    toggle.addEventListener('change', function() {
                        applyVisibility(this.dataset.index, this.checked);
                        saveVisibilityState();
                    });
                });

                // Run on initial load
                loadVisibilityState();
            });
        });
    </script>
    @endpush
@endonce
