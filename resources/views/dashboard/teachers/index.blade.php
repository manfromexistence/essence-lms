@extends('layouts.admin')

@section('title', 'Manage Teachers')
@section('page-title', 'Teacher Management')
@section('page-description', 'Manage all teachers in the system')

@section('content')
    <div class="bg-white rounded-xl shadow-md border border-gray-100">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h2 class="text-lg font-semibold text-gray-900">All Teachers</h2>
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    {{ $teachers->total() ?? $teachers->count() }} Total
                </span>
            </div>
            <a href="{{ route('dashboard.teachers.create') }}"
                class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium text-sm shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Teacher
            </a>
        </div>

        @php
            $headers = [
                ['key' => 'teacher', 'label' => 'Teacher'],
                ['key' => 'contact', 'label' => 'Contact'],
                ['key' => 'department', 'label' => 'Department'],
                ['key' => 'subjects', 'label' => 'Subjects'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'actions', 'label' => 'Actions'],
            ];
        @endphp

        <x-ui.data-table id="teacher-table" :headers="$headers" :rows="$teachers" :route="route('dashboard.teachers.index')">
            @forelse($teachers as $teacher)
                <tr class="hover:bg-gray-50 transition-colors">
                    <!-- Teacher -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="shrink-0 w-10 h-10">
                                @if ($teacher->profile_image)
                                    <img class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm"
                                        src="{{ media_url($teacher->profile_image) }}"
                                        alt="{{ $teacher->user->name }}">
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

                    <!-- Contact -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $teacher->user->email ?? 'N/A' }}</div>
                        <div class="text-sm text-gray-500">{{ $teacher->phone ?? 'No phone' }}</div>
                    </td>

                    <!-- Department -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $teacher->department ?? 'General' }}
                        </span>
                    </td>

                    <!-- Subjects -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if ($teacher->subjects && is_array($teacher->subjects))
                            {{ Str::limit(implode(', ', $teacher->subjects), 30) }}
                        @else
                            Not assigned
                        @endif
                    </td>

                    <!-- Status -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $teacher->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($teacher->status ?? 'Active') }}
                        </span>
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                        <div class="flex justify-start space-x-2">
                            <a href="{{ route('dashboard.teachers.show', $teacher) }}"
                                class="text-gray-500 hover:text-blue-600 transition-colors" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            <a href="{{ route('dashboard.teachers.edit', $teacher) }}"
                                class="text-gray-500 hover:text-bd-green transition-colors" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            <form action="{{ route('dashboard.teachers.destroy', $teacher) }}" method="POST"
                                class="inline" onsubmit="return confirmDelete(this, 'Are you sure you want to delete this teacher? This action cannot be undone.')">
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
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <h3 class="text-sm font-medium text-gray-900">No teachers found</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by adding a new teacher.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-ui.data-table>
    </div>
@endsection
