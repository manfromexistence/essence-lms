@props([
    'label' => '',
    'name',
    'confirm' => null,
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'autocomplete' => 'new-password',
    'helperText' => null,
    'showRequirements' => true,
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
            @if($name !== 'current_password')
                minlength="12"
                pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{12,}"
                title="Use at least 12 characters with uppercase, lowercase, a number, and a symbol."
            @endif
            @if($required) required @endif
            oninput="updatePasswordStrength(this)"
            {{ $attributes->merge(['class' => 'w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-24 text-gray-900 placeholder:text-gray-400 focus:border-green-700 focus:ring-green-700 transition-all outline-none']) }}
        >

        <button type="button" data-password-toggle="{{ $name }}" aria-label="Show password" aria-pressed="false"
            onclick="togglePasswordVisibility('{{ $name }}', this)"
            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-green-800 focus:outline-none focus:ring-2 focus:ring-green-700">
            <svg data-password-eye="show" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <svg data-password-eye="hide" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 002.828 2.828M9.88 9.88A3 3 0 0114.12 14.12M6.228 6.228C4.476 7.602 3.2 9.558 2.458 12c1.274 4.057 5.064 7 9.542 7 1.337 0 2.609-.274 3.77-.772M17.772 17.772C19.524 16.398 20.8 14.442 21.542 12c-.743-2.441-2.018-4.398-3.77-5.772A9.953 9.953 0 0012 5c-.932 0-1.833.133-2.685.383"/></svg>
        </button>

        {{-- In-box colored strength meter: a filled bar + label, no text below the input --}}
        <div class="absolute bottom-0 left-0 right-0 mx-4 h-1 overflow-hidden rounded-b-lg">
            <div id="{{ $name }}-strength-bar" class="h-full w-0 rounded-b-lg transition-all duration-300"></div>
        </div>
        <span id="{{ $name }}-strength-label" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-semibold tracking-wide uppercase"></span>
    </div>

    @if($helperText)
        <p class="text-xs text-gray-500 mt-1">{{ $helperText }}</p>
    @elseif($showRequirements && $name !== 'current_password')
        <p class="text-xs text-gray-500 mt-1">At least 12 characters with uppercase, lowercase, a number, and one symbol (e.g. ! @ # $).</p>
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

    window.togglePasswordVisibility = function (id, button) {
        const input = document.getElementById(id);
        if (!input) return;
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        button.setAttribute('aria-pressed', String(!visible));
        button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
        const show = button.querySelector('[data-password-eye="show"]');
        const hide = button.querySelector('[data-password-eye="hide"]');
        if (show) show.classList.toggle('hidden', !visible);
        if (hide) hide.classList.toggle('hidden', visible);
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('input[type="password"]').forEach(input => {
            input.dispatchEvent(new Event('input'));
        });

    });
</script>
@endpush
