<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $certificate->certificate_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@media print { .no-print { display:none!important } body { background:white!important } .certificate { box-shadow:none!important; margin:0!important } }</style>
</head>
<body class="bg-gray-100 p-4 md:p-10">
    @php
        $template = $certificate->template;
        $layout = $template?->layout_config ?? [];
        $bgOpacity = is_array($layout) && isset($layout['background_opacity']) ? $layout['background_opacity'] : 0.6;
        $student = $certificate->student;
        $user = $student?->user;
        $course = $certificate->course;
        $settings = app(\App\Services\SettingsService::class);

        $values = [
            'institution_name' => $settings->get('institution_name', 'Dhaka IT Institute'),
            'student_name' => $user?->name ?? 'Student',
            'course_name' => $course?->name ?? 'Course',
            'certificate_number' => $certificate->certificate_number,
            'verification_code' => $certificate->verification_code,
            'issued_at' => $certificate->issued_at?->format('d M Y') ?? now()->format('d M Y'),
            'grade' => $certificate->grade ?? '',
            'student_id' => $student?->registration_no ?? $student?->id ?? '',
            'student_phone' => $student?->phone ?? '',
            'student_email' => $user?->email ?? '',
            'course_code' => $course?->code ?? '',
            'course_duration' => $course ? trim(($course->duration ?? '') . ' ' . ($course->duration_unit ?? '')) : '',
            'institution_phone' => $settings->get('institution_phone', ''),
            'institution_address' => $settings->get('institution_address', ''),
        ];

        $background = $template?->background_image ? asset('storage/' . $template->background_image) : null;
    @endphp
    <div class="no-print mx-auto mb-5 flex max-w-5xl justify-between gap-3">
        <a href="{{ url()->previous() }}" class="rounded-lg border bg-white px-4 py-2 font-semibold">← Back</a>
        <button onclick="window.print()" class="rounded-lg bg-green-800 px-5 py-2 font-semibold text-white">Print / Save PDF</button>
    </div>
    <main class="certificate relative mx-auto overflow-hidden bg-white shadow-2xl"
          style="width: {{ $template?->width ?? 1200 }}px; max-width: 100%; aspect-ratio: {{ ($template?->width ?? 1200) }} / {{ ($template?->height ?? 900) }};">
        @if($background)
            <div class="absolute inset-0" style="opacity: {{ $bgOpacity }};">
                <img src="{{ $background }}" alt="" class="h-full w-full object-cover">
            </div>
        @else
            <div class="absolute inset-0" style="opacity: {{ $bgOpacity }}; background: linear-gradient(135deg, #d1fae5 0%, #ffffff 50%, #a7f3d0 100%);"></div>
        @endif
        <div class="relative z-10 h-full w-full">
            @include('dashboard.certificates.partials.render-elements', ['template' => $template, 'values' => $values])
        </div>
    </main>
</body>
</html>
