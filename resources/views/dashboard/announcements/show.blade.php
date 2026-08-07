@extends('layouts.admin')

@section('title', $announcement->title)
@section('page-title', 'Announcement Details')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4 flex justify-between items-center">
        <a href="{{ route('dashboard.announcements.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i> Back to Announcements
        </a>
        <div class="flex gap-2">
            <x-ui.button variant="outline" size="sm" type="button" onclick="openEditModal({{ $announcement->id }})">
                <i class="fas fa-edit mr-1"></i> Edit
            </x-ui.button>
            <form action="{{ route('dashboard.announcements.destroy', $announcement) }}" method="POST" onsubmit="return confirm('Delete this announcement?')">
                @csrf
                @method('DELETE')
                <x-ui.button variant="destructive" size="sm" type="submit">
                    <i class="fas fa-trash mr-1"></i> Delete
                </x-ui.button>
            </form>
        </div>
    </div>

    <x-ui.card>
        <x-ui.card-content class="p-8">
            <div class="flex items-center gap-3 mb-6">
                <span class="px-3 py-1 rounded-full text-xs font-semibold
                    @if($announcement->priority === 'urgent') bg-red-100 text-red-800
                    @elseif($announcement->priority === 'high') bg-orange-100 text-orange-800
                    @else bg-blue-100 text-blue-800 @endif">
                    {{ ucfirst($announcement->priority) }} Priority
                </span>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                    Target: {{ $announcement->target_name }}
                </span>
                @if($announcement->is_active)
                    <x-ui.badge class="bg-emerald-500">Active</x-ui.badge>
                @else
                    <x-ui.badge variant="secondary">Inactive</x-ui.badge>
                @endif
            </div>

            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $announcement->title }}</h1>

            <div class="flex items-center gap-4 text-sm text-gray-500 mb-6">
                <span><i class="far fa-calendar-alt mr-1"></i> {{ $announcement->starts_at ? $announcement->starts_at->format('M d, Y') : 'Immediately' }}</span>
                @if($announcement->expires_at)
                    <span><i class="far fa-clock mr-1"></i> Expires {{ $announcement->expires_at->format('M d, Y') }}</span>
                @endif
                @if($announcement->creator)
                    <span><i class="far fa-user mr-1"></i> {{ $announcement->creator->name }}</span>
                @endif
            </div>

            <div class="prose max-w-none text-gray-700 leading-relaxed border-t border-gray-100 pt-6">
                {!! nl2br(e($announcement->content)) !!}
            </div>
        </x-ui.card-content>
    </x-ui.card>
</div>
@endsection
