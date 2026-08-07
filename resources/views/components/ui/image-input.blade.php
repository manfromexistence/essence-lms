@props([
    'label' => '',
    'name',
    'value' => '',
    'required' => false,
    'helperText' => 'Recommended size: 400x400px',
    'persist' => false,
])

@php
    // Check for old values - prioritize file upload marker, then URL
    $urlValue = old($name . '_url', $value);
    // Clear blob URLs as they don't persist across reloads
    $urlValue = str_starts_with($urlValue ?? '', 'blob:') ? '' : $urlValue;
    
    // Check if this is a default asset (logo.png or favicon.ico)
    $isDefaultAsset = $urlValue && (
        str_contains($urlValue, '/logo.png') || 
        str_contains($urlValue, '/favicon.ico')
    );
    
    // Determine if the value is a storage path (not an external URL)
    $isStoragePath = $urlValue && !str_starts_with($urlValue, 'http://') && !str_starts_with($urlValue, 'https://');
    
    // Brand assets live directly under public/images (e.g. images/brand/...)
    $isBrandAsset = $isStoragePath && str_starts_with($urlValue, 'images/');
    
    // For preview: convert storage/brand paths to full URLs
    if ($isBrandAsset) {
        $previewUrl = asset($urlValue);
    } elseif ($isStoragePath) {
        // Remove 'public/' prefix if present (storage paths are stored as 'logos/file.png' not 'public/logos/file.png')
        $cleanPath = str_starts_with($urlValue, 'public/') ? substr($urlValue, 7) : $urlValue;
        $previewUrl = asset('storage/' . $cleanPath);
    } else {
        $previewUrl = $urlValue;
    }
    
    // For display in URL input: show the full URL
    $displayUrl = $previewUrl;
    
    // For submission: disable URL input for storage paths, brand assets, and default assets
    $shouldDisableUrlInput = $isStoragePath || $isDefaultAsset || $isBrandAsset;
    
    $hasFileError = $errors->has($name . '_file') || $errors->has($name);
@endphp

