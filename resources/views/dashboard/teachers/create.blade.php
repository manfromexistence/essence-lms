@extends('layouts.admin')

@section('title', 'Add New Teacher')
@section('page-title', 'Add New Teacher')
@section('page-description', 'Create a new teacher account and assign details')

@section('content')
    <div class="max-w-4xl mx-auto">
        <form action="{{ route('dashboard.teachers.store') }}" method="POST" enctype="multipart/form-data">
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

            <!-- Personal Information -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Personal Information</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.text-input name="name" label="Full Name" required persist />
                    <x-ui.text-input name="email" label="Email Address" type="email" required persist />
                    <x-ui.text-input name="phone" label="Phone Number" type="tel" persist />
                    <x-ui.date-picker name="dob" label="Date of Birth" placeholder="Select Birth Date" persist />

                    <div class="md:col-span-2">
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-ui.password-input name="password" label="Password" placeholder="Enter a strong password" required autocomplete="new-password" />
                            <x-ui.password-input name="password_confirmation" label="Confirm Password" placeholder="Re-enter password" required autocomplete="new-password" />
                        </div>
                    </div>

                    <x-ui.select name="gender" label="Gender" persist>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </x-ui.select>

                    <div class="md:col-span-2">
                        <x-ui.image-input name="profile_image" label="Profile Photo" helperText="Passport size photo recommended." persist />
                    </div>
                </div>
            </div>

            <!-- Professional Information -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Professional Information</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.select name="department" label="Department" persist>
                        <option value="">Select Department</option>
                        <option value="Computer Science">Computer Science</option>
                        <option value="Graphic Design">Graphic Design</option>
                        <option value="Digital Marketing">Digital Marketing</option>
                        <option value="Mathematics">Mathematics</option>
                        <option value="English">English</option>
                        <option value="Science">Science</option>
                        <option value="Bangla">Bangla</option>
                        <option value="Physics">Physics</option>
                        <option value="Chemistry">Chemistry</option>
                        <option value="Biology">Biology</option>
                    </x-ui.select>

                    <x-ui.text-input name="designation" label="Designation / Title" placeholder="e.g., Senior Web Instructor" persist />
                    <x-ui.text-input name="salary" label="Monthly Salary (৳)" type="number" placeholder="0.00" persist />
                    <x-ui.text-input name="qualification" label="Highest Qualification" placeholder="e.g., M.Sc in Computer Science" persist />
                    <x-ui.text-input name="experience" label="Years of Experience" type="number" placeholder="0" persist />
                    <x-ui.text-input name="display_order" label="Display Order" type="number" placeholder="0" helperText="Lower shows first on Team page" persist />
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700"><input type="checkbox" name="is_featured" value="1" class="rounded border-gray-300 text-bd-green focus:ring-bd-green" /> Featured on Team page</label>
                </div>
                <div class="p-6 pt-0">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Bio</label>
                    <textarea name="bio" rows="3" placeholder="Short bio shown on team card & modal" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-transparent focus:ring-2 focus:ring-bd-green outline-none">{{ old('bio') }}</textarea>
                </div>
                <div class="p-6 pt-0 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.text-input name="social_links[facebook]" label="Facebook URL" placeholder="https://facebook.com/..." persist />
                    <x-ui.text-input name="social_links[linkedin]" label="LinkedIn URL" placeholder="https://linkedin.com/in/..." persist />
                    <x-ui.text-input name="social_links[twitter]" label="X / Twitter URL" placeholder="https://x.com/..." persist />
                    <x-ui.text-input name="social_links[instagram]" label="Instagram URL" placeholder="https://instagram.com/..." persist />
                    <x-ui.text-input name="social_links[github]" label="GitHub URL" placeholder="https://github.com/..." persist />
                    <x-ui.text-input name="social_links[website]" label="Website URL" placeholder="https://..." persist />
                </div>
            </div>

            <!-- Subjects -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Subjects</h3>
                    <p class="text-sm text-gray-500 mt-1">Select the subjects this teacher can teach</p>
                </div>
                <div class="p-6">
                    @php
                        $availableSubjects = [
                            'Programming', 'Database Management', 'Web Development', 
                            'Graphic Design', 'UI/UX Design', 'Digital Art', 
                            'Digital Marketing', 'Business Development', 'Entrepreneurship', 
                            'Mathematics', 'Statistics', 'Data Analysis', 
                            'English Literature', 'Communication Skills', 'Writing', 
                            'Physics', 'Chemistry', 'Biology',
                            'Bangla', 'History', 'Geography', 'Economics'
                        ];
                    @endphp

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($availableSubjects as $subject)
                            <label class="flex items-center space-x-3 cursor-pointer p-2 rounded-lg hover:bg-gray-50 transition-colors">
                                <x-ui.checkbox name="subjects[]" value="{{ $subject }}" :checked="in_array($subject, old('subjects', []))" />
                                <span class="text-sm text-gray-700">{{ $subject }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('subjects')
                        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Address Information -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Address Information</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.text-input name="address" label="Street Address" persist />
                    <x-ui.text-input name="city" label="City" persist />
                    <x-ui.text-input name="district" label="District" persist />
                    <x-ui.text-input name="postal_code" label="Postal Code" persist />
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 mb-10">
                <a href="{{ route('dashboard.teachers.index') }}"
                   class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Teacher
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Clear storage ONLY on success
            @if(session('success'))
                const keysToClear = [
                    'input_persist_',
                    'image_persist_',
                    'date_persist_',
                    'select_persist_'
                ];
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