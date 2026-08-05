@extends('layouts.frontend')

@section('title', 'Verify Certificate')

@section('content')
<section class="min-h-[65vh] bg-gray-50 py-16">
    <div class="mx-auto max-w-3xl px-4">
        <div class="rounded-3xl bg-white p-7 shadow-xl md:p-10">
            <div class="text-center"><i class="fa-solid fa-shield-check text-5xl text-green-700"></i><h1 class="mt-4 text-3xl font-black">Verify a certificate</h1><p class="mt-2 text-gray-500">Enter the verification code printed on the certificate.</p></div>
            <form class="mt-7 flex gap-3" action="{{ route('certificates.verify') }}" method="GET">
                <input name="code" value="{{ $code }}" required placeholder="Verification code" class="min-w-0 flex-1 rounded-xl border-gray-300 px-4 py-3 uppercase focus:border-green-700 focus:ring-green-700">
                <button class="rounded-xl bg-green-800 px-5 py-3 font-bold text-white">Verify</button>
            </form>
            @if($code !== '')
                @if($certificate)
                    <div class="mt-8 rounded-2xl border {{ $certificate->status === 'active' ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} p-6">
                        <p class="font-bold {{ $certificate->status === 'active' ? 'text-green-800' : 'text-red-800' }}">{{ $certificate->status === 'active' ? 'Valid certificate' : 'Certificate ' . $certificate->status }}</p>
                        <dl class="mt-4 grid gap-4 sm:grid-cols-2"><div><dt class="text-xs uppercase text-gray-500">Student</dt><dd class="font-semibold">{{ $certificate->student->user->name }}</dd></div><div><dt class="text-xs uppercase text-gray-500">Course</dt><dd class="font-semibold">{{ $certificate->course->name }}</dd></div><div><dt class="text-xs uppercase text-gray-500">Certificate</dt><dd class="font-mono text-sm">{{ $certificate->certificate_number }}</dd></div><div><dt class="text-xs uppercase text-gray-500">Issued</dt><dd>{{ $certificate->issued_at->format('d M Y') }}</dd></div></dl>
                    </div>
                @else
                    <div class="mt-8 rounded-2xl border border-red-200 bg-red-50 p-5 font-semibold text-red-800">No certificate was found for this verification code.</div>
                @endif
            @endif
        </div>
    </div>
</section>
@endsection
