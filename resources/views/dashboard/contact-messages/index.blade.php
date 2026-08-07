@extends('layouts.admin')

@section('title', 'Contact Messages')
@section('page-title', 'Contact Messages')
@section('page-description', 'Messages submitted through the contact form')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-sm text-gray-500">Total</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-sm text-gray-500">New</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['new'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-sm text-gray-500">Read</p>
            <p class="text-2xl font-bold text-amber-600">{{ $stats['read'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-sm text-gray-500">Replied</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $stats['replied'] }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email or subject..."
                class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
            <select name="status" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary">
                <option value="">All statuses</option>
                <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
                <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read</option>
                <option value="replied" {{ request('status') === 'replied' ? 'selected' : '' }}>Replied</option>
            </select>
            <button type="submit" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">Filter</button>
            @if(request('search') || request('status'))
                <a href="{{ route('dashboard.contact-messages.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">Clear</a>
            @endif
        </form>
    </div>

    <!-- Messages List -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        @forelse($messages as $message)
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 p-5 hover:bg-gray-50 transition {{ $message->status === 'new' ? 'bg-blue-50/50' : '' }}">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-gray-900">{{ $message->name }}</span>
                        @if($message->status === 'new')
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold uppercase text-red-700">New</span>
                        @elseif($message->status === 'read')
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-700">Read</span>
                        @else
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700">Replied</span>
                        @endif
                        <span class="text-xs text-gray-400">{{ $message->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    <p class="mt-1 text-sm font-medium text-gray-700">{{ $message->subject }}</p>
                    <p class="mt-0.5 text-sm text-gray-500 line-clamp-2">{{ $message->message }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $message->email }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ route('dashboard.contact-messages.show', $message) }}"
                        class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90">View</a>
                    <form action="{{ route('dashboard.contact-messages.destroy', $message) }}" method="POST"
                        onsubmit="return confirm('Delete this message?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-gray-500">
                <i class="fa-solid fa-envelope-open-text text-4xl text-gray-300 mb-3"></i>
                <p>No contact messages found.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $messages->links() }}</div>
</div>
@endsection
