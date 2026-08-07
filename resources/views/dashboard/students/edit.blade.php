@extends('layouts.admin')

@section('title', 'Edit Student')
@section('page-title', 'Edit Student')
@section('page-description', 'Update student information')

@section('content')
    <form class="max-w-5xl mx-auto" action="{{ route('dashboard.students.update', $student->id) }}" method="POST"
        enctype="multipart/form-data">
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

        <!-- 1. Student Information -->
        <div class="bg-white rounded-xl shadow-md border-gray-200 border mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Student Information</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-ui.text-input name="name" label="Full Name" :value="$student->user->name ?? ''" required max="255" placeholder="Student's full name" />
                <x-ui.text-input name="email" label="Email Address" type="email" :value="$student->user->email ?? ''" required placeholder="john@example.com" />
                <x-ui.text-input name="phone" label="Mobile No." type="tel" :value="$student->phone" max="20" placeholder="017xxxxxxxx" required />

                <x-ui.date-picker name="dob" label="Date of Birth (Optional)" :value="$student->dob" placeholder="Select Birth Date" />
                <x-ui.select name="gender" label="Gender">
                    <option value="">Select Gender</option>
                    <option value="Male" @selected((string) old('gender', $student->gender) === 'Male')>Male</option>
                    <option value="Female" @selected((string) old('gender', $student->gender) === 'Female')>Female</option>
                    <option value="Other" @selected((string) old('gender', $student->gender) === 'Other')>Other</option>
                </x-ui.select>
                <x-ui.text-input name="guardian_phone" label="Guardian Phone (Optional)" type="tel" :value="$student->guardian_phone" max="20" />

                <div class="md:col-span-3">
                    <x-ui.image-input name="profile_image" label="Profile Photo" :value="$student->profile_image"
                        helperText="Passport size photo recommended." />
                </div>
            </div>
        </div>

        <!-- 2. Course & Batch Information -->
        <div class="bg-white rounded-xl shadow-md border-gray-200 border mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Course & Admission</h3>
                <p class="mt-1 text-sm text-gray-500">Choose online or offline first; only matching courses will be available.</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-ui.select name="admission_mode" label="Student Type" required
                    :options="['online' => 'Online Student', 'offline' => 'Offline Student']"
                    :selected="old('admission_mode', $defaultMode)" />

                <x-ui.select name="course_id" label="Select Course">
                    <option value="">Select Course</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" data-mode="{{ $course->delivery_mode }}"
                            @selected((string) old('course_id', $student->batch->course_id ?? '') === (string) $course->id)>
                            {{ $course->name }} — {{ ucfirst($course->delivery_mode) }}
                        </option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="batch_id" label="Batch (Optional)">
                    <option value="">Assign later</option>
                    @foreach($batches as $batch)
                        <option value="{{ $batch->id }}" data-course="{{ $batch->course_id }}" data-schedule="{{ $batch->schedule }}"
                            @selected((string) old('batch_id', $student->batch_id ?? '') === (string) $batch->id)>
                            {{ $batch->name }}@if($batch->schedule) — {{ $batch->schedule }}@endif
                        </option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="class_days" label="Class Days (Optional)">
                    <option value="">Select Days</option>
                    <!-- Populated by JS -->
                </x-ui.select>

                <x-ui.select name="class_time" label="Class Time (Optional)">
                    <option value="">Select Time</option>
                    <!-- Populated by JS -->
                </x-ui.select>
            </div>
        </div>

        <!-- 3. Payment Information -->
        <div class="bg-white rounded-xl shadow-md border-gray-200 border mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Payment Information</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-6">
                <x-ui.text-input name="total_amount" label="Total Amount" type="number" :value="$student->total_amount" placeholder="0.00" />
                <x-ui.text-input name="paid_amount" label="Paid Amount" type="number" :value="$student->paid_amount" placeholder="0.00" />
                <x-ui.text-input name="due_amount" label="Due Amount" type="number" :value="$student->due_amount" placeholder="0.00" readonly />

                <x-ui.select name="payment_method" label="Payment Method">
                    <option value="">Select Method</option>
                    <option value="Cash" @selected((string) old('payment_method', $student->payment_method) === 'Cash')>Cash</option>
                    <option value="Bkash" @selected((string) old('payment_method', $student->payment_method) === 'Bkash')>Bkash</option>
                    <option value="Nagad" @selected((string) old('payment_method', $student->payment_method) === 'Nagad')>Nagad</option>
                    <option value="Rocket" @selected((string) old('payment_method', $student->payment_method) === 'Rocket')>Rocket</option>
                    <option value="Bank Transfer" @selected((string) old('payment_method', $student->payment_method) === 'Bank Transfer')>Bank Transfer</option>
                </x-ui.select>
            </div>
        </div>

        <!-- Global Actions -->
        <div class="flex justify-end space-x-3 mb-10">
            <a href="{{ route('dashboard.students.index') }}"
                class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                Cancel
            </a>
            <button type="submit"
                class="px-6 py-2.5 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium">
                Update Student
            </button>
        </div>

    </form>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const els = {
                mode: document.getElementById('admission_mode'),
                course: document.getElementById('course_id'),
                batch: document.getElementById('batch_id'),
                day: document.getElementById('class_days'),
                time: document.getElementById('class_time'),
            };

            // Store the student's current values for initial sync
            const currentBatchId = '{{ old("batch_id", $student->batch_id ?? "") }}';
            const currentCourseId = '{{ old("course_id", $student->batch->course_id ?? "") }}';

            // 1. Online/offline mode controls the available institute courses.
            function filterCourses(resetSelection = false) {
                Array.from(els.course.options).forEach(option => {
                    if (!option.value) return;
                    option.hidden = option.dataset.mode !== els.mode.value;
                    option.disabled = option.hidden;
                });

                const selectedOption = els.course.options[els.course.selectedIndex];
                if (resetSelection || (selectedOption && selectedOption.hidden)) {
                    els.course.value = '';
                }

                // Filter batches to those matching the current course.
                filterBatches();
                updateUI(els.course);
            }

            function filterBatches() {
                const courseId = els.course.value;
                const selectedBatchId = els.batch.value || currentBatchId;

                let visible = false;
                Array.from(els.batch.options).forEach(option => {
                    if (!option.value) return;
                    const matchesCourse = option.dataset.course === courseId;
                    option.hidden = !matchesCourse;
                    option.disabled = option.hidden;
                    if (matchesCourse) visible = true;
                });

                // If the selected batch no longer matches, reset it.
                const currentOption = Array.from(els.batch.options).find(o => o.value === selectedBatchId);
                if (selectedBatchId && (!currentOption || currentOption.hidden)) {
                    els.batch.value = '';
                    resetSchedule();
                }

                // Restore the current batch if it matches the course.
                if (courseId && currentBatchId && !els.batch.value) {
                    const match = Array.from(els.batch.options).find(o => o.value === currentBatchId && !o.hidden);
                    if (match) els.batch.value = currentBatchId;
                }

                updateUI(els.batch);
                els.batch.dispatchEvent(new Event('change'));
            }

            els.mode.addEventListener('change', () => filterCourses(true));

            // 2. Course Change -> Filter batches
            els.course.addEventListener('change', function() {
                resetSchedule();
                filterBatches();
            });

            // 3. Batch Change -> Populate Day/Time
            els.batch.addEventListener('change', function() {
                resetSchedule();

                const batchId = this.value;
                if (batchId) {
                    const option = Array.from(this.options).find(o => o.value === batchId);
                    const schedule = option ? option.dataset.schedule : '';
                    if (schedule) {
                        const parts = schedule.split(':');
                        const day = parts[0] ? parts[0].trim() : '';
                        const time = parts[1] ? parts[1].trim() : '';

                        if (day) {
                            const opt = new Option(day, day, true, true);
                            els.day.add(opt);
                            updateUI(els.day);
                        }
                        if (time) {
                            const opt = new Option(time, time, true, true);
                            els.time.add(opt);
                            updateUI(els.time);
                        }
                    }
                }
            });

            function resetSchedule() {
                els.day.innerHTML = '<option value="">Select Days</option>';
                els.time.innerHTML = '<option value="">Select Time</option>';
                updateUI(els.day);
                updateUI(els.time);
            }

            // Update Custom UI (x-ui.select)
            function updateUI(nativeSelect) {
                const name = nativeSelect.id;
                const list = document.getElementById('select-list-' + name);
                const textSpan = document.getElementById('select-text-' + name);

                if (list && textSpan) {
                    list.innerHTML = '';
                    let hasSelection = false;

                    Array.from(nativeSelect.options).forEach(option => {
                        if (option.hidden || (option.value === "" && option.disabled)) return;

                        const li = document.createElement('li');
                        li.className = 'px-4 py-2 hover:bg-emerald-50 cursor-pointer text-sm text-gray-700 hover:text-bd-green transition-colors';
                        li.textContent = option.textContent;
                        li.onclick = () => {
                            if (typeof window.selectOption === 'function') {
                                window.selectOption(name, option.value, option.textContent);
                            } else {
                                nativeSelect.value = option.value;
                                nativeSelect.dispatchEvent(new Event('change'));
                                textSpan.textContent = option.textContent;
                                textSpan.classList.remove('text-gray-500');
                                textSpan.classList.add('text-gray-900');
                                document.getElementById('select-dropdown-' + name).classList.add('hidden');
                            }
                        };
                        list.appendChild(li);

                        if (option.selected && option.value !== "") {
                            textSpan.textContent = option.textContent;
                            textSpan.classList.remove('text-gray-500');
                            textSpan.classList.add('text-gray-900');
                            hasSelection = true;
                        }
                    });

                    if (!hasSelection) {
                        textSpan.textContent = nativeSelect.options[0]?.textContent || 'Select...';
                        if (nativeSelect.value == "") {
                            textSpan.classList.remove('text-gray-900');
                            textSpan.classList.add('text-gray-500');
                        }
                    }
                }
            }

            // Payment Calculation
            const totalInput = document.querySelector('input[name="total_amount"]');
            const paidInput = document.querySelector('input[name="paid_amount"]');
            const dueInput = document.querySelector('input[name="due_amount"]');

            function calculateDue() {
                const total = parseFloat(totalInput.value) || 0;
                const paid = parseFloat(paidInput.value) || 0;
                dueInput.value = (total - paid).toFixed(2);
            }

            if (totalInput && paidInput && dueInput) {
                totalInput.addEventListener('input', calculateDue);
                paidInput.addEventListener('input', calculateDue);
            }

            // Initialize: filter courses by mode, then restore course/batch chain.
            filterCourses(false);
            if (currentCourseId && Array.from(els.course.options).some(option => option.value == currentCourseId && !option.hidden)) {
                els.course.value = currentCourseId;
                updateUI(els.course);
            }
            if (els.course.value) {
                els.course.dispatchEvent(new Event('change'));
            }
        });
    </script>
    @endpush
@endsection
