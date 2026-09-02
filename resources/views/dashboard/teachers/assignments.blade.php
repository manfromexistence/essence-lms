@extends('layouts.admin')

@section('title', 'Teacher Assignments')
@section('page-title', 'শিক্ষক নিয়োগ')
@section('page-description', 'Manage subject and class assignments for teachers')

@section('content')
    <div class="bg-white rounded-xl shadow-md border border-gray-100">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h2 class="text-lg font-semibold text-gray-900">Teacher Assignments</h2>
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    {{ $teachers->total() }} Teachers Assigned
                </span>
            </div>
            <button
                class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Assign Teacher
            </button>
        </div>

        @php
            $headers = [
                ['key' => 'teacher', 'label' => 'Teacher'],
                ['key' => 'department', 'label' => 'Department'],
                ['key' => 'assigned_batches', 'label' => 'Assigned Batches'],
                ['key' => 'subjects', 'label' => 'Subjects'],
                ['key' => 'actions', 'label' => 'Actions'],
            ];
        @endphp

        <x-ui.data-table id="assignments-table" :headers="$headers" :rows="$teachers" :route="route('dashboard.teachers.assignments')">
            @forelse($teachers as $teacher)
                <tr class="hover:bg-gray-50 transition-colors">
                    <!-- Teacher -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="shrink-0 w-10 h-10">
                                @if ($teacher->profile_image)
                                    <img src="{{ media_url($teacher->profile_image ?? '') }}"
                                        alt="{{ $teacher->user->name }}"
                                        class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm">
                                @else
                                    <div
                                        class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold shadow-sm">
                                        {{ strtoupper(substr($teacher->user->name ?? 'T', 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">{{ $teacher->user->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">ID: TCH-{{ str_pad($teacher->id, 5, '0', STR_PAD_LEFT) }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Department -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $teacher->department ?? 'General' }}
                        </span>
                    </td>

                    <!-- Assigned Batches -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-wrap gap-1">
                            @forelse($teacher->batches as $batch)
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ $batch->name }}
                                </span>
                            @empty
                                <span class="text-sm text-gray-500">No batches assigned</span>
                            @endforelse
                        </div>
                    </td>

                    <!-- Subjects -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if ($teacher->subjects && is_array($teacher->subjects))
                            @foreach ($teacher->subjects as $subject)
                                <span
                                    class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded mr-1 mb-1">{{ $subject }}</span>
                            @endforeach
                        @else
                            Not specified
                        @endif
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                        <div class="flex justify-start space-x-2">
                        <div class="flex justify-start space-x-2">
                            <a href="{{ route('dashboard.teachers.assignment.edit', $teacher) }}" class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit Assignment">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            <form action="{{ route('dashboard.teachers.assignment.remove', $teacher) }}" method="POST" onsubmit="return confirmDelete(this, 'Are you sure you want to remove all assignments for this teacher?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Remove All Assignments">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <h3 class="text-sm font-medium text-gray-900">No teacher assignments</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by assigning teachers to batches and subjects.
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-ui.data-table>
    </div>
@endsection
