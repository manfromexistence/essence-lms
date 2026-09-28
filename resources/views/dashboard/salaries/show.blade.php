@extends('layouts.admin')

@section('title', 'Salary Payment')
@section('page-title', 'Salary Payment Details')
@section('page-description', 'A single recorded salary payment')

@section('content')
    <div class="max-w-2xl space-y-6">
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>
                    {{ $salary->teacher->user->name ?? 'Teacher #' . $salary->teacher_id }}
                </x-ui.card-title>
                <x-ui.card-description>
                    Paid {{ optional($salary->payment_date)->format('M d, Y') ?? '—' }}
                </x-ui.card-description>
            </x-ui.card-header>

            <x-ui.card-content>
                <dl class="divide-y divide-gray-100">
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Amount</dt>
                        <dd class="text-lg font-semibold">৳ {{ number_format((float) $salary->amount, 2) }}</dd>
                    </div>
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Payment Date</dt>
                        <dd class="text-sm">{{ optional($salary->payment_date)->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Payment Method</dt>
                        <dd class="text-sm">{{ $salary->payment_method ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Recorded</dt>
                        <dd class="text-sm">{{ $salary->created_at?->format('M d, Y g:i A') ?? '—' }}</dd>
                    </div>
                    @if ($salary->notes)
                        <div class="py-3">
                            <dt class="text-sm text-muted-foreground mb-1">Notes</dt>
                            <dd class="text-sm whitespace-pre-line">{{ $salary->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </x-ui.card-content>

            <x-ui.card-footer class="flex items-center gap-3">
                <x-ui.button as="a" href="{{ route('dashboard.salaries.edit', $salary) }}">
                    <i class="fas fa-pen mr-2"></i> Edit
                </x-ui.button>
                @if ($salary->teacher)
                    <x-ui.button variant="outline" as="a"
                        href="{{ route('dashboard.salaries.history', $salary->teacher) }}">
                        <i class="fas fa-clock-rotate-left mr-2"></i> Teacher History
                    </x-ui.button>
                @endif
                <x-ui.button variant="outline" as="a" href="{{ route('dashboard.salaries.index') }}">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Salaries
                </x-ui.button>
            </x-ui.card-footer>
        </x-ui.card>
    </div>
@endsection
