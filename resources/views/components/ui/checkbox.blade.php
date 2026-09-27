@props(['checked' => false, 'name' => '', 'value' => '', 'id' => null])

@php
    $id = $id ?? 'checkbox-' . uniqid();
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'inline-flex items-center']) }}>
    <input
        type="checkbox"
        id="{{ $id }}"
        @if($name !== '') name="{{ $name }}" @endif
        @if($value !== '') value="{{ $value }}" @endif
        {{ $checked ? 'checked' : '' }}
        {{ $attributes->except('class')->merge([
            'class' => 'h-4 w-4 shrink-0 cursor-pointer rounded border border-gray-300 bg-white transition-colors accent-[var(--color-primary)] checked:border-[var(--color-primary)] checked:bg-[var(--color-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)]/40 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        ]) }}
    >
    <label for="{{ $id }}" class="ml-2 text-sm font-medium leading-none cursor-pointer select-none">
        {{ $slot }}
    </label>
</div>
