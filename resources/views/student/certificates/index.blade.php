@extends('layouts.admin')

@section('title', 'My Certificates')
@section('page-title', 'My Certificates')
@section('page-description', 'Certificates earned by completing your courses')

@section('content')
<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
    @forelse($certificates as $certificate)
        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-100 text-green-800"><i class="fa-solid fa-award text-xl"></i></div>
            <h2 class="mt-5 text-lg font-bold text-gray-900">{{ $certificate->course->name }}</h2>
            <p class="mt-1 text-sm text-gray-500">Issued {{ $certificate->issued_at->format('d M Y') }}</p>
            <p class="mt-3 font-mono text-xs text-gray-500">{{ $certificate->certificate_number }}</p>
            <a href="{{ route('student.certificates.show', $certificate) }}" class="mt-5 inline-flex rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">View certificate</a>
        </article>
    @empty
        <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
            <i class="fa-solid fa-award text-4xl text-gray-300"></i>
            <h2 class="mt-4 text-xl font-bold">No certificates yet</h2>
            <p class="mt-2 text-gray-500">Complete every lesson in an enrolled course to earn its certificate.</p>
        </div>
    @endforelse
</div>
@endsection
