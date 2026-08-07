@extends('layouts.admin')

@section('title', 'Email Dashboard')
@section('page-title', 'Email Dashboard')
@section('page-description', 'Send emails to students via Brevo')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
            <p class="text-sm font-medium text-gray-600">Total Sent</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_sent'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500">
            <p class="text-sm font-medium text-gray-600">Failed</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_failed'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-amber-500">
            <p class="text-sm font-medium text-gray-600">Pending</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_pending'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-sm font-medium text-gray-600">Today's Sent</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($stats['today_sent'] ?? 0) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Send Single Email -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Send Single Email</x-ui.card-title>
                <x-ui.card-description>Send an email to a single recipient</x-ui.card-description>
            </x-ui.card-header>
            <x-ui.card-content>
                <form action="{{ route('dashboard.email.send') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <x-ui.label for="email">Email Address</x-ui.label>
                        <x-ui.input type="email" name="email" id="email" required placeholder="student@example.com" />
                        @error('email')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <x-ui.label for="single_subject">Subject</x-ui.label>
                        <x-ui.input type="text" name="subject" id="single_subject" required placeholder="Email subject" />
                        @error('subject')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <x-ui.label for="single_message">Message</x-ui.label>
                        <x-ui.textarea name="message" id="single_message" rows="5" required placeholder="Write your message here..."></x-ui.textarea>
                        @error('message')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <x-ui.button type="submit" class="w-full">
                        <i class="fa-solid fa-envelope mr-2"></i> Send Email
                    </x-ui.button>
                </form>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Send Bulk Email -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Send Bulk Email</x-ui.card-title>
                <x-ui.card-description>Email multiple students at once</x-ui.card-description>
            </x-ui.card-header>
            <x-ui.card-content>
                <form id="bulkEmailForm" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-ui.label for="recipient_type">Recipient Type</x-ui.label>
                            <x-ui.select name="recipient_type" id="recipient_type" onchange="toggleRecipientFields()">
                                <option value="all">All Students</option>
                                <option value="batch">By Batch</option>
                                <option value="course">By Course</option>
                                <option value="students_with_dues">Students with Dues</option>
                                <option value="custom">Custom Emails</option>
                            </x-ui.select>
                        </div>
                        <div id="batch_field" class="hidden">
                            <x-ui.label for="batch_id">Select Batch</x-ui.label>
                            <x-ui.select name="batch_id" id="batch_id">
                                <option value="">Select Batch</option>
                                @foreach($batches as $batch)
                                    <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </div>
                        <div id="course_field" class="hidden">
                            <x-ui.label for="course_id">Select Course</x-ui.label>
                            <x-ui.select name="course_id" id="course_id">
                                <option value="">Select Course</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </div>
                    </div>

                    <div id="custom_field" class="hidden">
                        <x-ui.label for="custom_emails">Email Addresses (comma separated)</x-ui.label>
                        <x-ui.textarea name="custom_emails" id="custom_emails" rows="2" placeholder="a@example.com, b@example.com"></x-ui.textarea>
                    </div>

                    <div>
                        <x-ui.label for="bulk_subject">Subject</x-ui.label>
                        <x-ui.input type="text" name="subject" id="bulk_subject" required placeholder="Email subject" />
                    </div>
                    <div>
                        <x-ui.label for="bulk_message">Message</x-ui.label>
                        <x-ui.textarea name="message" id="bulk_message" rows="4" required placeholder="Write your message here..."></x-ui.textarea>
                    </div>

                    <x-ui.button type="submit" id="bulkSendBtn" class="w-full">
                        <i class="fa-solid fa-envelope-open-text mr-2"></i> Send Bulk Email
                    </x-ui.button>
                </form>

                <div id="bulkProgress" class="hidden mt-4">
                    <div class="bg-blue-50 rounded-lg p-4 flex items-center">
                        <svg class="animate-spin h-5 w-5 text-blue-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-blue-700 font-medium">Sending emails...</span>
                    </div>
                </div>
                <div id="bulkResult" class="hidden mt-4"></div>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    <!-- Recent Email Logs -->
    <x-ui.card>
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Recent Email Logs</h3>
                <p class="text-sm text-gray-500">Last 20 emails</p>
            </div>
        </div>
        <div class="p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left">Recipient</th>
                            <th class="px-5 py-3 text-left">Subject</th>
                            <th class="px-5 py-3 text-left">Status</th>
                            <th class="px-5 py-3 text-left">Sent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentLogs as $log)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap text-gray-900 font-medium">{{ $log->to }}</td>
                                <td class="px-5 py-4 text-gray-600 max-w-xs truncate" title="{{ $log->subject }}">{{ Str::limit($log->subject, 60) }}</td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'sent' => 'bg-green-100 text-green-800',
                                            'failed' => 'bg-red-100 text-red-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$log->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-gray-500">
                                    No emails sent yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-ui.card>
</div>

@push('scripts')
<script>
function toggleRecipientFields() {
    const type = document.getElementById('recipient_type').value;
    document.getElementById('batch_field').classList.toggle('hidden', type !== 'batch');
    document.getElementById('course_field').classList.toggle('hidden', type !== 'course');
    document.getElementById('custom_field').classList.toggle('hidden', type !== 'custom');
}

document.getElementById('bulkEmailForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('bulkSendBtn');
    const progressDiv = document.getElementById('bulkProgress');
    const resultDiv = document.getElementById('bulkResult');

    submitBtn.disabled = true;
    progressDiv.classList.remove('hidden');
    resultDiv.classList.add('hidden');

    try {
        const response = await fetch('{{ route("dashboard.email.send-bulk") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                recipient_type: formData.get('recipient_type'),
                batch_id: formData.get('batch_id'),
                course_id: formData.get('course_id'),
                custom_emails: formData.get('custom_emails'),
                subject: formData.get('subject'),
                message: formData.get('message'),
            }),
        });

        const data = await response.json();
        progressDiv.classList.add('hidden');
        submitBtn.disabled = false;

        if (data.success) {
            resultDiv.innerHTML = `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-green-700 font-medium">${data.message}</span>
                    </div>
                    <div class="mt-2 text-sm text-green-600">
                        Total: ${data.data.total} | Successful: ${data.data.successful} | Failed: ${data.data.failed}
                    </div>
                </div>`;
        } else {
            resultDiv.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-red-700 font-medium">${data.message}</span>
                    </div>
                </div>`;
        }
        resultDiv.classList.remove('hidden');
        setTimeout(() => location.reload(), 3000);
    } catch (error) {
        progressDiv.classList.add('hidden');
        submitBtn.disabled = false;
        resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700 font-medium">An error occurred. Please try again.</div>`;
        resultDiv.classList.remove('hidden');
    }
});
</script>
@endpush
@endsection
