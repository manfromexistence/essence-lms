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
        $settingsService = app(\App\Services\SettingsService::class);
        $sampleName = $certificate->student?->user?->name ?? 'Student';
        $sampleCourse = $certificate->course?->name ?? 'Course';
        $sampleNumber = $certificate->certificate_number;
        $sampleCode = $certificate->verification_code;
        $sampleDate = $certificate->issued_at?->format('d M Y') ?? now()->format('d M Y');
        $sampleGrade = $certificate->grade ?? '';
        $background = $template?->background_image ? asset('storage/' . $template->background_image) : null;
    @endphp
    <div class="no-print mx-auto mb-5 flex max-w-5xl justify-between gap-3">
        <a href="{{ url()->previous() }}" class="rounded-lg border bg-white px-4 py-2 font-semibold">← Back</a>
        <button onclick="window.print()" class="rounded-lg bg-green-800 px-5 py-2 font-semibold text-white">Print / Save PDF</button>
    </div>
    <main class="certificate relative mx-auto overflow-hidden bg-white shadow-2xl"
          style="width: {{ $template?->width ?? 1200 }}px; max-width: 100%; aspect-ratio: {{ ($template?->width ?? 1200) }} / {{ ($template?->height ?? 900) }};">
        @if($background)
            <img src="{{ $background }}" alt="" class="absolute inset-0 h-full w-full object-cover">
        @else
            <div class="absolute inset-3 border-2 border-black"></div>
        @endif
        <div class="relative z-10 h-full w-full">
            @include('dashboard.certificates.partials.render-elements', ['template' => $template])
        </div>
    </main>
</body>
</html>
