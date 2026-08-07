@props([
    'id' => null,
    'name' => null,
    'checked' => false,
    'disabled' => false,
])

<label class="relative inline-flex items-center cursor-pointer">
    <input
        type="checkbox"
        @if($id) id="{{ $id }}" @endif
        @if($name) name="{{ $name }}" @endif
        class="sr-only peer"
        @if($checked) checked @endif
        @if($disabled) disabled @endif
        {{ $attributes }}
    />
    <div class="peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 bg-input peer-checked:bg-[var(--color-primary)] peer-focus:ring-2 peer-focus:ring-ring peer-focus:ring-offset-2">
        <span class="pointer-events-none block h-5 w-5 rounded-full bg-background shadow-lg ring-0 transition-transform translate-x-0 peer-checked:translate-x-5"></span>
    </div>
</label>
