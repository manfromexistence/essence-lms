@extends('layouts.admin')

@section('title', 'SMS Logs')
@section('page-title', 'SMS Logs')
@section('page-description', 'View all SMS message logs with filtering and retry options')

@section('content')
<div class="space-y-6">
    <!-- Filters Card - Requirement 10.3: Display all sent messages with filtering options -->
    <x-ui.card>
        <x-ui.card-header>
            <x-ui.card-title>Filter SMS Logs</x-ui.card-title>
        </x-ui.card-header>
        <x-ui.card-content>
            <form method="GET" action="{{ route('dashboard.communication.logs') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <x-ui.label for="status">Status</x-ui.label>
                        <x-ui.select name="status" id="status" :selected="$filters['status'] ?? ''">
                            <option value="">All Status</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    
                    <div>
                        <x-ui.label for="type">Type</x-ui.label>
                        <x-ui.select name="type" id="type" :selected="$filters['type'] ?? ''">
                            <option value="">All Types</option>
                            @foreach($typeOptions as $type)
                                <option value="{{ $type }}" {{ ($filters['type'] ?? '') === $type ? 'selected' : '' }}>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    
                    <div>
                        <x-ui.label for="phone">Phone Number</x-ui.label>
                        <x-ui.input type="text" name="phone" id="phone" placeholder="Search phone..." value="{{ $filters['phone'] ?? '' }}" />
                    </div>
                    
                    <div>
                        <x-ui.date-picker name="date_from" id="date_from" label="From Date" value="{{ $filters['date_from'] ?? '' }}" />
                    </div>
                    
                    <div>
                        <x-ui.date-picker name="date_to" id="date_to" label="To Date" value="{{ $filters['date_to'] ?? '' }}" />
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <x-ui.button type="submit">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Apply Filters
                    </x-ui.button>
                    
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('dashboard.communication.logs') }}" class="text-gray-600 hover:text-gray-800 text-sm">
                            Clear Filters
                        </a>
                    @endif
                </div>
            </form>
        </x-ui.card-content>
    </x-ui.card>

    <!-- SMS Logs Table -->
    <x-ui.card>
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">SMS Logs</h3>
                <p class="text-sm text-gray-500">{{ $logs->total() }} total messages</p>
            </div>
            <div class="flex items-center space-x-3">
                <form action="{{ route('dashboard.communication.retry-failed') }}" method="POST" class="inline">
                    @csrf
                    <x-ui.button type="submit" variant="outline" size="sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Retry All Failed
                    </x-ui.button>
                </form>
                
                <x-ui.button type="button" variant="secondary" size="sm" id="bulkRetryBtn" class="hidden" onclick="retrySelected()">
                    Retry Selected (<span id="selectedCount">0</span>)
                </x-ui.button>
            </div>
        </div>

        <x-ui.card-content class="p-0">
            <x-ui.table>
                <x-ui.table-header>
                    <x-ui.table-row>
                        <x-ui.table-head class="w-10">
                            <x-ui.checkbox id="selectAll" onchange="toggleSelectAll()" />
                        </x-ui.table-head>
                        <x-ui.table-head>Phone</x-ui.table-head>
                        <x-ui.table-head>Message</x-ui.table-head>
                        <x-ui.table-head>Type</x-ui.table-head>
                        <x-ui.table-head>Status</x-ui.table-head>
                        <x-ui.table-head>Sent At</x-ui.table-head>
                        <x-ui.table-head>Created</x-ui.table-head>
                        <x-ui.table-head>Actions</x-ui.table-head>
                    </x-ui.table-row>
                </x-ui.table-header>
                <x-ui.table-body>
                    @forelse($logs as $log)
                        <x-ui.table-row>
                            <x-ui.table-cell>
                                @if($log->status === 'failed')
                                    <x-ui.checkbox class="log-checkbox" data-id="{{ $log->id }}" onchange="updateSelectedCount()" />
                                @endif
                            </x-ui.table-cell>
                            <x-ui.table-cell class="font-medium">{{ $log->phone }}</x-ui.table-cell>
                            <x-ui.table-cell class="max-w-xs">
                                <span class="truncate block" title="{{ $log->message }}">{{ Str::limit($log->message, 60) }}</span>
                            </x-ui.table-cell>
                            <x-ui.table-cell>
                                <x-ui.badge variant="outline">{{ ucfirst($log->type ?? 'general') }}</x-ui.badge>
                            </x-ui.table-cell>
                            <x-ui.table-cell>
                                @php
                                    $statusVariant = match($log->status) {
                                        'sent', 'delivered' => 'success',
                                        'failed' => 'destructive',
                                        'pending' => 'warning',
                                        default => 'secondary'
                                    };
                                @endphp
                                <x-ui.badge variant="{{ $statusVariant }}">{{ ucfirst($log->status) }}</x-ui.badge>
                                @if($log->status === 'failed' && $log->error_message)
                                    <p class="text-xs text-red-500 mt-1" title="{{ $log->error_message }}">{{ Str::limit($log->error_message, 30) }}</p>
                                @endif
                            </x-ui.table-cell>
                            <x-ui.table-cell>{{ $log->sent_at ? $log->sent_at->format('d M Y H:i') : '-' }}</x-ui.table-cell>
                            <x-ui.table-cell>{{ $log->created_at->format('d M Y H:i') }}</x-ui.table-cell>
                            <x-ui.table-cell>
                                <div class="flex items-center space-x-2">
                                    <button type="button" class="text-gray-500 hover:text-blue-600" title="View Details" onclick="showLogDetails({{ json_encode($log) }})">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    @if($log->status === 'failed')
                                        <form action="{{ route('dashboard.communication.retry', $log) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-gray-500 hover:text-green-600" title="Retry">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </x-ui.table-cell>
                        </x-ui.table-row>
                    @empty
                        <x-ui.table-row>
                            <x-ui.table-cell colspan="8" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-medium text-gray-900">No SMS logs</h3>
                                    <p class="mt-1 text-sm text-gray-500 max-w-sm">
                                        @if(!empty(array_filter($filters)))
                                            No SMS logs match your filter criteria. Try adjusting your filters or date range.
                                        @else
                                            SMS logs will appear here once messages are sent through the system.
                                        @endif
                                    </p>
                                    @if(!empty(array_filter($filters)))
                                        <a href="{{ route('dashboard.communication.logs') }}"
                                            class="mt-3 text-bd-green hover:text-bd-green-dark font-medium">
                                            Clear all filters
                                        </a>
                                    @else
                                        <a href="{{ route('dashboard.communication.index') }}"
                                            class="mt-4 inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors text-sm">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                            </svg>
                                            Send SMS
                                        </a>
                                    @endif
                                </div>
                            </x-ui.table-cell>
                        </x-ui.table-row>
                    @endforelse
                </x-ui.table-body>
            </x-ui.table>
            
            @if($logs->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $logs->withQueryString()->links() }}
                </div>
            @endif
        </x-ui.card-content>
    </x-ui.card>
