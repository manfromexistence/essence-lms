@props([
    'label' => '',
    'name',
    'options' => [], 
    'selected' => '',
    'required' => false,
    'helperText' => null,
    'persist' => false,
])

@php
    $selectedValue = old($name, $selected);
@endphp

<div class="{{ $label ? 'space-y-1.5' : '' }} custom-select-group relative" id="select-group-{{ $name }}" data-persist="{{ $persist ? 'true' : 'false' }}" data-name="{{ $name }}">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-semibold text-gray-700">
            {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    <!-- Native Hidden Select for Form Submission -->
    <select name="{{ $name }}" id="{{ $name }}" {{ $attributes->merge(['class' => 'opacity-0 absolute z-[-1] w-full h-full pointer-events-none']) }} @if($required) required @endif tabindex="-1">
        {{ $slot }}
        @foreach($options as $value => $label)
             <option value="{{ $value }}" {{ $selectedValue == $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <!-- Custom Dropdown UI -->
    <div class="relative">
        <button 
            type="button" 
            id="select-btn-{{ $name }}"
            onclick="toggleCustomSelect('{{ $name }}')"
            class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl text-left focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all outline-none flex items-center justify-between group"
        >
            <span id="select-text-{{ $name }}" class="text-gray-900 truncate">Select Option</span>
            <svg id="select-arrow-{{ $name }}" class="w-4 h-4 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Options -->
        <div 
            id="select-dropdown-{{ $name }}" 
            class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
            <ul id="select-list-{{ $name }}" class="py-1">
                <!-- Options will be populated via JS -->
            </ul>
        </div>
    </div>

    @if($helperText)
        <p class="text-xs text-gray-500 mt-1">{{ $helperText }}</p>
    @endif

    @error($name)
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all custom selects
            const selects = document.querySelectorAll('.custom-select-group');
            selects.forEach(group => {
                const name = group.id.replace('select-group-', '');
                initCustomSelect(name);

                // Persistence Load
                const persist = group.dataset.persist === 'true';
                const select = document.getElementById(name);
                if (persist && select) {
                    const savedValue = localStorage.getItem(getStorageKey(name));
                    // Only load if current value is empty or hasn't matched an option yet
                    if (savedValue && (select.value === "" || select.value === null)) {
                        selectOption(name, savedValue, null);
                    }
                }
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.custom-select-group')) {
                    document.querySelectorAll('[id^="select-dropdown-"]').forEach(el => {
                        el.classList.add('hidden');
                    });
                    document.querySelectorAll('[id^="select-arrow-"]').forEach(el => {
                        el.classList.remove('rotate-180');
                    });
                }
            });
        });

        function getStorageKey(name) {
            return `select_persist_${window.location.pathname}_${name}`;
        }

        function initCustomSelect(name) {
            const nativeSelect = document.getElementById(name);
            const group = document.getElementById('select-group-' + name);
            
            // Populate options from native select
            renderOptions(name);
        }

        function renderOptions(name) {
            const nativeSelect = document.getElementById(name);
            const list = document.getElementById('select-list-' + name);
            const textSpan = document.getElementById('select-text-' + name);
            
            list.innerHTML = '';
            let hasSelection = false;
            
            Array.from(nativeSelect.options).forEach(option => {
                if (option.hidden || (option.value === "" && option.disabled)) return;

                const li = document.createElement('li');
                li.className = 'px-4 py-2 cursor-pointer text-sm text-gray-800 transition-colors hover:bg-primary/10 hover:text-primary focus:bg-primary/10 focus:text-primary';
                li.textContent = option.textContent;
                li.onclick = () => selectOption(name, option.value, option.textContent);
                
                list.appendChild(li);

                if (option.selected && option.value !== "") {
                    textSpan.textContent = option.textContent;
                    textSpan.classList.remove('text-gray-500');
                    textSpan.classList.add('text-gray-900');
                    hasSelection = true;
                }
            });

            if (!hasSelection && nativeSelect.options.length > 0) {
                 const firstOpt = nativeSelect.options[0];
                 textSpan.textContent = firstOpt.textContent;
                 if(firstOpt.value === "") {
                     textSpan.classList.remove('text-gray-900');
                     textSpan.classList.add('text-gray-500');
                 } else {
                     textSpan.classList.remove('text-gray-500');
                     textSpan.classList.add('text-gray-900');
                 }
            }
        }

        function toggleCustomSelect(name) {
            const dropdown = document.getElementById('select-dropdown-' + name);
            const arrow = document.getElementById('select-arrow-' + name);
            const isHidden = dropdown.classList.contains('hidden');

            // Close all other dropdowns and reset their arrows
            document.querySelectorAll('[id^="select-dropdown-"]').forEach(el => {
                if(el.id !== 'select-dropdown-' + name) {
                    el.classList.add('hidden');
                    const otherName = el.id.replace('select-dropdown-', '');
                    const otherArrow = document.getElementById('select-arrow-' + otherName);
                    if(otherArrow) otherArrow.classList.remove('rotate-180');
                }
            });

            if (isHidden) {
                // Re-render options in case they changed (dynamic selects)
                renderOptions(name);
                dropdown.classList.remove('hidden');
                arrow.classList.add('rotate-180');
            } else {
                dropdown.classList.add('hidden');
                arrow.classList.remove('rotate-180');
            }
        }

        function selectOption(name, value, label) {
            const nativeSelect = document.getElementById(name);
            const group = document.getElementById('select-group-' + name);
            const textSpan = document.getElementById('select-text-' + name);
            const dropdown = document.getElementById('select-dropdown-' + name);
            const arrow = document.getElementById('select-arrow-' + name);
            const isPersist = group.dataset.persist === 'true';

            // Update Native Select
            nativeSelect.value = value;
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));

            // Persistence
            if (isPersist) {
                localStorage.setItem(getStorageKey(name), value);
            }

            // Update UI
            if (label) {
                textSpan.textContent = label;
            } else {
                // Look up label from options
                const option = Array.from(nativeSelect.options).find(o => o.value == value);
                if (option) {
                    textSpan.textContent = option.textContent;
                }
            }
            
            if (nativeSelect.value !== "") {
                textSpan.classList.remove('text-gray-500');
                textSpan.classList.add('text-gray-900');
            } else {
                textSpan.classList.remove('text-gray-900');
                textSpan.classList.add('text-gray-500');
            }
            
            // Close
            dropdown.classList.add('hidden');
            arrow.classList.remove('rotate-180');
        }


        // Export globally for dynamic updates
        window.initCustomSelect = initCustomSelect;
        window.renderOptions = renderOptions;
        window.selectOption = selectOption;
    </script>
    @endpush
@endonce
