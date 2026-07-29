@extends('layouts.frontend')

@section('title', $announcement->title . ' - Dhaka IT Institute')

@section('content')
    <div class="py-16 bg-gray-50 min-h-screen">
        <div class="max-w-4xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            @if($announcement->priority === 'urgent') bg-red-100 text-red-800
                            @elseif($announcement->priority === 'high') bg-orange-100 text-orange-800
                            @else bg-blue-100 text-blue-800 @endif">
                            {{ ucfirst($announcement->priority) }} Priority
                        </span>
                        <span class="text-sm text-gray-500">
                            <i class="far fa-calendar-alt mr-1"></i>
                            {{ $announcement->starts_at ? $announcement->starts_at->format('F d, Y') : $announcement->created_at->format('F d, Y') }}
                        </span>
                    </div>

                    <h1 class="text-3xl font-bold text-gray-900 mb-6">{{ $announcement->title }}</h1>

                    <div class="prose max-w-none text-gray-700 leading-relaxed">
                        {!! nl2br(e($announcement->content)) !!}
                    </div>
                </div>
                <div class="bg-gray-50 px-8 py-4 border-t border-gray-100">
                    <a href="{{ url('/') }}" class="text-primary font-medium hover:underline">&larr; Back to Home</a>
                </div>
            </div>
        </div>
    </div>
@endsection
