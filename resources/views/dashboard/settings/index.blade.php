@extends('layouts.admin')

@section('title', 'System Settings')
@section('page-title', 'System Settings')

@push('scripts')
<script>
    // Sync color picker <-> text input <-> swatch with hex validation
    document.addEventListener('DOMContentLoaded', function() {
        const colorFields = [
            { picker: 'theme_primary_color', text: 'theme_primary_color_text', swatch: 'theme_primary_color_swatch' },
            { picker: 'theme_primary_foreground', text: 'theme_primary_foreground_text', swatch: 'theme_primary_foreground_swatch' },
            { picker: 'theme_secondary_color', text: 'theme_secondary_color_text', swatch: 'theme_secondary_color_swatch' },
            { picker: 'theme_secondary_foreground', text: 'theme_secondary_foreground_text', swatch: 'theme_secondary_foreground_swatch' }
        ];

        const isHex = (v) => /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(v.trim());
        const normalizeHex = (v) => {
            v = v.trim();
            if (v[0] !== '#') v = '#' + v;
            if (/^#[0-9a-fA-F]{3}$/.test(v)) {
                v = '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
            }
            return v;
        };

        colorFields.forEach(({ picker, text, swatch }) => {
            const pickerEl = document.getElementById(picker);
            const textEl = document.getElementById(text);
            const swatchEl = document.getElementById(swatch);

            if (!pickerEl || !textEl) return;

            const apply = (color) => {
                pickerEl.value = color;
                textEl.value = color;
                if (swatchEl) swatchEl.style.backgroundColor = color;
            };

            // Picker -> text + swatch
            pickerEl.addEventListener('input', function() {
                textEl.value = this.value;
                if (swatchEl) swatchEl.style.backgroundColor = this.value;
                textEl.classList.remove('border-red-400');
            });

            // Text -> picker + swatch (validate as you type)
            textEl.addEventListener('input', function() {
                const val = this.value.trim();
                if (isHex(val)) {
                    const normalized = normalizeHex(val);
                    pickerEl.value = normalized;
                    if (swatchEl) swatchEl.style.backgroundColor = normalized;
                    this.classList.remove('border-red-400', 'ring-2', 'ring-red-200');
                } else if (val !== '') {
                    this.classList.add('border-red-400', 'ring-2', 'ring-red-200');
                }
            });

            textEl.addEventListener('change', function() {
                const val = this.value.trim();
                if (isHex(val)) {
                    apply(normalizeHex(val));
                } else if (val === '') {
                    this.classList.remove('border-red-400', 'ring-2', 'ring-red-200');
                }
            });
        });
    });
