@extends('layouts.admin')

@section('title', 'Course Certificates')
@section('page-title', 'Course Certificates')
@section('page-description', 'Issue and manage verifiable completion certificates')

@section('content')
<div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
    <h2 class="text-lg font-bold">Issue certificate manually</h2>
    <p class="mt-1 text-sm text-gray-500">Certificates are also issued automatically after every course lesson is completed.</p>
    <form method="POST" action="{{ route('dashboard.certificates.store') }}" class="mt-4 flex flex-col gap-3 md:flex-row">@csrf
        <x-ui.select name="enrollment_id" required class="min-w-0 flex-1">
            <option value="">Select an enrolled student and course</option>
            @forelse($enrollments as $enrollment)
                <option value="{{ $enrollment->id }}">{{ $enrollment->student->user->name }} — {{ $enrollment->course->name }}</option>
            @empty
                <option value="" disabled>No eligible enrollments yet</option>
            @endforelse
        </x-ui.select>
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
                <button type="button" onclick="openEditModal({{ $certificate->id }}, '{{ addslashes($certificate->student?->user?->name ?? '') }}', '{{ addslashes($certificate->course?->name ?? '') }}', '{{ $certificate->grade ?? '' }}', '{{ $certificate->issued_at?->format('Y-m-d') ?? '' }}', '{{ $certificate->status }}')" class="font-semibold text-indigo-600">Edit</button>
                <form method="POST" action="{{ route('dashboard.certificates.email', $certificate) }}" class="inline">@csrf
                    <button class="font-semibold text-blue-600" title="Email certificate to student">Email</button>
                </form>
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

<!-- Edit Certificate Modal -->
<div id="certEditModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" onclick="closeEditModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">Edit Certificate</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p id="certEditStudent" class="text-sm text-gray-500 mb-4"></p>
            <form method="POST" id="certEditForm">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Grade</label>
                        <input type="text" name="grade" id="certEditGrade" placeholder="e.g. A+" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Issued Date</label>
                        <input type="date" name="issued_at" id="certEditIssued" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="certEditStatus" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-primary">
                            <option value="active">Active</option>
                            <option value="revoked">Revoked</option>
                        </select>
                    </div>
                    <div id="certRevokeReasonWrap" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Revocation Reason</label>
                        <input type="text" name="revocation_reason" id="certEditReason" placeholder="Reason for revoking" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-primary">
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeEditModal()" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white hover:opacity-90">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openEditModal(id, studentName, courseName, grade, issuedAt, status) {
        document.getElementById('certEditStudent').textContent = studentName + ' — ' + courseName;
        document.getElementById('certEditGrade').value = grade || '';
        document.getElementById('certEditIssued').value = issuedAt || '';
        document.getElementById('certEditStatus').value = status || 'active';
        document.getElementById('certEditReason').value = '';
        document.getElementById('certRevokeReasonWrap').classList.toggle('hidden', (status || 'active') !== 'revoked');
        document.getElementById('certEditForm').action = `/dashboard/certificates/${id}`;
        document.getElementById('certEditModal').classList.remove('hidden');
    }
    function closeEditModal() {
        document.getElementById('certEditModal').classList.add('hidden');
    }
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('certEditStatus').addEventListener('change', function() {
            document.getElementById('certRevokeReasonWrap').classList.toggle('hidden', this.value !== 'revoked');
        });
    });
</script>
@endsection
