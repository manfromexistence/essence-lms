@extends('layouts.frontend')

@section('title', 'Verified Student — ' . ($profile['identity']['name'] ?? 'Certificate'))

@section('content')
<div class="min-h-screen bg-slate-100 py-8 md:py-12">
    <div class="mx-auto max-w-4xl px-4">

        {{-- Provenance: makes clear to the visitor what this page is and why
             they can trust it, before any claim is read. --}}
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-green-200 bg-green-50 px-5 py-4">
            <i class="fa-solid fa-shield-halved text-2xl text-green-700"></i>
            <div>
                <p class="font-bold text-green-900">Verified student record</p>
                <p class="text-sm text-green-800">
                    This page was opened from the QR code printed on a
                    {{ $institution }} course completion certificate. It shows the
                    academic record of the student named on that certificate.
                </p>
            </div>
        </div>

        @php
            $identity = $profile['identity'];
            $metrics = $profile['metrics'];
            $admission = $profile['admission'];
        @endphp

        {{-- Identity --}}
        <div class="overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex flex-col items-center gap-5 border-b border-slate-200 p-7 sm:flex-row sm:items-start">
                <img src="{{ $identity['photo'] }}" alt=""
                     class="h-24 w-24 shrink-0 rounded-2xl object-cover ring-4 ring-green-100">

                <div class="min-w-0 flex-1 text-center sm:text-left">
                    <h1 class="text-2xl font-black text-slate-900">{{ $identity['name'] }}</h1>
                    <p class="mt-1 font-mono text-sm text-slate-600">{{ $identity['registration_no'] }}</p>

                    <div class="mt-3 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                        @php
                            $statusBadge = [
                                'approved' => ['bg-green-100 text-green-800', 'Enrolled student'],
                                'rejected' => ['bg-red-100 text-red-800', 'Not enrolled'],
                                'pending' => ['bg-amber-100 text-amber-800', 'Admission pending'],
                            ][$admission['status']] ?? ['bg-slate-100 text-slate-800', ucfirst($admission['status'])];
                        @endphp
                        <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold {{ $statusBadge[0] }}">
                            <i class="fa-solid fa-circle text-[6px]"></i>{{ $statusBadge[1] }}
                        </span>
                        @if($admission['mode'])
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium capitalize text-slate-700">
                                {{ $admission['mode'] }} admission
                            </span>
                        @endif
                        @if($admission['since'])
                            <span class="text-xs text-slate-500">Enrolled since {{ $admission['since'] }}</span>
                        @endif
                    </div>

                    @if($identity['course_name'])
                        <p class="mt-3 text-sm text-slate-600">
                            <span class="font-medium text-slate-800">{{ $identity['course_name'] }}</span>
                            @if($identity['batch'])
                                <span class="text-slate-500"> &middot; {{ $identity['batch'] }}</span>
                            @endif
                            @if($identity['roll'])
                                <span class="text-slate-500"> &middot; Roll {{ $identity['roll'] }}</span>
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            {{-- Headline metrics --}}
            <div class="grid grid-cols-2 divide-x divide-slate-200 border-b border-slate-200 sm:grid-cols-4">
                @php
                    $stats = [
                        ['label' => 'Courses completed', 'value' => $metrics['courses_completed'], 'icon' => 'fa-graduation-cap'],
                        ['label' => 'Exams taken', 'value' => $metrics['exams_taken'], 'icon' => 'fa-file-pen'],
                        ['label' => 'Average score', 'value' => $metrics['average_score'] === null ? '—' : $metrics['average_score'] . '%', 'icon' => 'fa-chart-simple'],
                        ['label' => 'Attendance', 'value' => $metrics['classes_total'] > 0 ? $metrics['attendance_percentage'] . '%' : '—', 'icon' => 'fa-user-check'],
                    ];
                @endphp
                @foreach($stats as $stat)
                    <div class="px-4 py-5 text-center">
                        <i class="fa-solid {{ $stat['icon'] }} text-lg text-green-700"></i>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ $stat['value'] }}</p>
                        <p class="mt-0.5 text-[11px] uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="space-y-6 p-7">

                {{-- Certificates --}}
                <section>
                    <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-500">
                        <i class="fa-solid fa-certificate text-green-700"></i> Certificates issued
                    </h2>

                    @if($profile['certificates']->isEmpty())
                        <p class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            No active certificates have been issued to this student.
                        </p>
                    @else
                        <ul class="mt-3 space-y-2">
                            @foreach($profile['certificates'] as $certificate)
                                <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">{{ $certificate->course?->name ?? 'Course' }}</p>
                                        <p class="font-mono text-xs text-slate-500">{{ $certificate->certificate_number }}</p>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-slate-600">
                                        @if($certificate->grade)
                                            <span class="rounded-full bg-green-100 px-2.5 py-1 font-bold text-green-800">Grade {{ $certificate->grade }}</span>
                                        @endif
                                        <span>{{ $certificate->issued_at?->format('d M Y') }}</span>
                                        <a href="{{ route('certificates.verify', $certificate->verification_code) }}"
                                           class="font-semibold text-green-700 hover:underline">Check</a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                {{-- Courses --}}
                <section>
                    <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-500">
                        <i class="fa-solid fa-book-open text-green-700"></i> Coursework
                    </h2>

                    @if($profile['courses']->isEmpty())
                        <p class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">Not enrolled in any course.</p>
                    @else
                        <div class="mt-3 space-y-3">
                            @foreach($profile['courses'] as $course)
                                <div class="rounded-xl border border-slate-200 p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="font-semibold text-slate-900">
                                            {{ $course['course'] }}
                                            @if($course['code'])
                                                <span class="ml-1 font-mono text-xs text-slate-500">{{ $course['code'] }}</span>
                                            @endif
                                        </p>
                                        <div class="flex items-center gap-2 text-xs">
                                            @if($course['certified'])
                                                <span class="rounded-full bg-green-100 px-2.5 py-1 font-semibold text-green-800">Completed</span>
                                            @endif
                                            @if($course['enrolled_at'])
                                                <span class="text-slate-500">Since {{ $course['enrolled_at'] }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($course['total_videos'] > 0)
                                        <div class="mt-3 flex items-center gap-3">
                                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200">
                                                <div class="h-full rounded-full bg-green-600"
                                                     style="width: {{ $course['progress_percentage'] }}%"></div>
                                            </div>
                                            <span class="shrink-0 text-xs font-medium text-slate-600">
                                                {{ $course['completed_videos'] }}/{{ $course['total_videos'] }} lessons
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                {{-- Submitted work --}}
                <section>
                    <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-500">
                        <i class="fa-solid fa-file-arrow-up text-green-700"></i> Submitted assignments
                    </h2>

                    @if($profile['assignments']->isEmpty())
                        <p class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            No written assignments have been submitted.
                        </p>
                    @else
                        <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-4 py-2.5 font-semibold">Assignment</th>
                                        <th class="px-4 py-2.5 font-semibold">Submitted</th>
                                        <th class="px-4 py-2.5 font-semibold">Files</th>
                                        <th class="px-4 py-2.5 font-semibold">Result</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($profile['assignments'] as $assignment)
                                        <tr>
                                            <td class="px-4 py-2.5 font-medium text-slate-900">{{ $assignment['exam'] }}</td>
                                            <td class="px-4 py-2.5 text-slate-600">{{ $assignment['submitted_at'] ?? '—' }}</td>
                                            <td class="px-4 py-2.5 text-slate-600">{{ $assignment['files'] }}</td>
                                            <td class="px-4 py-2.5">
                                                @if($assignment['evaluated'] && $assignment['marks'] !== null)
                                                    <span class="font-semibold text-slate-900">
                                                        {{ rtrim(rtrim(number_format((float) $assignment['marks'], 2), '0'), '.') }}@if($assignment['out_of'])/{{ $assignment['out_of'] }}@endif
                                                    </span>
                                                @else
                                                    {{-- No mark yet means unevaluated, which is not the same
                                                         as a zero and must not be shown as one. --}}
                                                    <span class="text-slate-500">Awaiting evaluation</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                {{-- Results --}}
                <section>
                    <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-500">
                        <i class="fa-solid fa-chart-line text-green-700"></i> Exam performance
                    </h2>

                    @if($profile['results']->isEmpty())
                        <p class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">No exam results recorded.</p>
                    @else
                        <div class="mt-3 space-y-2">
                            @foreach($profile['results'] as $result)
                                @php $percentage = (float) $result->percentage; @endphp
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">{{ $result->exam?->title ?? 'Exam' }}</p>
                                        <p class="text-xs text-slate-500">{{ $result->created_at?->format('d M Y') }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-slate-600">
                                            {{ $result->obtained_marks ?? $result->marks }}@if($result->total_marks)/{{ $result->total_marks }}@endif
                                        </span>
                                        <span class="w-16 rounded-full px-2 py-1 text-center text-xs font-bold
                                            {{ $percentage >= 80 ? 'bg-green-100 text-green-800' : ($percentage >= 60 ? 'bg-blue-100 text-blue-800' : ($percentage >= 40 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800')) }}">
                                            {{ $percentage }}%
                                        </span>
                                        @if($result->grade)
                                            <span class="text-xs font-bold text-slate-700">{{ $result->grade }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-slate-500">
            This record was generated by {{ $institution }} and reflects the student's
            record at the time of viewing.
            <a href="{{ route('certificates.verify') }}" class="font-semibold text-green-700 hover:underline">
                Verify a certificate by code
            </a>
        </p>
    </div>
</div>
@endsection