@extends('layouts.admin')

@section('title', 'CQ Submission')
@section('page-title', 'Creative Question Submission')
@section('page-description', 'Your uploaded answer sheets and evaluation status')

@section('content')
    <div class="max-w-3xl space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold tracking-tight">{{ $submission->exam->title ?? 'Exam' }}</h2>
            <x-ui.button variant="outline" as="a" href="{{ route('student.exams') }}">
                <i class="fas fa-arrow-left mr-2"></i> Back to Exams
            </x-ui.button>
        </div>

        {{-- Status --}}
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Submission Status</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <dl class="divide-y divide-gray-100">
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Status</dt>
                        <dd>
                            @if ($submission->evaluated_at)
                                <x-ui.badge variant="outline" class="text-emerald-700 border-emerald-200 bg-emerald-50">
                                    <i class="fas fa-check mr-1"></i> Evaluated
                                </x-ui.badge>
                            @else
                                <x-ui.badge variant="outline" class="text-amber-700 border-amber-200 bg-amber-50">
                                    <i class="fas fa-clock mr-1"></i> Awaiting evaluation
                                </x-ui.badge>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-muted-foreground">Submitted</dt>
                        <dd class="text-sm">
                            {{ optional($submission->submitted_at)->format('M d, Y g:i A') ?? '—' }}
                        </dd>
                    </div>
                    @if ($submission->evaluated_at)
                        <div class="flex justify-between py-3">
                            <dt class="text-sm text-muted-foreground">Evaluated</dt>
                            <dd class="text-sm">{{ $submission->evaluated_at->format('M d, Y g:i A') }}</dd>
                        </div>
                        <div class="flex justify-between py-3">
                            <dt class="text-sm text-muted-foreground">Marks</dt>
                            <dd class="text-sm font-semibold">
                                {{ rtrim(rtrim(number_format((float) $submission->marks, 2), '0'), '.') }}
                                @if (!empty($submission->exam->total_marks))
                                    <span class="text-muted-foreground font-normal">
                                        / {{ $submission->exam->total_marks }}
                                    </span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>

                @if ($submission->evaluated_at && ($submission->feedback || $submission->teacher_notes))
                    <x-ui.alert class="mt-4 bg-blue-50 text-blue-900 border-blue-200">
                        <i class="fas fa-comment-dots mr-2"></i>
                        <x-ui.alert-title>Teacher Feedback</x-ui.alert-title>
                        <x-ui.alert-description>
                            {{ $submission->feedback ?: $submission->teacher_notes }}
                        </x-ui.alert-description>
                    </x-ui.alert>
                @endif
            </x-ui.card-content>
        </x-ui.card>

        {{-- Uploaded answer sheets --}}
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>
                    Answer Sheets
                    <x-ui.badge variant="secondary" class="ml-2">{{ count($submission->files ?? []) }}</x-ui.badge>
                </x-ui.card-title>
                <x-ui.card-description>The pages you uploaded for this examination.</x-ui.card-description>
            </x-ui.card-header>
            <x-ui.card-content class="p-0">
                <div class="divide-y divide-border">
                    @forelse (($submission->files ?? []) as $index => $file)
                        <div class="flex items-center justify-between p-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-9 w-9 rounded-lg bg-muted flex items-center justify-center text-muted-foreground">
                                    <i class="fas fa-file-image"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Page {{ $index + 1 }}</p>
                                    <p class="text-xs text-muted-foreground font-mono">
                                        {{ basename($file['path'] ?? '') }}
                                    </p>
                                </div>
                            </div>
                            @if (!empty($file['path']))
                                <x-ui.button variant="outline" size="sm" as="a"
                                    href="{{ media_url($file['path']) }}" target="_blank" rel="noopener">
                                    <i class="fas fa-up-right-from-square mr-2"></i> View
                                </x-ui.button>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-muted-foreground">
                            No answer sheets were uploaded for this submission.
                        </div>
                    @endforelse
                </div>
            </x-ui.card-content>
        </x-ui.card>
    </div>
@endsection
