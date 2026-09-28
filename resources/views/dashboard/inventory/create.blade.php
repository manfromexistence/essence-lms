@extends('layouts.admin')

@section('title', 'Add Inventory Item')
@section('page-title', 'Add Inventory Item')
@section('page-description', 'Add a new item to the institute inventory')

@section('content')
    <div class="max-w-2xl">
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Item Details</x-ui.card-title>
                <x-ui.card-description>
                    Set a low-stock threshold to be alerted when this item runs out.
                </x-ui.card-description>
            </x-ui.card-header>

            <x-ui.card-content>
                <form action="{{ route('dashboard.inventory.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div class="space-y-2">
                        <x-ui.label for="name">Item Name <span class="text-destructive">*</span></x-ui.label>
                        <x-ui.input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus />
                        @error('name')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="category">Category</x-ui.label>
                        <x-ui.select-native name="category" id="category">
                            <option value="">Select a category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected(old('category') === $category)>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </x-ui.select-native>
                        @error('category')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <x-ui.label for="quantity">Quantity <span class="text-destructive">*</span></x-ui.label>
                            <x-ui.input type="number" min="0" name="quantity" id="quantity"
                                value="{{ old('quantity', 0) }}" required />
                            @error('quantity')
                                <p class="text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <x-ui.label for="unit">Unit</x-ui.label>
                            <x-ui.input type="text" name="unit" id="unit" value="{{ old('unit') }}"
                                placeholder="pcs / box / kg" />
                            @error('unit')
                                <p class="text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <x-ui.label for="unit_price">Unit Price (৳) <span class="text-destructive">*</span></x-ui.label>
                            <x-ui.input type="number" step="0.01" min="0" name="unit_price" id="unit_price"
                                value="{{ old('unit_price') }}" required />
                            @error('unit_price')
                                <p class="text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <x-ui.label for="low_stock_threshold">Low Stock Threshold</x-ui.label>
                            <x-ui.input type="number" min="0" name="low_stock_threshold" id="low_stock_threshold"
                                value="{{ old('low_stock_threshold') }}" />
                            @error('low_stock_threshold')
                                <p class="text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <x-ui.label for="location">Storage Location</x-ui.label>
                            <x-ui.input type="text" name="location" id="location" value="{{ old('location') }}" />
                            @error('location')
                                <p class="text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="description">Description</x-ui.label>
                        <x-ui.textarea name="description" id="description" rows="3">{{ old('description') }}</x-ui.textarea>
                        @error('description')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <x-ui.button type="submit">
                            <i class="fas fa-plus mr-2"></i> Add Item
                        </x-ui.button>
                        <x-ui.button variant="outline" as="a" href="{{ route('dashboard.inventory.index') }}">
                            Cancel
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card-content>
        </x-ui.card>
    </div>
@endsection
