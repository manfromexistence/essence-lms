@props(['checked' => false, 'name' => '', 'value' => '', 'id' => null])

@php
    $id = $id ?? 'checkbox-' . uniqid();
@endphp

{{--
    Visual styling (size, radius, themed border, hover halo, brand fill, white
    tick, focus ring, disabled state) comes from the global "Themed checkboxes"
    block in resources/css/app.css. It is deliberately NOT duplicated here:

      * it has to apply to the ~20 views that hand-write <input type="checkbox">
        instead of using this component, and
      * those global rules are unlayered, so they beat Tailwind utilities
        anyway — repeating them here would only mislead the next reader.

    Only layout utilities belong on the element itself.
--}}
<div {{ $attributes->only('class')->merge(['class' => 'inline-flex items-center']) }}>
    <input
        type="checkbox"
        id="{{ $id }}"
        @if($name !== '') name="{{ $name }}" @endif
        @if($value !== '') value="{{ $value }}" @endif
        {{ $checked ? 'checked' : '' }}
        {{ $attributes->except('class')->merge([
            'class' => 'shrink-0 cursor-pointer disabled:cursor-not-allowed',
        ]) }}
    >
    <label for="{{ $id }}" class="ml-2 text-sm font-medium leading-none cursor-pointer select-none">
        {{ $slot }}
    </label>
</div>
