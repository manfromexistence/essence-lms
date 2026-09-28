@extends('layouts.admin')

@section('title', 'Record Salary Payment')
@section('page-title', 'Record Salary Payment')
@section('page-description', 'Record a salary payment made to a teacher')

@section('content')
    <div class="max-w-2xl">
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Payment Details</x-ui.card-title>
                <x-ui.card-description>
                    Recording a payment twice for the same teacher and month is blocked.
                </x-ui.card-description>
            </x-ui.card-header>

            <x-ui.card-content>
                <form action="{{ route('dashboard.salaries.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div class="space-y-2">
                        <x-ui.label for="teacher_id">Teacher <span class="text-destructive">*</span></x-ui.label>
                        <x-ui.select-native name="teacher_id" id="teacher_id" required>
                            <option value="">Select a teacher</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>
                                    {{ $teacher->user->name ?? 'Teacher #' . $teacher->id }}
                                </option>
                            @endforeach
                        </x-ui.select-native>
                        @error('teacher_id')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <x-ui.label for="amount">Amount (৳) <span class="text-destructive">*</span></x-ui.label>
                            <x-ui.input type="number" step="0.01" min="0" name="amount" id="amount"
                                value="{{ old('amount') }}" required />
                            @error('amount')
                                <p class="text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <x-ui.label for="payment_date">Payment Date <span class="text-destructive">*</span></x-ui.label>
                            <x-ui.input type="date" name="payment_date" id="payment_date"
                                value="{{ old('payment_date', now()->format('Y-m-d')) }}" required />
                            @error('payment_date')
                                <p class="text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="payment_method">Payment Method</x-ui.label>
                        <x-ui.select-native name="payment_method" id="payment_method">
                            <option value="">Select a method</option>
                            @foreach (['Cash', 'bKash', 'Nagad', 'Bank Transfer'] as $method)
                                <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ $method }}
                                </option>
                            @endforeach
                        </x-ui.select-native>
                        @error('payment_method')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="notes">Notes</x-ui.label>
                        <x-ui.textarea name="notes" id="notes" rows="3">{{ old('notes') }}</x-ui.textarea>
                        @error('notes')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <x-ui.button type="submit">
                            <i class="fas fa-money-bill-wave mr-2"></i> Record Payment
                        </x-ui.button>
                        <x-ui.button variant="outline" as="a" href="{{ route('dashboard.salaries.index') }}">
                            Cancel
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card-content>
        </x-ui.card>
    </div>
@endsection
