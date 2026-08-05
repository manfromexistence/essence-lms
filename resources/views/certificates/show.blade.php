<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $certificate->certificate_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@media print { .no-print { display:none!important } body { background:white!important } .certificate { box-shadow:none!important; margin:0!important } }</style>
</head>
<body class="bg-gray-100 p-4 md:p-10">
    <div class="no-print mx-auto mb-5 flex max-w-5xl justify-between gap-3">
        <a href="{{ url()->previous() }}" class="rounded-lg border bg-white px-4 py-2 font-semibold">← Back</a>
        <button onclick="window.print()" class="rounded-lg bg-green-800 px-5 py-2 font-semibold text-white">Print / Save PDF</button>
    </div>
    <main class="certificate relative mx-auto aspect-[1.414/1] max-w-5xl overflow-hidden border-[14px] border-green-800 bg-white p-8 text-center shadow-2xl md:p-14">
        <div class="absolute inset-3 border-2 border-black"></div>
        <div class="relative z-10 flex h-full flex-col items-center justify-center">
            <img src="{{ app(\App\Services\SettingsService::class)->getLogo() }}" alt="Dhaka IT Institute" class="h-14 w-auto md:h-20">
            <p class="mt-6 uppercase tracking-[0.35em] text-green-800">Certificate of Completion</p>
            <h1 class="mt-5 text-3xl font-black text-gray-900 md:text-5xl">This certifies that</h1>
            <p class="mt-5 border-b-2 border-green-800 px-10 pb-2 font-serif text-3xl italic md:text-5xl">{{ $certificate->student->user->name }}</p>
            <p class="mt-5 max-w-2xl text-base leading-7 text-gray-600 md:text-lg">has successfully completed all learning requirements for</p>
            <h2 class="mt-3 text-2xl font-black text-green-800 md:text-4xl">{{ $certificate->course->name }}</h2>
            <div class="mt-8 grid w-full max-w-3xl grid-cols-3 gap-4 text-xs md:text-sm">
                <div><p class="font-bold">Issued</p><p>{{ $certificate->issued_at->format('d M Y') }}</p></div>
                <div><p class="font-bold">Certificate No.</p><p>{{ $certificate->certificate_number }}</p></div>
                <div><p class="font-bold">Verification</p><p>{{ $certificate->verification_code }}</p></div>
            </div>
            @if($certificate->status !== 'active')<p class="mt-5 rounded bg-red-100 px-4 py-2 font-bold text-red-700">{{ strtoupper($certificate->status) }}</p>@endif
        </div>
    </main>
</body>
</html>
