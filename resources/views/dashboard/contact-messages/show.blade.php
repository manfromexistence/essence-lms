@extends('layouts.admin')

@section('title', 'Message - ' . $message->name)
@section('page-title', 'Contact Message')
@section('page-description', 'View message details')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between">
        <a href="{{ route('dashboard.contact-messages.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline">
            <i class="fa-solid fa-arrow-left"></i> Back to messages
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">{{ $message->subject }}</h2>
                <p class="text-xs text-gray-500 mt-1">Received {{ $message->created_at->format('d M Y, h:i A') }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-bold uppercase
                {{ $message->status === 'new' ? 'bg-red-100 text-red-700' : ($message->status === 'read' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                {{ $message->status }}
            </span>
        </div>
        <div class="px-6 py-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase text-gray-400">Name</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $message->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-gray-400">Email</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $message->email }}</p>
                </div>
                @if($message->ip_address)
                <div>
                    <p class="text-xs font-semibold uppercase text-gray-400">IP Address</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $message->ip_address }}</p>
                </div>
                @endif
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-gray-400">Message</p>
                <div class="mt-2 rounded-lg bg-gray-50 p-4 text-sm leading-7 text-gray-800 whitespace-pre-wrap">{{ $message->message }}</div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="mailto:{{ $message->email }}?subject=Re: {{ $message->subject }}"
            class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">
            <i class="fa-solid fa-reply"></i> Reply via Email
        </a>
        <form action="{{ route('dashboard.contact-messages.status', $message) }}" method="POST" class="inline-flex items-center gap-2">
            @csrf
            <select name="status" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                <option value="new" {{ $message->status === 'new' ? 'selected' : '' }}>New</option>
                <option value="read" {{ $message->status === 'read' ? 'selected' : '' }}>Read</option>
                <option value="replied" {{ $message->status === 'replied' ? 'selected' : '' }}>Replied</option>
            </select>
            <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Update Status</button>
        </form>
        <form action="{{ route('dashboard.contact-messages.destroy', $message) }}" method="POST" onsubmit="return confirm('Delete this message?')">
            @csrf @method('DELETE')
            <button type="submit" class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">Delete</button>
        </form>
    </div>
</div>
@endsection
