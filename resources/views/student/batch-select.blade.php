@extends('layouts.admin')

@section('title', 'Choose Batch - ' . $course->name)

@section('content')
<div class="mx-auto max-w-3xl px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900">Choose your batch</h1>
    <p class="mt-1 text-sm text-gray-600">Payment approved for <strong>{{ $course->name }}</strong>. Select a batch to start learning.</p>

    @if($errors->any())
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form action="{{ route('student.course.batch', $course) }}" method="POST" class="mt-6 space-y-4 rounded-xl border bg-white p-6 shadow-sm">
        @csrf
        @forelse($batches as $batch)
        <label class="flex items-center justify-between gap-4 rounded-lg border px-4 py-3 hover:bg-gray-50">
            <span>
                <span class="block font-semibold text-gray-900">{{ $batch->name }}</span>
                <span class="block text-xs text-gray-500">{{ $batch->schedule ?? 'Schedule announced soon' }}{{ $batch->room ? ' • Room: '.$batch->room : '' }} • {{ $batch->students()->count() }}{{ $batch->max_students ? '/'.$batch->max_students : '' }} seats taken</span>
            </span>
            <input type="radio" name="batch_id" value="{{ $batch->id }}" required class="h-5 w-5">
        </label>
        @empty
        <p class="text-sm text-gray-600">No active batches for this course yet. Please contact the office — we will assign you as soon as a batch opens.</p>
        @endforelse

        @if($batches->isNotEmpty())
        <button class="w-full rounded-lg bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-700">Confirm batch</button>
        @endif
    </form>
</div>
@endsection
