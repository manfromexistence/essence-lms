@extends('layouts.admin')

@section('title', 'Manage Students')
@section('page-title', 'Student Management')
@section('page-description', 'Manage all students enrolled in the system')

@section('content')
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach([
            ['label' => 'All Students', 'value' => $studentCounts['all'], 'color' => 'blue'],
            ['label' => 'Online Students', 'value' => $studentCounts['online'], 'color' => 'emerald'],
            ['label' => 'Offline Students', 'value' => $studentCounts['offline'], 'color' => 'amber'],
        ] as $stat)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stat['value']) }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-100">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-4">
                <h2 class="text-lg font-semibold text-gray-900">All Students</h2>
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $students->total() ?? $students->count() }} Total
                </span>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admission.offline') }}" target="_blank" rel="noopener"
                    class="inline-flex items-center justify-center rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-900 transition-colors hover:bg-amber-100">
                    Offline Admission Form
                </a>
                <a href="{{ route('dashboard.students.create', ['mode' => 'offline']) }}"
                    class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New Student
                </a>
            </div>
        </div>

        <form action="{{ route('dashboard.students.index') }}" method="GET" class="border-b border-gray-200 bg-gray-50/70 p-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div class="xl:col-span-2">
                    <label for="student-search" class="mb-1.5 block text-sm font-semibold text-gray-700">Search students</label>
                    <input id="student-search" name="search" value="{{ $filters['search'] }}" type="search"
                        placeholder="Enter name, number, batch, area or blood group"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-bd-green focus:ring-bd-green">
                </div>
                <div>
                    <label for="search-field" class="mb-1.5 block text-sm font-semibold text-gray-700">Search field</label>
                    <x-ui.select name="search_field" id="search-field"
                        :options="['all' => 'All fields', 'name' => 'Name', 'number' => 'Phone / ID number', 'batch' => 'Batch', 'area' => 'Area', 'blood_group' => 'Blood group']"
                        :selected="$filters['search_field'] ?? 'all'" />
                </div>
                <div>
                    <label for="student-mode" class="mb-1.5 block text-sm font-semibold text-gray-700">Student type</label>
                    <x-ui.select name="mode" id="student-mode"
                        :options="['' => 'Online & offline', 'online' => 'Online students', 'offline' => 'Offline students']"
                        :selected="$filters['mode'] ?? ''" />
                </div>
                <div>
                    <label for="student-batch" class="mb-1.5 block text-sm font-semibold text-gray-700">Batch</label>
                    <x-ui.select name="batch_id" id="student-batch"
                        :options="['' => 'All batches'] + $batches->pluck('name', 'id')->map(fn($n) => (string) $n)->all()"
                        :selected="(string) ($filters['batch_id'] ?? '')" />
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-lg bg-bd-green px-4 py-2.5 text-sm font-semibold text-white hover:bg-bd-green-dark">Search</button>
                    <a href="{{ route('dashboard.students.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-100">Reset</a>
                </div>
            </div>
        </form>

        @php
            $headers = [
                ['key' => 'student', 'label' => 'Student'],
                ['key' => 'contact', 'label' => 'Contact'],
                ['key' => 'admission_mode', 'label' => 'Type'],
                ['key' => 'batch', 'label' => 'Batch'],
                ['key' => 'area', 'label' => 'Area'],
                ['key' => 'blood_group', 'label' => 'Blood Group'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'enrolled', 'label' => 'Enrolled'],
                ['key' => 'actions', 'label' => 'Actions'],
            ];
        @endphp

        <x-ui.data-table id="student-table" :headers="$headers" :rows="$students" :route="route('dashboard.students.index')" :searchable="false">
            @forelse($students as $student)
                <tr class="hover:bg-gray-50 transition-colors">
                    <!-- Student -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="shrink-0 w-10 h-10">
                                @if ($student->profile_image)
                                    <img class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm"
                                        src="{{ Str::startsWith($student->profile_image, 'http') ? $student->profile_image : asset('storage/' . $student->profile_image) }}"
                                        alt="{{ $student->user->name }}">
                                @else
                                    <div
                                        class="w-10 h-10 bg-bd-green rounded-full flex items-center justify-center text-white font-bold shadow-sm">
                                        {{ strtoupper(substr($student->user->name ?? 'S', 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">{{ $student->user->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">ID: STU-{{ str_pad($student->id, 5, '0', STR_PAD_LEFT) }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Contact -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $student->user->email ?? 'N/A' }}</div>
                        <div class="text-sm text-gray-500">{{ $student->phone ?? 'No phone' }}</div>
                    </td>

                    <!-- Online / Offline -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $student->admission_mode === 'online' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ ucfirst($student->admission_mode ?? 'offline') }}
                        </span>
                    </td>

                    <!-- Batch -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            {{ $student->batch->name ?? 'Unassigned' }}
                        </span>
                    </td>

                    <!-- Area -->
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ collect([$student->present_village, $student->present_ps, $student->present_dist])->filter()->join(', ') ?: 'Not provided' }}
                    </td>

                    <!-- Blood Group -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                        {{ $student->blood_group ?: 'N/A' }}
                    </td>

                    <!-- Status -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $admissionStatus = $student->admission_status ?? ($student->batch_id ? 'approved' : 'pending');
                            $statusBadge = [
                                'approved' => ['bg-green-100 text-green-800', 'Admitted'],
                                'rejected' => ['bg-red-100 text-red-800', 'Rejected'],
                                'pending' => ['bg-yellow-100 text-yellow-800', 'Pending'],
                            ][$admissionStatus] ?? ['bg-gray-100 text-gray-800', ucfirst($admissionStatus)];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadge[0] }}">
                            {{ $statusBadge[1] }}
                        </span>
                    </td>

                    <!-- Enrolled -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $student->created_at->format('M d, Y') }}
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                        <div class="flex justify-start space-x-2">
                            <a href="{{ route('dashboard.students.show', $student) }}"
                                class="text-gray-500 hover:text-blue-600 transition-colors" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            <a href="{{ route('dashboard.students.edit', $student) }}"
                                class="text-gray-500 hover:text-bd-green transition-colors" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            <form action="{{ route('dashboard.students.destroy', $student) }}" method="POST"
                                class="inline" onsubmit="return confirmDelete(this, 'Are you sure you want to delete this student? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-500 hover:text-red-600 transition-colors"
                                    title="Delete">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <h3 class="text-sm font-medium text-gray-900">No students found</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by adding a new student.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-ui.data-table>
    </div>
@endsection
