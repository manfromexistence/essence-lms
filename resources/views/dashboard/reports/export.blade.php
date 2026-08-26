@extends('layouts.admin')

@section('title', 'Export Reports')
@section('page-title', 'রিপোর্ট রপ্তানি')
@section('page-description', 'Export reports to various formats')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Background Exports</h3>
                <p class="text-gray-500 max-w-lg mx-auto">Excel and PDF exports are generated in the background so
                    large datasets never time out. Start an export from any report page (Attendance, Payment,
                    Performance, Student) — when it is ready you will receive a dashboard notification with a
                    download link, and it will also appear below.</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Your recent exports</h3>
            @if($exports->isEmpty())
                <p class="text-sm text-gray-500">No exports yet. Generate one from a report page.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                                <th class="py-2 pr-4">Report</th>
                                <th class="py-2 pr-4">Format</th>
                                <th class="py-2 pr-4">Requested</th>
                                <th class="py-2 pr-4">Status</th>
                                <th class="py-2">File</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($exports as $export)
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-900 capitalize">{{ $export->report_type }}</td>
                                    <td class="py-2 pr-4 uppercase">{{ $export->format }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $export->created_at->format('d M Y, H:i') }}</td>
                                    <td class="py-2 pr-4">
                                        @if($export->isCompleted())
                                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">Ready</span>
                                        @elseif($export->isFailed())
                                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800"
                                                title="{{ $export->error }}">Failed</span>
                                        @else
                                            <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-800">Processing…</span>
                                        @endif
                                    </td>
                                    <td class="py-2">
                                        @if($export->isCompleted())
                                            <a href="{{ route('dashboard.reports.exports.download', $export) }}"
                                                class="font-semibold text-indigo-600 hover:underline">Download</a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