<div class="space-y-2 image-input-group custom-image-group" id="image-input-{{ $name }}" data-persist="{{ $persist ? 'true' : 'false' }}" data-name="{{ $name }}">
    @if($label)
        <label class="block text-sm font-semibold text-gray-700">
            {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    <div class="relative">
        <div class="w-48 h-48 rounded-xl border-2 border-dashed {{ $hasFileError ? 'border-red-400' : 'border-gray-300' }} bg-gray-50 flex flex-col items-center justify-center overflow-hidden relative transition-all group hover:border-bd-green">
            
            <!-- Default Placeholder -->
            <div id="{{ $name }}-placeholder" class="flex flex-col items-center text-gray-400 p-4 text-center {{ $previewUrl ? 'hidden' : '' }}">
                <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-xs">Click or drag</span>
            </div>

            <!-- Image Preview -->
            <img id="{{ $name }}-preview" src="{{ $previewUrl }}" 
                 class="w-full h-full object-cover {{ $previewUrl ? '' : 'hidden' }}">

            <!-- Overlay on hover -->
            <label for="{{ $name }}-file" class="absolute inset-0 bg-[#e9e9e9] bg-opacity-40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity cursor-pointer">
                <div class="border bg-white text-gray-900 px-4 py-2 rounded-lg font-medium text-sm hover:bg-gray-100 shadow-sm">
                    {{ $previewUrl ? 'Change Image' : 'Upload Image' }}
                </div>
            </label>
            
            <!-- File input - OUTSIDE the label to avoid nesting issues -->
            <input type="file" 
                   name="{{ $name }}_file" 
                   id="{{ $name }}-file" 
                   class="hidden" 
                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp,image/x-icon,.ico"
                   onchange="handleImageUpload(this, '{{ $name }}')">
        </div>
    </div>

    <div class="space-y-1 mt-3">
        <label class="block text-xs font-medium text-gray-500">
            Image URL (or upload above)
            @if($isDefaultAsset)
                <span class="text-gray-400 font-normal">- Using default image</span>
            @elseif($shouldDisableUrlInput)
                <span class="text-gray-400 font-normal">- Current image shown</span>
            @endif
        </label>
        <!-- URL input - disabled when showing existing storage path or default asset to prevent validation errors -->
        <input 
            type="text" 
            @if(!$shouldDisableUrlInput) name="{{ $name }}_url" @endif
            id="{{ $name }}-url"
            value="{{ $displayUrl }}"
            oninput="handleUrlInput(this, '{{ $name }}')"
            placeholder="Paste image URL here..."
            {{ $shouldDisableUrlInput ? 'disabled' : '' }}
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent outline-none transition-all {{ $shouldDisableUrlInput ? 'bg-gray-100 text-gray-500' : 'bg-white' }}"
        >
        @if($helperText)
            <p class="text-[10px] text-gray-400">{{ $helperText }}</p>
        @endif
    </div>

    @error($name . '_file')
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
    @enderror
    @error($name . '_url')
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
    @enderror
    @error($name)
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
    @enderror
    
    {{-- Debug info for development --}}
    @if(config('app.debug') && ($errors->has($name . '_file') || $errors->has($name . '_url') || $errors->has($name)))
        <details class="mt-2 text-xs text-gray-500" open>
            <summary class="cursor-pointer hover:text-gray-700">Debug Info</summary>
            <div class="mt-1 p-2 bg-gray-100 rounded text-xs font-mono">
                <p>File field: {{ $name }}_file</p>
                <p>URL field: {{ $name }}_url</p>
                @if($errors->has($name . '_file'))
                    <p class="text-red-600">File error: {{ $errors->first($name . '_file') }}</p>
                @endif
                @if($errors->has($name . '_url'))
                    <p class="text-red-600">URL error: {{ $errors->first($name . '_url') }}</p>
                @endif
            </div>
        </details>
    @endif
</div>

@once
    @push('scripts')
    <script>
        function getImageStorageKey(name) {
            return `image_persist_${window.location.pathname}_${name}`;
        }

        function handleImageUpload(input, name) {
            const file = input.files[0];
            const group = input.closest('.custom-image-group');
            const isPersist = group && group.dataset.persist === 'true';

            console.log('handleImageUpload called', { name, file, hasFile: !!file });

            if (file) {
                console.log('File details:', { 
                    name: file.name, 
                    size: file.size, 
                    type: file.type 
                });

                // Create Object URL for preview only
                const objectUrl = URL.createObjectURL(file);
                
                // Update Preview (Scope to group)
                const preview = group.querySelector('img[id$="-preview"]');
                const placeholder = group.querySelector('div[id$="-placeholder"]');
                // Find URL input by ID instead of name attribute (since it might not have name when disabled)
                const urlInput = group.querySelector(`input[type="text"][id="${name}-url"]`);
                
                console.log('Elements found:', { 
                    preview: !!preview, 
                    placeholder: !!placeholder, 
                    urlInput: !!urlInput 
                });
                
                if (preview) {
                    preview.src = objectUrl;
                    preview.classList.remove('hidden');
                }
                if (placeholder) {
                    placeholder.classList.add('hidden');
                }
                
                // IMPORTANT: Clear and disable URL input when file is selected
                // This prevents sending invalid blob: URLs or old values
                if(urlInput) {
                    urlInput.value = ''; 
                    urlInput.disabled = true;
                    urlInput.removeAttribute('name'); // Remove name attribute to prevent submission
                    urlInput.placeholder = 'File selected - URL input disabled';
                    urlInput.classList.add('bg-gray-100', 'text-gray-500');
                    urlInput.classList.remove('bg-white');
                }

                if (isPersist) {
                    // Clear any stored URL since we're using a file now
                    localStorage.removeItem(getImageStorageKey(name));
                }
                
                console.log('File upload handled successfully');
            } else {
                console.warn('No file selected');
            }
        }

        function handleUrlInput(input, name) {
            const url = input.value;
            const group = input.closest('.custom-image-group');
            const preview = group.querySelector('img[id$="-preview"]');
            const placeholder = group.querySelector('div[id$="-placeholder"]');
            const isPersist = group && group.dataset.persist === 'true';
            
            // Enable the input and add name attribute when user starts typing
            if (input.disabled) {
                input.disabled = false;
                input.setAttribute('name', name + '_url');
                input.classList.remove('bg-gray-100', 'text-gray-500');
                input.classList.add('bg-white');
            }
            
            if (url) {
                if (preview) {
                    preview.src = url;
                    preview.classList.remove('hidden');
                }
                if (placeholder) {
                    placeholder.classList.add('hidden');
                }
                if (isPersist && !url.startsWith('blob:')) {
                    localStorage.setItem(getImageStorageKey(name), url);
                }
            } else {
                if (preview) {
                    preview.classList.add('hidden');
                    preview.src = '';
                }
                if (placeholder) {
                    placeholder.classList.remove('hidden');
                }
                if (isPersist) {
                    localStorage.removeItem(getImageStorageKey(name));
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const groups = document.querySelectorAll('.custom-image-group');
            groups.forEach(group => {
                const name = group.dataset.name;
                const isPersist = group.dataset.persist === 'true';
                const urlInput = group.querySelector('input[type="text"]');

                if (isPersist && urlInput) {
                    const savedValue = localStorage.getItem(getImageStorageKey(name));
                    if (savedValue !== null && savedValue !== "") {
                        // Only override if current value is empty
                        if (!urlInput.value) {
                            urlInput.value = savedValue;
                            handleUrlInput(urlInput, name);
                        }
                    }

                    // Save on input and change
                    urlInput.addEventListener('input', () => {
                        const url = urlInput.value;
                        if (!url.startsWith('blob:')) {
                             localStorage.setItem(getImageStorageKey(name), url);
                        } else {
                             localStorage.removeItem(getImageStorageKey(name));
                        }
                    });
                    urlInput.addEventListener('change', () => {
                        const url = urlInput.value;
                        if (!url.startsWith('blob:')) {
                             localStorage.setItem(getImageStorageKey(name), url);
                        }
                    });
                }
            });
        });
    </script>
    @endpush
@endonce
