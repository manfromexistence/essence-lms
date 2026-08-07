@props(['checked' => false, 'name' => '', 'value' => '', 'id' => null])

@php
    $id = $id ?? 'checkbox-' . uniqid();
@endphp

<div class="inline-flex items-center">
    <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" {{ $checked ? 'checked' : '' }} 
           {{ $attributes->merge(['class' => 'h-4 w-4 shrink-0 rounded-sm border border-gray-300 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 accent-primary cursor-pointer']) }}>
    <label for="{{ $id }}" class="ml-2 text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer">
        {{ $slot }}
    </label>
</div>
