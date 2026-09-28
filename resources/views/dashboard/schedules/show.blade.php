@extends('layouts.admin')

@section('title', 'Class Schedule')
@section('page-title', 'Class Schedule')
@section('page-description', 'Details for this scheduled class')

@section('content')
    <div class="max-w-2xl space-y-6">
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>
                    {{ $schedule->subject ?: 'Class' }}
                    @if ($schedule->batch)
                        <span class="text-muted-foreground font-normal">— {{ $schedule->batch->name ?? '' }}</span>
                    @endif
                </x-ui.card-title>
                <x-ui.card-description>Weekly class slot</x-ui.card-description>
            </x-ui.card-header>

            <x-ui.card-content>
                <dl class="divide-y divide-gray-100">
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Day</dt>
                        <dd class="text-sm font-medium">{{ $schedule->day_of_week }}</dd>
                    </div>
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Time</dt>
                        <dd class="text-sm font-medium">
                            {{ $schedule->start_time }} – {{ $schedule->end_time }}
                        </dd>
                    </div>
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Batch</dt>
                        <dd class="text-sm">{{ $schedule->batch->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Teacher</dt>
                        <dd class="text-sm">{{ $schedule->teacher->user->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Room</dt>
                        <dd class="text-sm">{{ $schedule->room ?: '—' }}</dd>
                    </div>
                </dl>
            </x-ui.card-content>

            <x-ui.card-footer class="flex items-center gap-3">
                <x-ui.button as="a" href="{{ route('dashboard.schedules.edit', $schedule) }}">
                    <i class="fas fa-pen mr-2"></i> Edit Schedule
                </x-ui.button>
                <x-ui.button variant="outline" as="a" href="{{ route('dashboard.schedules.index') }}">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Schedules
                </x-ui.button>
            </x-ui.card-footer>
        </x-ui.card>
    </div>
@endsection