</script>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">
    <form action="{{ route('dashboard.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        <!-- Institution Settings -->
        <x-ui.card>
            <x-ui.card-header>
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <i class="fas fa-university text-lg"></i>
                    </div>
                    <div>
                        <x-ui.card-title>Institution Information</x-ui.card-title>
                        <x-ui.card-description>Basic information about your institution</x-ui.card-description>
                    </div>
                </div>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <x-ui.label for="institution_name">Institution Name</x-ui.label>
                        <x-ui.input type="text" name="institution_name" id="institution_name" 
                            value="{{ $settings['institution']['institution_name']['value'] ?? '' }}" />
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="institution_email">Email Address</x-ui.label>
                        <x-ui.input type="email" name="institution_email" id="institution_email" 
                            value="{{ $settings['institution']['institution_email']['value'] ?? '' }}" />
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="institution_phone">Phone Number</x-ui.label>
                        <x-ui.input type="text" name="institution_phone" id="institution_phone" 
                            value="{{ $settings['institution']['institution_phone']['value'] ?? '' }}" />
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="institution_website">Website</x-ui.label>
                        <x-ui.input type="url" name="institution_website" id="institution_website" 
                            value="{{ $settings['institution']['institution_website']['value'] ?? '' }}" />
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <x-ui.label for="institution_address">Address</x-ui.label>
                        <x-ui.textarea name="institution_address" id="institution_address" rows="2">
                            {{ $settings['institution']['institution_address']['value'] ?? '' }}
                        </x-ui.textarea>
                    </div>

                    <!-- Logo Upload -->
                    <div class="md:col-span-2">
                        @php
                            $settingsService = app(\App\Services\SettingsService::class);
                            $currentLogo = $settings['institution']['institution_logo']['value'] ?? '';
                            // Resolve via the service so brand asset paths (images/...) get a full URL
                            $logoPreview = $settingsService->getLogo();
                            $logoHelperText = empty($currentLogo) || str_contains($currentLogo, 'logo.png')
                                ? 'Currently using default logo.png. Upload a file to replace it. Recommended size: 200x200px. Supports JPG, PNG, SVG, WebP'
                                : 'Recommended size: 200x200px. Supports JPG, PNG, SVG, WebP';
                        @endphp
                        <x-ui.image-input 
                            name="institution_logo" 
                            label="Institution Logo"
                            :value="$logoPreview"
                            :helperText="$logoHelperText"
                        />
                    </div>

                    <!-- Favicon Upload -->
                    <div class="md:col-span-2">
                        @php
                            $currentFavicon = $settings['institution']['institution_favicon']['value'] ?? '';
                            // Resolve via the service so brand asset paths (images/...) get a full URL
                            $faviconPreview = $settingsService->getFavicon();
                            $faviconHelperText = empty($currentFavicon)
                                ? 'Currently using the Dhaka IT Institute favicon. Upload a file to replace it. Recommended size: 32x32px or 64x64px. Supports ICO, PNG, SVG'
                                : 'Recommended size: 32x32px or 64x64px. Supports ICO, PNG, SVG';
                        @endphp
                        <x-ui.image-input 
                            name="institution_favicon" 
                            label="Favicon"
                            :value="$faviconPreview"
                            :helperText="$faviconHelperText"
                        />
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Student ID Settings -->
        <x-ui.card>
            <x-ui.card-header>
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-600">
                        <i class="fas fa-id-card text-lg"></i>
                    </div>
                    <div>
                        <x-ui.card-title>Student ID Format</x-ui.card-title>
                        <x-ui.card-description>Configure how student registration numbers are generated</x-ui.card-description>
                    </div>
                </div>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <x-ui.label for="student_id_format">ID Format Pattern</x-ui.label>
                        <x-ui.input type="text" name="student_id_format" id="student_id_format" 
                            value="{{ $settings['student']['student_id_format']['value'] ?? '{YEAR}{BATCH}{SEQ:4}' }}" />
                        <p class="text-[0.8rem] text-muted-foreground">Tokens: {YEAR}, {MONTH}, {BATCH}, {SEQ:n}</p>
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="student_id_sequence_start">Sequence Start Number</x-ui.label>
                        <x-ui.input type="number" name="student_id_sequence_start" id="student_id_sequence_start" min="1"
                            value="{{ $settings['student']['student_id_sequence_start']['value'] ?? 1 }}" />
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- SMS Gateway Settings -->
        <x-ui.card>
            <x-ui.card-header>
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 rounded-lg bg-purple-500/10 flex items-center justify-center text-purple-600">
                        <i class="fas fa-comment-alt text-lg"></i>
                    </div>
                    <div>
                        <x-ui.card-title>SMS Gateway</x-ui.card-title>
                        <x-ui.card-description>Configure SMS notification settings</x-ui.card-description>
                    </div>
                </div>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="mb-6 flex items-center space-x-2">
                    <input type="checkbox" id="sms_gateway_enabled" name="sms_gateway_enabled" value="1"
                        {{ ($settings['sms']['sms_gateway_enabled']['value'] ?? false) ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-input text-primary focus:ring-ring">
                    <x-ui.label for="sms_gateway_enabled" class="font-normal">Enable SMS Notifications</x-ui.label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <x-ui.select name="sms_gateway_provider" label="SMS Provider">
                            <option value="twilio" {{ ($settings['sms']['sms_gateway_provider']['value'] ?? '') == 'twilio' ? 'selected' : '' }}>Twilio</option>
                            <option value="nexmo" {{ ($settings['sms']['sms_gateway_provider']['value'] ?? '') == 'nexmo' ? 'selected' : '' }}>Nexmo</option>
                            <option value="custom" {{ ($settings['sms']['sms_gateway_provider']['value'] ?? '') == 'custom' ? 'selected' : '' }}>Custom</option>
                        </x-ui.select>
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="sms_sender_id">Sender ID</x-ui.label>
                        <x-ui.input type="text" name="sms_sender_id" id="sms_sender_id" 
                            value="{{ $settings['sms']['sms_sender_id']['value'] ?? '' }}" />
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="sms_gateway_api_key">API Key</x-ui.label>
                        <x-ui.input type="password" name="sms_gateway_api_key" id="sms_gateway_api_key" 
                            value="{{ $settings['sms']['sms_gateway_api_key']['value'] ?? '' }}" />
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="sms_gateway_api_secret">API Secret</x-ui.label>
                        <x-ui.input type="password" name="sms_gateway_api_secret" id="sms_gateway_api_secret" 
                            value="{{ $settings['sms']['sms_gateway_api_secret']['value'] ?? '' }}" />
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Attendance & Payment Settings -->
        <x-ui.card>
             <x-ui.card-header>
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-600">
                        <i class="fas fa-coins text-lg"></i>
                    </div>
                    <div>
                        <x-ui.card-title>Attendance & Payment</x-ui.card-title>
                        <x-ui.card-description>Configure attendance thresholds and payment settings</x-ui.card-description>
                    </div>
                </div>
            </x-ui.card-header>
            <x-ui.card-content>
                 <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <x-ui.label for="attendance_threshold">Attendance Threshold (%)</x-ui.label>
                        <x-ui.input type="number" name="attendance_threshold" id="attendance_threshold" min="0" max="100"
                            value="{{ $settings['attendance']['attendance_threshold']['value'] ?? 75 }}" />
                        <p class="text-[0.8rem] text-muted-foreground">Students below this will be flagged</p>
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="currency">Currency</x-ui.label>
                        <x-ui.input type="text" name="currency" id="currency" 
                            value="{{ $settings['payment']['currency']['value'] ?? 'BDT' }}" />
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="receipt_prefix">Receipt Prefix</x-ui.label>
                        <x-ui.input type="text" name="receipt_prefix" id="receipt_prefix" 
                            value="{{ $settings['payment']['receipt_prefix']['value'] ?? 'RCP' }}" />
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="invoice_prefix">Invoice Prefix</x-ui.label>
                        <x-ui.input type="text" name="invoice_prefix" id="invoice_prefix" 
                            value="{{ $settings['payment']['invoice_prefix']['value'] ?? 'INV' }}" />
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Theme Settings -->
        <x-ui.card>
            <x-ui.card-header>
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 rounded-lg bg-pink-500/10 flex items-center justify-center text-pink-600">
                        <i class="fas fa-palette text-lg"></i>
                    </div>
                    <div>
                        <x-ui.card-title>Theme Colors</x-ui.card-title>
                        <x-ui.card-description>Customize the primary and secondary colors of your website</x-ui.card-description>
                    </div>
                </div>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @php
                        $themeColors = [
                            'theme_primary_color' => ['label' => 'Primary Background Color', 'hint' => 'Main brand background color', 'default' => '#168536'],
                            'theme_primary_foreground' => ['label' => 'Primary Text Color', 'hint' => 'Text color on primary background', 'default' => '#ffffff'],
                            'theme_secondary_color' => ['label' => 'Secondary Background Color', 'hint' => 'Accent background color', 'default' => '#171717'],
                            'theme_secondary_foreground' => ['label' => 'Secondary Text Color', 'hint' => 'Text color on secondary background', 'default' => '#ffffff'],
                        ];
                    @endphp
                    @foreach($themeColors as $key => $meta)
                        @php
                            $colorValue = $settings['theme'][$key]['value'] ?? $meta['default'];
                        @endphp
                        <div class="space-y-2">
                            <x-ui.label for="{{ $key }}">{{ $meta['label'] }}</x-ui.label>
                            <div class="flex gap-3 items-center">
                                <input type="color" name="{{ $key }}" id="{{ $key }}"
                                    value="{{ $colorValue }}"
                                    class="h-10 w-14 rounded border border-gray-300 cursor-pointer bg-transparent p-1" />
                                <x-ui.input type="text" id="{{ $key }}_text"
                                    value="{{ $colorValue }}"
                                    placeholder="{{ $meta['default'] }}" class="flex-1 font-mono" maxlength="7" />
                                <span class="h-10 w-10 shrink-0 rounded-lg border border-gray-200 shadow-sm" id="{{ $key }}_swatch" style="background-color: {{ $colorValue }}"></span>
                            </div>
                            <p class="text-[0.8rem] text-muted-foreground">{{ $meta['hint'] }}</p>
                        </div>
                    @endforeach
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Submit Button -->
        <div class="flex justify-end gap-4">
            <x-ui.button type="button" variant="outline" as="a" href="{{ route('dashboard.settings.clear-cache') }}">
                Clear Cache
            </x-ui.button>
            <x-ui.button type="submit">
                Save Settings
            </x-ui.button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Debug form submission
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[action="{{ route('dashboard.settings.update') }}"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('Form submitting...');
                const formData = new FormData(form);
                
                // Log all form data
                console.log('Form data entries:');
                for (let [key, value] of formData.entries()) {
                    if (value instanceof File) {
                        console.log(`${key}:`, {
                            name: value.name,
                            size: value.size,
                            type: value.type
                        });
                    } else {
                        console.log(`${key}:`, value);
                    }
                }
                
                // Check for file inputs
                const logoFile = formData.get('institution_logo_file');
                const faviconFile = formData.get('institution_favicon_file');
                
                if (logoFile && logoFile.size > 0) {
                    console.log('Logo file found:', logoFile.name, logoFile.size, 'bytes');
                }
                if (faviconFile && faviconFile.size > 0) {
                    console.log('Favicon file found:', faviconFile.name, faviconFile.size, 'bytes');
                }
            });
        }
    });
</script>
@endpush
@endsection