</div>

<!-- Log Details Modal -->
<div id="logDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">SMS Details</h3>
            <button type="button" onclick="closeLogDetails()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4" id="logDetailsContent"></div>
    </div>
</div>

@push('scripts')
<script>
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.log-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.log-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('bulkRetryBtn').classList.toggle('hidden', count === 0);
}

async function retrySelected() {
    const checkboxes = document.querySelectorAll('.log-checkbox:checked');
    const logIds = Array.from(checkboxes).map(cb => parseInt(cb.dataset.id));
    if (logIds.length === 0) return;
    
    try {
        const response = await fetch('{{ route("dashboard.communication.retry-bulk") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ log_ids: logIds }),
        });
        const data = await response.json();
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Failed to retry messages');
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
    }
}

function showLogDetails(log) {
    const modal = document.getElementById('logDetailsModal');
    const content = document.getElementById('logDetailsContent');
    const statusColors = {
        'sent': 'bg-green-100 text-green-800',
        'delivered': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800',
        'pending': 'bg-yellow-100 text-yellow-800',
    };
    
    content.innerHTML = `
        <div class="space-y-4">
            <div><label class="text-sm font-medium text-gray-500">Phone Number</label><p class="text-gray-900">${log.phone}</p></div>
            <div><label class="text-sm font-medium text-gray-500">Message</label><p class="text-gray-900 bg-gray-50 p-3 rounded-lg">${log.message}</p></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-sm font-medium text-gray-500">Type</label><p class="text-gray-900">${log.type || 'general'}</p></div>
                <div><label class="text-sm font-medium text-gray-500">Status</label><p><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColors[log.status] || 'bg-gray-100 text-gray-800'}">${log.status}</span></p></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-sm font-medium text-gray-500">Created At</label><p class="text-gray-900">${new Date(log.created_at).toLocaleString()}</p></div>
                <div><label class="text-sm font-medium text-gray-500">Sent At</label><p class="text-gray-900">${log.sent_at ? new Date(log.sent_at).toLocaleString() : '-'}</p></div>
            </div>
            ${log.error_message ? `<div><label class="text-sm font-medium text-gray-500">Error Message</label><p class="text-red-600 bg-red-50 p-3 rounded-lg">${log.error_message}</p></div>` : ''}
        </div>
    `;
    modal.classList.remove('hidden');
}

function closeLogDetails() {
    document.getElementById('logDetailsModal').classList.add('hidden');
}

document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeLogDetails(); });
document.getElementById('logDetailsModal').addEventListener('click', function(e) { if (e.target === this) closeLogDetails(); });
</script>
@endpush
@endsection
