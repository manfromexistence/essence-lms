@extends('layouts.frontend')

@section('title', 'Student Admission - Dhaka IT Institute')

@section('content')
<section class="bg-gray-50 py-10">
    <div class="mx-auto max-w-5xl px-4">
        <div class="mb-7 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900">{{ $lockedMode === 'offline' ? 'Offline Student Admission Form' : 'Student Admission Form' }}</h1>
            <p class="mt-2 text-gray-600">Dhaka IT Institute — Let’s Build Your Dream</p>
            @if($lockedMode === 'offline')
                <p class="mx-auto mt-3 max-w-2xl rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    Complete this form for an in-person course. The office will review the application and confirm the batch and payment details.
                </p>
            @endif
        </div>

        <form action="{{ route('admission.store') }}" method="POST" class="space-y-6 rounded-2xl bg-white p-6 shadow-lg md:p-9">
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
                    <x-ui.text-input name="guardian_phone" label="Guardian Phone Number" :value="old('guardian_phone')" />
                    <x-ui.text-input name="father_name" label="Father / Husband Name" :value="old('father_name')" />
                    <x-ui.text-input name="profession" label="Profession" :value="old('profession')" />
                    <x-ui.date-picker name="dob" label="Date of Birth" :value="old('dob')" />
                    <x-ui.select name="gender" label="Gender" :selected="old('gender')">
                        <option value="">Select gender</option><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option>
                    </x-ui.select>
                    <x-ui.select name="marital_status" label="Marital Status" :selected="old('marital_status')">
                        <option value="">Select status</option><option value="single">Single</option><option value="married">Married</option>
                    </x-ui.select>
                    <x-ui.select name="religion" label="Religion" :selected="old('religion')">
                        <option value="">Select religion</option><option>Islam</option><option>Hinduism</option><option>Buddhism</option><option>Christianity</option><option>Other</option>
                    </x-ui.select>
                    <x-ui.select name="blood_group" label="Blood Group" :selected="old('blood_group')">
                        <option value="">Select group</option>@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $group)<option>{{ $group }}</option>@endforeach
                    </x-ui.select>
                    <x-ui.text-input name="admission_purpose" label="Purpose" :value="old('admission_purpose')" />
                </div>
            </fieldset>

            <fieldset class="border-t pt-6">
                <legend class="mb-4 text-lg font-bold text-green-800">Address</legend>
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Present / Mailing Address</label>
                        <textarea name="present_village" rows="4" class="w-full rounded-lg border border-gray-300 p-3 focus:border-green-700 focus:ring-green-700">{{ old('present_village') }}</textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Permanent Address</label>
                        <textarea name="permanent_village" rows="4" class="w-full rounded-lg border border-gray-300 p-3 focus:border-green-700 focus:ring-green-700">{{ old('permanent_village') }}</textarea>
                    </div>
                </div>
            </fieldset>

            <fieldset class="border-t pt-6">
                <legend class="mb-4 text-lg font-bold text-green-800">Academic qualification</legend>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-sm">
                        <thead class="bg-gray-100"><tr><th class="p-2 text-left">Examination</th><th class="p-2">Institution</th><th class="p-2">Board</th><th class="p-2">Year</th><th class="p-2">Result / Grade</th></tr></thead>
                        <tbody>
                            @foreach(['ssc' => 'SSC / Equivalent', 'hsc' => 'HSC / Equivalent', 'undergrad' => 'Undergraduate'] as $prefix => $label)
                            <tr class="border-b">
                                <td class="p-2 font-medium">{{ $label }}</td>
                                <td class="p-2"><input class="w-full rounded border-gray-300" name="{{ $prefix }}_institute" value="{{ old($prefix.'_institute') }}"></td>
                                <td class="p-2"><input class="w-full rounded border-gray-300" name="{{ $prefix }}_board" value="{{ old($prefix.'_board') }}"></td>
                                <td class="p-2"><input class="w-full rounded border-gray-300" type="number" name="{{ $prefix }}_year" value="{{ old($prefix.'_year') }}"></td>
                                <td class="p-2"><input class="w-full rounded border-gray-300" type="number" step="0.01" name="{{ $prefix }}_gpa" value="{{ old($prefix.'_gpa') }}"></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </fieldset>

            <fieldset class="border-t pt-6">
                <legend class="mb-4 text-lg font-bold text-green-800">Course selection</legend>
                <div class="grid gap-4 md:grid-cols-2">
                    @if($lockedMode)
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Class Mode</label>
                            <input type="hidden" name="admission_mode" id="admission_mode" value="{{ $lockedMode }}">
                            <div class="rounded-xl border-2 border-amber-200 bg-amber-50 px-4 py-3 font-semibold text-amber-900">Offline</div>
                        </div>
                    @else
                        <x-ui.select name="admission_mode" id="admission_mode" label="Class Mode" required
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
