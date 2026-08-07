@extends('layouts.admin')

@section('title', 'Course Certificates')
@section('page-title', 'Course Certificates')
@section('page-description', 'Issue and manage verifiable completion certificates')

@section('content')
<div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
    <h2 class="text-lg font-bold">Issue certificate manually</h2>
    <p class="mt-1 text-sm text-gray-500">Certificates are also issued automatically after every course lesson is completed.</p>
    <form method="POST" action="{{ route('dashboard.certificates.store') }}" class="mt-4 flex flex-col gap-3 md:flex-row">@csrf
        <select name="enrollment_id" required class="min-w-0 flex-1 rounded-xl border-gray-300 px-4 py-3 focus:border-green-700 focus:ring-green-700">
            <option value="">Select an enrolled student and course</option>
            @forelse($enrollments as $enrollment)
                <option value="{{ $enrollment->id }}">{{ $enrollment->student->user->name }} — {{ $enrollment->course->name }}</option>
            @empty
                <option value="" disabled>No eligible enrollments yet</option>
            @endforelse
        </select>
        <button class="rounded-xl bg-primary px-5 py-3 font-bold text-white">Issue certificate</button>
    </form>
</div>

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50"><tr><th class="px-5 py-3 text-left">Student</th><th class="px-5 py-3 text-left">Course</th><th class="px-5 py-3 text-left">Certificate</th><th class="px-5 py-3 text-left">Status</th><th class="px-5 py-3 text-left">Issued</th><th class="px-5 py-3"></th></tr></thead><tbody class="divide-y divide-gray-100">
        @forelse($certificates as $certificate)
        <tr>
            <td class="px-5 py-4 font-semibold">{{ $certificate->student?->user?->name ?? 'Unknown student' }}</td>
            <td class="px-5 py-4">{{ $certificate->course?->name ?? 'Unknown course' }}</td>
            <td class="px-5 py-4 font-mono text-xs">{{ $certificate->certificate_number }}</td>
            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $certificate->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ ucfirst($certificate->status) }}</span></td>
            <td class="px-5 py-4">{{ $certificate->issued_at?->format('d M Y') ?? '—' }}</td>
            <td class="px-5 py-4 text-right"><div class="flex justify-end gap-3">
                @if($certificate->verification_code)
                    <a target="_blank" href="{{ route('certificates.verify', $certificate->verification_code) }}" class="font-semibold text-primary">Verify</a>
                @endif
                @if($certificate->status === 'active')
                    <form method="POST" action="{{ route('dashboard.certificates.revoke', $certificate) }}" onsubmit="const value = prompt('Reason for revoking this certificate:'); if (!value) return false; this.elements.reason.value = value;">@csrf<input type="hidden" name="reason"><button class="font-semibold text-red-600">Revoke</button></form>
                @endif
            </div></td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-5 py-12 text-center text-gray-500">No certificates issued yet.</td></tr>
        @endforelse
    </tbody></table></div>
    @if($certificates->hasPages())<div class="border-t p-4">{{ $certificates->links() }}</div>@endif
</div>
@endsection
