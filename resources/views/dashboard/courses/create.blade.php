@extends('layouts.admin')

@section('title', 'Create Course')
@section('page-title', 'Create New Course')
@section('page-description', 'Add a new course to the system')

@section('content')
    <div class="max-w-4xl mx-auto">
        <form action="{{ route('dashboard.courses.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Global Validation Error Display --}}
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 mr-2 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <strong class="font-semibold">Please fix the following errors:</strong>
                    </div>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Basic Information -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Course Information</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.text-input name="name" label="Course Name" required placeholder="Enter course name" persist />
                    <x-ui.text-input name="code" label="Course Code" required placeholder="e.g., WEB001" persist />
                    
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-semibold text-gray-700 mb-1.5">Description</label>
                        <textarea name="description" id="description" rows="4"
                                  class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent transition-all outline-none text-gray-900 placeholder:text-gray-400"
                                  placeholder="Course description...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Pricing & Duration -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Pricing & Duration</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <x-ui.text-input name="price" label="Price (৳)" type="number" required placeholder="0.00" persist />
                    <x-ui.text-input name="duration" label="Duration" type="number" placeholder="e.g., 6" persist />
                    
                    <x-ui.select name="duration_unit" label="Duration Unit" persist>
                        <option value="months">Months</option>
                        <option value="weeks">Weeks</option>
                        <option value="days">Days</option>
                        <option value="hours">Hours</option>
                    </x-ui.select>
                </div>
            </div>

            <!-- Category & Level -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Category & Level</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <x-ui.text-input name="category" label="Category" placeholder="e.g., Programming, Design" persist />
                    
                    <x-ui.select name="class" label="Class (Grade)" persist>
                        <option value="">Select Class</option>
                        @foreach(range(1, 12) as $c)
                            <option value="{{ $c }}">Class {{ $c }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select name="level" label="Difficulty Level" required persist>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </x-ui.select>
                    <x-ui.select name="delivery_mode" label="Delivery Mode" required persist>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                    </x-ui.select>
                    <div class="md:col-span-3 grid gap-4 md:grid-cols-2">
                        <div><label class="mb-1 block text-sm font-semibold">Online student details</label><textarea name="online_details" rows="4" class="w-full rounded-lg border-gray-300" placeholder="Live link policy, platform, support, recordings...">{{ old('online_details') }}</textarea></div>
                        <div><label class="mb-1 block text-sm font-semibold">Offline student details</label><textarea name="offline_details" rows="4" class="w-full rounded-lg border-gray-300" placeholder="Campus, room, class schedule, materials...">{{ old('offline_details') }}</textarea></div>
                    </div>
                </div>
            </div>

            <!-- Status & Image -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Status & Image</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.select name="status" label="Status" required persist>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="draft">Draft</option>
                    </x-ui.select>

                    <div class="md:col-span-1">
                        <x-ui.image-input name="image" label="Course Image" helperText="Upload a course thumbnail (JPEG, PNG, max 2MB)" persist />
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 mb-10">
                <a href="{{ route('dashboard.courses.index') }}"
                   class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium">
                    Create Course
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                const keysToClear = ['input_persist_', 'image_persist_', 'date_persist_', 'select_persist_'];
                Object.keys(localStorage).forEach(key => {
                    if (keysToClear.some(prefix => key.startsWith(prefix + window.location.pathname))) {
                        localStorage.removeItem(key);
                    }
                });
            @endif
        });
    </script>
    @endpush
@endsection
