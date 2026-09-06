@extends('layouts.frontend')

@section('title', 'Notices - Dhaka IT Institute')

@section('content')
<section class="bg-gray-50 py-12 min-h-screen">
    <div class="mx-auto max-w-4xl px-4">
        <h1 class="text-3xl font-bold text-gray-900">নোটিশ বোর্ড</h1>
        <p class="mt-1 text-sm text-gray-600">Latest institute notices and announcements.</p>
        <div class="mt-6 space-y-4">
            @forelse($announcements as $announcement)
            <a href="{{ route('announcement.show', $announcement) }}" class="block rounded-xl border bg-white p-5 shadow-sm hover:shadow">
                <p class="font-semibold text-gray-900">{{ $announcement->title }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($announcement->content, 160) }}</p>
                <p class="mt-2 text-xs text-gray-500">{{ ($announcement->starts_at ?? $announcement->created_at)->format('d M, Y') }} • {{ ucfirst($announcement->priority) }}</p>
            </a>
            @empty
            <p class="text-gray-600">কোন নোটিশ পাওয়া যায়নি।</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $announcements->links() }}</div>
    </div>
</section>
@endsection
