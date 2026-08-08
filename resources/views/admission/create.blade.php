@extends('layouts.frontend')

@section('title', 'Student Admission - Dhaka IT Institute')

@section('content')
<section class="bg-gray-50 py-10">
    <div class="mx-auto max-w-3xl px-4">
        <div class="mb-7 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900">{{ $lockedMode === 'offline' ? 'Offline Student Admission Form' : 'Student Admission Form' }}</h1>
            <p class="mt-2 text-gray-600">Dhaka IT Institute — Let’s Build Your Dream</p>
            @if($lockedMode === 'offline')
                <p class="mx-auto mt-3 max-w-2xl rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    Complete this form for an in-person course. The office will review the application and confirm the batch and payment details.
                </p>
            @endif
        </div>

        <form action="{{ route('admission.store') }}" method="POST" class="space-y-6 rounded-2xl bg-white p-6 shadow-lg md:p-8">
            @csrf
            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                    <p class="font-semibold">Please correct the form:</p>
                    <ul class="mt-2 list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <fieldset>
                <legend class="mb-4 text-lg font-bold text-green-800">Applicant information</legend>
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.text-input name="name_bn" label="Full Name" required :value="old('name_bn')" />
                    <x-ui.text-input name="email" label="E-mail" type="email" required :value="old('email')" />
                    <x-ui.text-input name="phone" label="Phone Number" required :value="old('phone')" />
                    <x-ui.text-input name="guardian_phone" label="Guardian Phone (Optional)" :value="old('guardian_phone')" />
                    <x-ui.select name="blood_group" label="Blood Group" :selected="old('blood_group')">
                        <option value="">Select group</option>@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $group)<option>{{ $group }}</option>@endforeach
                    </x-ui.select>
                </div>
            </fieldset>

            <fieldset>
                <legend class="mb-4 text-lg font-bold text-green-800">Login credentials</legend>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Password</label>
                        <input type="password" name="password" autocomplete="new-password" value="{{ old('password') }}" class="mt-2 w-full rounded-lg border border-gray-300 focus:border-green-700 focus:ring-green-700" />
                        <p class="mt-1 text-xs text-gray-500">12+ chars with upper, lower, number and symbol. Leave blank to receive a reset link by email after approval.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Confirm Password</label>
                        <input type="password" name="password_confirmation" autocomplete="new-password" value="{{ old('password_confirmation') }}" class="mt-2 w-full rounded-lg border border-gray-300 focus:border-green-700 focus:ring-green-700" />
                    </div>
                </div>
            </fieldset>

            <fieldset class="border-t pt-6">
                <legend class="mb-4 text-lg font-bold text-green-800">Address</legend>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Current Address</label>
                    <textarea name="present_village" rows="2" placeholder="Area, road/house and district" class="w-full rounded-lg border border-gray-300 p-3 focus:border-green-700 focus:ring-green-700">{{ old('present_village') }}</textarea>
                </div>
            </fieldset>

            <fieldset class="border-t pt-6">
                <legend class="mb-4 text-lg font-bold text-green-800">Course selection</legend>
                <div class="grid gap-4 md:grid-cols-2">
                    @if($lockedMode)
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Learning Mode</label>
                            <input type="hidden" name="admission_mode" id="admission_mode" value="{{ $lockedMode }}">
                            <div class="rounded-xl border-2 border-amber-200 bg-amber-50 px-4 py-3 font-semibold text-amber-900">Offline</div>
                        </div>
                    @else
                        <x-ui.select name="admission_mode" id="admission_mode" label="Learning Mode" required
                            :options="['online' => 'Online', 'offline' => 'Offline']" :selected="old('admission_mode', $selectedMode)" />
                    @endif
                    <x-ui.select name="course_id" id="course_id" label="Course" required :selected="old('course_id')">
                        <option value="">Select course</option>
                        @foreach($courses as $course)<option value="{{ $course->id }}" data-mode="{{ $course->delivery_mode }}" @selected((string) old('course_id') === (string) $course->id)>{{ $course->name }} — {{ ucfirst($course->delivery_mode) }} — ৳{{ number_format($course->price) }}</option>@endforeach
                    </x-ui.select>
                </div>
            </fieldset>

            <button class="w-full rounded-lg bg-green-800 px-6 py-3 font-bold text-white hover:bg-black">Submit Admission Application</button>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const mode = document.getElementById('admission_mode');
    const course = document.getElementById('course_id');
    const filter = () => Array.from(course.options).forEach(option => {
        if (option.value) option.hidden = option.dataset.mode !== mode.value;
    });
    mode.addEventListener('change', () => { course.value = ''; filter(); if (typeof renderOptions === 'function') renderOptions('course_id'); });
    filter();
});
</script>
@endpush
