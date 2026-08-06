@extends('layouts.admin')

@section('title', 'Edit Teacher')
@section('page-title', 'Edit Teacher')
@section('page-description', 'Update teacher information and assignments')

@section('content')
    <div class="max-w-4xl mx-auto">
        <form action="{{ route('dashboard.teachers.update', $teacher) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

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
                    <x-ui.text-input name="name" label="Full Name" required :value="old('name', $teacher->user->name)" />
                    <x-ui.text-input name="email" label="Email Address" type="email" required :value="old('email', $teacher->user->email)" />
                    <x-ui.text-input name="phone" label="Phone Number" type="tel" :value="old('phone', $teacher->phone)" />
                    <x-ui.date-picker name="dob" label="Date of Birth" placeholder="Select Birth Date" :value="old('dob', $teacher->dob)" />
                    
                    <x-ui.select name="gender" label="Gender" :selected="old('gender', $teacher->gender)">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </x-ui.select>

                    <div class="md:col-span-2">
                        <x-ui.image-input name="profile_image" label="Profile Photo" :value="$teacher->profile_image" helperText="Passport size photo recommended." />
                    </div>
                </div>
            </div>

            <!-- Professional Information -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Professional Information</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.select name="department" label="Department" :selected="old('department', $teacher->department)">
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

                    <x-ui.text-input name="designation" label="Designation / Title" placeholder="e.g., Senior Web Instructor" :value="old('designation', $teacher->designation)" />
                    <x-ui.text-input name="salary" label="Monthly Salary (৳)" type="number" placeholder="0.00" :value="old('salary', $teacher->salary)" />
                    <x-ui.text-input name="qualification" label="Highest Qualification" placeholder="e.g., M.Sc in Computer Science" :value="old('qualification', $teacher->qualification)" />
                    <x-ui.text-input name="experience" label="Years of Experience" type="number" placeholder="0" :value="old('experience', $teacher->experience)" />
                    <x-ui.text-input name="display_order" label="Display Order" type="number" :value="old('display_order', $teacher->display_order)" helperText="Lower shows first" />
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $teacher->is_featured)) class="rounded border-gray-300 text-bd-green focus:ring-bd-green" /> Featured</label>

                    <x-ui.select name="status" label="Status" :selected="old('status', $teacher->status)">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </x-ui.select>
                </div>
                <div class="p-6 pt-0">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Bio</label>
                    <textarea name="bio" rows="3" placeholder="Short bio" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-transparent focus:ring-2 focus:ring-bd-green outline-none">{{ old('bio', $teacher->bio) }}</textarea>
                </div>
                <div class="p-6 pt-0 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @php $sl = $teacher->social_links ?? []; @endphp
                    <x-ui.text-input name="social_links[facebook]" label="Facebook URL" :value="old('social_links.facebook', $sl['facebook'] ?? '')" placeholder="https://facebook.com/..." />
                    <x-ui.text-input name="social_links[linkedin]" label="LinkedIn URL" :value="old('social_links.linkedin', $sl['linkedin'] ?? '')" placeholder="https://linkedin.com/in/..." />
                    <x-ui.text-input name="social_links[twitter]" label="X / Twitter URL" :value="old('social_links.twitter', $sl['twitter'] ?? '')" placeholder="https://x.com/..." />
                    <x-ui.text-input name="social_links[instagram]" label="Instagram URL" :value="old('social_links.instagram', $sl['instagram'] ?? '')" placeholder="https://instagram.com/..." />
                    <x-ui.text-input name="social_links[github]" label="GitHub URL" :value="old('social_links.github', $sl['github'] ?? '')" placeholder="https://github.com/..." />
                    <x-ui.text-input name="social_links[website]" label="Website URL" :value="old('social_links.website', $sl['website'] ?? '')" placeholder="https://..." />
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
                        $teacherSubjects = old('subjects', $teacher->subjects ?? []);
                    @endphp

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($availableSubjects as $subject)
                            <label class="flex items-center space-x-3 cursor-pointer p-2 rounded-lg hover:bg-gray-50 transition-colors">
                                <x-ui.checkbox name="subjects[]" value="{{ $subject }}" :checked="in_array($subject, $teacherSubjects)" />
                                <span class="text-sm text-gray-700">{{ $subject }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('subjects')
                        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 mb-10">
                <a href="{{ route('dashboard.teachers.show', $teacher) }}"
                   class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Update Teacher
                </button>
            </div>
        </form>
    </div>
@endsection