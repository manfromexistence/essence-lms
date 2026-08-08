@props([
    'label' => '',
    'name',
    'confirm' => null,
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'autocomplete' => 'new-password',
    'helperText' => null,
])

<div class="space-y-1.5 input-group-{{ $name }}" id="input-group-{{ $name }}" data-name="{{ $name }}">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-semibold text-gray-700">
            {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    <div class="relative">
        <input
            type="password"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            autocomplete="{{ $autocomplete }}"
            @if($required) required @endif
            oninput="updatePasswordStrength(this)"
            {{ $attributes->merge(['class' => 'w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-24 text-gray-900 placeholder:text-gray-400 focus:border-green-700 focus:ring-green-700 transition-all outline-none']) }}
        >

        {{-- In-box colored strength meter: a filled bar + label, no text below the input --}}
        <div class="absolute bottom-0 left-0 right-0 mx-4 h-1 overflow-hidden rounded-b-lg">
            <div id="{{ $name }}-strength-bar" class="h-full w-0 rounded-b-lg transition-all duration-300"></div>
        </div>
        <span id="{{ $name }}-strength-label" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-semibold tracking-wide uppercase"></span>
    </div>

    @if($helperText)
        <p class="text-xs text-gray-500 mt-1">{{ $helperText }}</p>
    @endif

    @error($name)
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>

@push('scripts')
<script>
    // Shared password strength logic used by every password field on the page.
    window.passwordStrength = function (value) {
        let score = 0;
        if (value.length >= 8) score += 1;
        if (value.length >= 12) score += 1;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score += 1;
        if (/\d/.test(value)) score += 1;
        if (/[^A-Za-z0-9]/.test(value)) score += 1;
        return Math.min(score, 4);
    };

    window.passwordStrengthMeta = [
        { label: '', pct: 0, color: 'bg-gray-300', text: 'text-gray-400' },
        { label: 'Weak', pct: 25, color: 'bg-red-500', text: 'text-red-600' },
        { label: 'Fair', pct: 50, color: 'bg-orange-500', text: 'text-orange-600' },
        { label: 'Good', pct: 75, color: 'bg-yellow-500', text: 'text-yellow-700' },
        { label: 'Strong', pct: 100, color: 'bg-green-600', text: 'text-green-700' },
    ];

    window.updatePasswordStrength = function (input) {
        const bar = document.getElementById(input.id + '-strength-bar');
        const label = document.getElementById(input.id + '-strength-label');
        if (!bar || !label) return;

        const level = window.passwordStrength(input.value || '');
        const meta = window.passwordStrengthMeta[level];

        bar.className = 'h-full rounded-b-lg transition-all duration-300 ' + meta.color;
        bar.style.width = meta.pct + '%';
        label.textContent = meta.label;
        label.className = 'pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-semibold tracking-wide uppercase ' + meta.text;
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('input[type="password"]').forEach(input => {
            input.dispatchEvent(new Event('input'));
        });
    });
</script>
@endpush
