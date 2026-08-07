@extends('layouts.admin')

@section('title', 'SMS Dashboard')
@section('page-title', 'SMS Dashboard')
@section('page-description', 'Send SMS and manage communications')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards - Requirement 10.3: Display SMS statistics -->
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
        <!-- Send Single SMS Form -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Send Single SMS</x-ui.card-title>
                <x-ui.card-description>Send SMS to a single recipient</x-ui.card-description>
            </x-ui.card-header>
            <x-ui.card-content>
                <form action="{{ route('dashboard.communication.send') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="type" value="general">
                    
                    <div>
                        <x-ui.label for="phone">Phone Number</x-ui.label>
                        <x-ui.input type="text" name="phone" id="phone" required placeholder="01XXXXXXXXX" />
                        @error('phone')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <x-ui.label for="single_template">Use Template (Optional)</x-ui.label>
                        <x-ui.select name="template_select" id="single_template" onchange="applySingleTemplate()">
                            <option value="">Select Template</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->content }}">{{ $template->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    
                    <div>
                        <x-ui.label for="single_message">Message</x-ui.label>
                        <x-ui.textarea name="message" id="single_message" rows="4" required maxlength="500" placeholder="Enter your message here..."></x-ui.textarea>
                        <p class="text-sm text-gray-500 mt-1"><span id="singleCharCount">0</span>/500 characters</p>
                        @error('message')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <x-ui.button type="submit" class="w-full">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Send SMS
                    </x-ui.button>
                </form>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Send Bulk SMS Form - Requirement 11.1: Support recipient selection by batch, course, or custom list -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Send Bulk SMS</x-ui.card-title>
                <x-ui.card-description>Send SMS to multiple recipients</x-ui.card-description>
            </x-ui.card-header>
            <x-ui.card-content>
                <form id="bulkSmsForm" class="space-y-4">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-ui.label for="recipient_type">Recipient Type</x-ui.label>
                            <x-ui.select name="recipient_type" id="recipient_type" onchange="toggleRecipientFields()" required>
                                <option value="all">All Students</option>
                                <option value="batch">By Batch</option>
                                <option value="course">By Course</option>
                                <option value="students_with_dues">Students with Dues</option>
                                <option value="custom">Custom Numbers</option>
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
                        <x-ui.label for="custom_numbers">Phone Numbers (comma separated)</x-ui.label>
                        <x-ui.textarea name="custom_numbers" id="custom_numbers" rows="2" placeholder="01712345678, 01812345678"></x-ui.textarea>
                    </div>

                    <!-- Requirement 11.5: Support sending to both students and parents -->
                    <div class="flex items-center space-x-2">
                        <x-ui.checkbox name="include_parents" id="include_parents" value="1" />
                        <x-ui.label for="include_parents" class="cursor-pointer">Also send to parents/guardians</x-ui.label>
                    </div>
                    
                    <div>
                        <x-ui.label for="bulk_template">Use Template (Optional)</x-ui.label>
                        <x-ui.select name="template_select" id="bulk_template" onchange="applyBulkTemplate()">
                            <option value="">Select Template</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->content }}">{{ $template->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    
                    <div>
                        <x-ui.label for="bulk_message">Message</x-ui.label>
                        <x-ui.textarea name="message" id="bulk_message" rows="4" required maxlength="500" placeholder="Enter your message here..."></x-ui.textarea>
                        <p class="text-sm text-gray-500 mt-1"><span id="bulkCharCount">0</span>/500 characters</p>
                    </div>

                    <!-- Available Placeholders -->
                    @if(!empty($placeholders))
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-600 mb-2">Available Placeholders:</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($placeholders as $placeholder)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 cursor-pointer hover:bg-blue-200" onclick="insertPlaceholder('{{ $placeholder }}')">
                                    {{ $placeholder }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    <x-ui.button type="submit" id="bulkSendBtn" class="w-full">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                        </svg>
                        Send Bulk SMS
                    </x-ui.button>
                </form>

                <!-- Progress indicator for bulk SMS - Requirement 11.3 -->
                <div id="bulkProgress" class="hidden mt-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="animate-spin h-5 w-5 text-blue-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-blue-700 font-medium">Sending SMS...</span>
                        </div>
                    </div>
                </div>

                <!-- Result display -->
                <div id="bulkResult" class="hidden mt-4"></div>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    <!-- Recent SMS Logs - Requirement 10.3: Display all sent messages with filtering options -->
    <x-ui.card>
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Recent SMS Logs</h3>
                <p class="text-sm text-gray-500">Last 20 messages</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('dashboard.communication.logs') }}" class="text-sm text-blue-600 hover:underline">View All Logs</a>
                <a href="{{ route('dashboard.communication.templates') }}" class="text-sm text-purple-600 hover:underline">Manage Templates</a>
            </div>
        </div>
        
        <div class="p-0">
            @php
                $headers = [
                    ['key' => 'phone', 'label' => 'Phone'],
                    ['key' => 'message', 'label' => 'Message'],
                    ['key' => 'type', 'label' => 'Type'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'date', 'label' => 'Date'],
                    ['key' => 'actions', 'label' => 'Actions'],
                ];
            @endphp
    
            <x-ui.data-table
                id="sms-logs-table"
                :headers="$headers"
                :rows="$recentLogs"
                :paginated="false"
            >
                @forelse($recentLogs as $log)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $log->phone }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="{{ $log->message }}">
                            {{ Str::limit($log->message, 50) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border border-gray-200 bg-white text-gray-800">
                                {{ ucfirst($log->type ?? 'general') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'sent' => 'bg-green-100 text-green-800',
                                    'delivered' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$log->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($log->status === 'failed')
                                <form action="{{ route('dashboard.communication.retry', $log) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 font-medium">
                                        Retry
                                    </button>
                                </form>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                                <h3 class="text-sm font-medium text-gray-900">No SMS logs found</h3>
                                <p class="mt-1 text-sm text-gray-500">SMS logs will appear here once messages are sent.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-ui.data-table>
        </div>
    </x-ui.card>
</div>

@push('scripts')
<script>
// Toggle recipient fields based on selection
function toggleRecipientFields() {
    const type = document.getElementById('recipient_type').value;
    document.getElementById('batch_field').classList.toggle('hidden', type !== 'batch');
    document.getElementById('course_field').classList.toggle('hidden', type !== 'course');
    document.getElementById('custom_field').classList.toggle('hidden', type !== 'custom');
}

// Apply template to single SMS form
function applySingleTemplate() {
    const template = document.getElementById('single_template').value;
    if (template) {
        document.getElementById('single_message').value = template;
        updateSingleCharCount();
    }
}

// Apply template to bulk SMS form
function applyBulkTemplate() {
    const template = document.getElementById('bulk_template').value;
    if (template) {
        document.getElementById('bulk_message').value = template;
        updateBulkCharCount();
    }
}

// Insert placeholder into bulk message
function insertPlaceholder(placeholder) {
    const textarea = document.getElementById('bulk_message');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + placeholder + text.substring(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
    updateBulkCharCount();
}

// Character count updates
function updateSingleCharCount() {
    const message = document.getElementById('single_message').value;
    document.getElementById('singleCharCount').textContent = message.length;
}

function updateBulkCharCount() {
    const message = document.getElementById('bulk_message').value;
    document.getElementById('bulkCharCount').textContent = message.length;
}

document.getElementById('single_message').addEventListener('input', updateSingleCharCount);
document.getElementById('bulk_message').addEventListener('input', updateBulkCharCount);

// Bulk SMS form submission with AJAX
document.getElementById('bulkSmsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('bulkSendBtn');
    const progressDiv = document.getElementById('bulkProgress');
    const resultDiv = document.getElementById('bulkResult');
    
    // Show progress, hide result
    submitBtn.disabled = true;
    progressDiv.classList.remove('hidden');
    resultDiv.classList.add('hidden');
    
    try {
        const response = await fetch('{{ route("dashboard.communication.send-bulk") }}', {
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
                custom_numbers: formData.get('custom_numbers'),
                include_parents: formData.get('include_parents') === '1',
                message: formData.get('message'),
            }),
        });
        
        const data = await response.json();
        
        // Hide progress
        progressDiv.classList.add('hidden');
        submitBtn.disabled = false;
        
        // Show result - Requirement 11.4: Show summary of successful and failed messages
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
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-red-700 font-medium">${data.message}</span>
                    </div>
                </div>
            `;
        }
        resultDiv.classList.remove('hidden');
        
        // Reload page after 3 seconds to refresh stats
        setTimeout(() => location.reload(), 3000);
        
    } catch (error) {
        progressDiv.classList.add('hidden');
        submitBtn.disabled = false;
        resultDiv.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span class="text-red-700 font-medium">An error occurred. Please try again.</span>
                </div>
            </div>
        `;
        resultDiv.classList.remove('hidden');
    }
});
</script>
@endpush
@endsection
