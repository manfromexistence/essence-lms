@extends('layouts.admin')

@section('title', 'Add New Student')
@section('page-title', 'Add New Student')
@section('page-description', 'Register a new student in the system')

@section('content')
    <form class="max-w-5xl mx-auto" action="{{ route('dashboard.students.store') }}" method="POST"
        enctype="multipart/form-data">
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

        <!-- 1. Student Information -->
        <div class="bg-white rounded-xl shadow-md border-gray-200 border mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Student Information</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-ui.text-input name="name" label="Full Name" required max="255" placeholder="Student's full name" persist />
                <x-ui.text-input name="email" label="Email Address" type="email" placeholder="john@example.com" required persist />
                <x-ui.text-input name="phone" label="Mobile No." type="tel" placeholder="017xxxxxxxx" max="20" required persist />

                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold text-gray-700">Password</label>
                    <div class="mt-2 grid gap-4 md:grid-cols-2">
                        <input type="password" name="password" autocomplete="new-password" class="w-full rounded-lg border border-gray-300 focus:border-green-700 focus:ring-green-700" placeholder="12+ chars, upper+lower+num+symbol" />
                        <input type="password" name="password_confirmation" autocomplete="new-password" class="w-full rounded-lg border border-gray-300 focus:border-green-700 focus:ring-green-700" placeholder="Confirm password" />
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Optional. Leave blank to email the student a password-reset link instead.</p>
                </div>

                <x-ui.date-picker name="dob" label="Date of Birth (Optional)" placeholder="Select Birth Date" persist />
                <x-ui.select name="gender" label="Gender" persist>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </x-ui.select>
                <x-ui.text-input name="guardian_phone" label="Guardian Phone (Optional)" type="tel" max="20" persist />

                <div class="md:col-span-3">
                    <x-ui.image-input name="profile_image" label="Profile Photo"
                        helperText="Passport size photo recommended." persist />
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
                <x-ui.select name="admission_mode" label="Student Type" required persist
                    :options="['online' => 'Online Student', 'offline' => 'Offline Student']"
                    :selected="old('admission_mode', $defaultMode)" />

                <x-ui.select name="course_id" label="Select Course" persist>
                    <option value="">Select Course</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" data-mode="{{ $course->delivery_mode }}" @selected((string) old('course_id') === (string) $course->id)>
                            {{ $course->name }} — {{ ucfirst($course->delivery_mode) }}
                        </option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="batch_id" label="Batch (Optional)" persist>
                    <option value="">Assign later</option>
                    <!-- Populated by JS -->
                </x-ui.select>

                <x-ui.select name="class_days" label="Class Days (Optional)" persist>
                    <option value="">Select Days</option>
                    <!-- Populated by JS -->
                </x-ui.select>

                <x-ui.select name="class_time" label="Class Time (Optional)" persist>
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
                <x-ui.text-input name="total_amount" label="Total Amount" type="number" placeholder="0.00" persist />
                <x-ui.text-input name="paid_amount" label="Paid Amount" type="number" placeholder="0.00" persist />
                <x-ui.text-input name="due_amount" label="Due Amount" type="number" placeholder="0.00" readonly persist />
                
                <x-ui.select name="payment_method" label="Payment Method" persist>
                    <option value="">Select Method</option>
                    <option value="Cash">Cash</option>
                    <option value="Bkash">Bkash</option>
                    <option value="Nagad">Nagad</option>
                    <option value="Rocket">Rocket</option>
                    <option value="Bank Transfer">Bank Transfer</option>
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
                Register Student
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

            // Store loaded batches to parse schedule locally
            let currentBatches = [];

            // Old values from server (for restoration after validation failure)
            const oldValues = {
                course_id: '{{ old("course_id", "") }}',
                batch_id: '{{ old("batch_id", "") }}',
                class_days: '{{ old("class_days", "") }}',
                class_time: '{{ old("class_time", "") }}',
            };

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

                resetSelect(els.batch, 'Assign later');
                resetSelect(els.day, 'Select Days');
                resetSelect(els.time, 'Select Time');
                currentBatches = [];

                updateUI(els.course);
            }

            els.mode.addEventListener('change', () => filterCourses(true));

            // 2. Course Change -> Fetch Batches
            els.course.addEventListener('change', function() {
                const courseId = this.value;
                resetSelect(els.batch, 'Assign later');
                resetSelect(els.day, 'Select Days');
                resetSelect(els.time, 'Select Time');
                currentBatches = [];

                if (courseId) {
                    const baseUrl = "{{ url('/dashboard/students') }}";
                    fetch(`${baseUrl}/get-batches/${courseId}`)
                        .then(res => res.json())
                        .then(data => {
                            currentBatches = data;
                            populateSelect(els.batch, data, oldValues.batch_id);
                        })
                        .catch(err => console.error(err));
                }
            });
            
            // 3. Batch Change -> Populate Day/Time
            els.batch.addEventListener('change', function() {
                const batchId = this.value;
                resetSelect(els.day, 'Select Days');
                resetSelect(els.time, 'Select Time');
                
                if (batchId) {
                    const batch = currentBatches.find(b => b.id == batchId);
                    if (batch && batch.schedule) {
                        const parts = batch.schedule.split(':');
                        const day = parts[0] ? parts[0].trim() : '';
                        const time = parts[1] ? parts[1].trim() : '';
                        
                        // Populate and select
                        if(day) {
                             const opt = new Option(day, day, true, true);
                             els.day.add(opt);
                             updateUI(els.day);
                        }
                        if(time) {
                             const opt = new Option(time, time, true, true);
                             els.time.add(opt);
                             updateUI(els.time);
                        }
                    }
                }
            });

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

            // Helpers
            function resetSelect(el, placeholder) {
                el.innerHTML = `<option value="">${placeholder}</option>`;
                updateUI(el);
            }

            function populateSelect(el, items, oldValue = null) {
                items.forEach(item => {
                    el.add(new Option(item.name, item.id));
                });
                
                // Check for old value from server first, then localStorage
                const savedValue = oldValue || localStorage.getItem(`select_persist_${window.location.pathname}_${el.id}`);
                
                if (savedValue) {
                    // Check if the saved value exists in the new options
                    const exists = Array.from(el.options).some(o => o.value == savedValue);
                    if (exists) {
                        el.value = savedValue;
                    }
                }
                
                updateUI(el);

                // Trigger change to continue the chain
                if (el.value) {
                    el.dispatchEvent(new Event('change'));
                }
            }

            // Update Custom UI (x-ui.select)
            function updateUI(nativeSelect) {
                const name = nativeSelect.id;
                const list = document.getElementById('select-list-' + name);
                const textSpan = document.getElementById('select-text-' + name);
                
                if(list && textSpan) {
                    list.innerHTML = '';
                    let hasSelection = false;
                    
                    Array.from(nativeSelect.options).forEach(option => {
                        if (option.hidden || (option.value === "" && option.disabled)) return;

                        const li = document.createElement('li');
                        li.className = 'px-4 py-2 hover:bg-emerald-50 cursor-pointer text-sm text-gray-700 hover:text-bd-green transition-colors';
                        li.textContent = option.textContent;
                        // Use window.selectOption if available, else fallback logic
                        li.onclick = () => {
                            if (typeof window.selectOption === 'function') {
                                window.selectOption(name, option.value, option.textContent);
                            } else {
                                // Fallback if component script hasn't loaded (rare)
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
                         if(nativeSelect.value == "") {
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

            if(totalInput && paidInput && dueInput) {
                totalInput.addEventListener('input', calculateDue);
                paidInput.addEventListener('input', calculateDue);
            }

            // Initialize course visibility, then restore the course/batch chain.
            filterCourses(false);
            if (oldValues.course_id && Array.from(els.course.options).some(option => option.value == oldValues.course_id && !option.hidden)) {
                els.course.value = oldValues.course_id;
                updateUI(els.course);
            }
            if (els.course.value) {
                els.course.dispatchEvent(new Event('change'));
            }
        });
    </script>
    @endpush
@endsection
