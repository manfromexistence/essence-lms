@extends('layouts.admin')

@section('title', 'Add New Student')
@section('page-title', 'Add New Student')
@section('page-description', 'Register a new student in the system')

@section('content')
    <form class="max-w-7xl mx-auto" action="{{ route('dashboard.students.store') }}" method="POST"
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

        <!-- 1. Identity Information -->
        <div class="bg-white rounded-xl shadow-md border-gray-200 border mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Identity Information</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-ui.text-input name="name" label="Full Name (English)" required max="255" persist />
                <x-ui.text-input name="name_bn" label="Full Name (Bangla)" max="255" persist />
                
                <!-- Registration No Auto-Generated -->
                 <!-- <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID No.</label>
                    <input type="text" disabled value="Auto-generated" 
                        class="min-h-12 px-4 w-full rounded-lg border-gray-300 bg-gray-100 text-gray-500 shadow-sm focus:border-bd-green focus:ring-bd-green">
                    <p class="text-xs text-gray-500 mt-1">ID will be generated after saving (YYYY-BATCH-SEQ)</p>
                </div> -->

                <x-ui.text-input name="email" label="Email Address" type="email" placeholder="john@example.com" required persist />
                <x-ui.text-input name="phone" label="Mobile No." type="tel" placeholder="017xxxxxxxx" max="20" persist />
                <x-ui.text-input name="profession" label="Profession" max="255" persist />
                <x-ui.date-picker name="dob" label="Date of Birth" placeholder="Select Birth Date" persist />

                <x-ui.select name="gender" label="Gender" persist>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </x-ui.select>
                <x-ui.select name="marital_status" label="Marital Status" persist>
                    <option value="">Select Status</option>
                    <option value="single">Single</option>
                    <option value="married">Married</option>
                </x-ui.select>

                <x-ui.select name="blood_group" label="Blood Group" persist>
                    <option value="">Select Group</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                </x-ui.select>

                <x-ui.select name="religion" label="Religion" persist>
                    <option value="">Select Religion</option>
                    <option value="Islam">Islam</option>
                    <option value="Hinduism">Hinduism</option>
                    <option value="Buddhism">Buddhism</option>
                    <option value="Christianity">Christianity</option>
                    <option value="Other">Other</option>
                </x-ui.select>
                <x-ui.text-input name="admission_purpose" label="Purpose" max="1000" persist />

                <div class="md:col-span-3">
                    <x-ui.image-input name="profile_image" label="Profile Photo"
                        helperText="Passport size photo recommended." persist />
                </div>
            </div>
        </div>

        <!-- 2. Family Information -->
        <div class="bg-white rounded-xl shadow-md border-gray-200 border mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Family Information</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Father -->
                <x-ui.text-input name="father_name" label="Father's Name" max="255" persist />
                <x-ui.text-input name="father_occupation" label="Father's Occupation" max="255" persist />
                <x-ui.text-input name="father_phone" label="Father's Mobile No." max="20" persist />

                <!-- Mother -->
                <x-ui.text-input name="mother_name" label="Mother's Name" max="255" persist />
                <x-ui.text-input name="mother_occupation" label="Mother's Occupation" max="255" persist />
                <x-ui.text-input name="mother_phone" label="Mother's Mobile No." max="20" persist />

                <!-- Guardian -->
                <x-ui.text-input name="guardian_name" label="Guardian's Name" max="255" persist />
                <x-ui.text-input name="guardian_phone" label="Guardian's Mobile No." max="20" persist />
                <div class="hidden md:block"></div> <!-- Spacer -->
            </div>
        </div>

        <!-- 3. Address Information -->
        <div class="bg-white rounded-xl shadow-md border-gray-200 border mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Address Information</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Present Address -->
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-700 border-b pb-2">Present Address</h4>
                    <x-ui.text-input name="present_holding" label="Holding No." persist />
                    <x-ui.text-input name="present_village" label="Mohila/Vill" persist />
                    <x-ui.text-input name="present_po" label="Post Office (PO)" persist />
                    <x-ui.text-input name="present_ps" label="Police Station (PS)" persist />
                    <x-ui.text-input name="present_dist" label="District" persist />
                </div>

                <!-- Permanent Address -->
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-700 border-b pb-2">Permanent Address</h4>
                    <x-ui.text-input name="permanent_holding" label="Holding No." persist />
                    <x-ui.text-input name="permanent_village" label="Mohila/Vill" persist />
                    <x-ui.text-input name="permanent_po" label="Post Office (PO)" persist />
                    <x-ui.text-input name="permanent_ps" label="Police Station (PS)" persist />
                    <x-ui.text-input name="permanent_dist" label="District" persist />
                </div>
            </div>
        </div>

        <!-- 4. Academic Qualification -->
        <div class="bg-white rounded-xl shadow-md border-gray-200 border mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Academic Qualification</h3>
            </div>
            <div class="p-6 space-y-6">
                <!-- SSC -->
                <div>
                    <h4 class="font-medium text-gray-700 mb-3">SSC / Equivalent</h4>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <x-ui.text-input name="ssc_institute" label="Name of Institute" class="md:col-span-1" persist />
                        <x-ui.select name="ssc_board" label="Board" persist>
                            <option value="">Select Board</option>
                            <option value="Dhaka">Dhaka</option>
                            <option value="Rajshahi">Rajshahi</option>
                            <option value="Comilla">Comilla</option>
                            <option value="Jessore">Jessore</option>
                            <option value="Chittagong">Chittagong</option>
                            <option value="Barisal">Barisal</option>
                            <option value="Sylhet">Sylhet</option>
                            <option value="Dinajpur">Dinajpur</option>
                            <option value="Mymensingh">Mymensingh</option>
                            <option value="Madrasah">Madrasah</option>
                            <option value="Technical">Technical</option>
                            <option value="Brou">BOU</option>
                            <option value="Other">Other</option>
                        </x-ui.select>
                        <x-ui.text-input name="ssc_year" label="Passing Year" type="number" placeholder="YYYY" min="1990" max="{{ date('Y') }}" persist />
                        <x-ui.text-input name="ssc_gpa" label="GPA" type="number" step="0.01" min="1.00" max="5.00" placeholder="5.00" persist />
                        <x-ui.select name="ssc_group" label="Group" persist>
                            <option value="">Select Group</option>
                            <option value="Science">Science</option>
                            <option value="Business Studies">Business Studies</option>
                            <option value="Humanities">Humanities</option>
                        </x-ui.select>
                    </div>
                </div>

                <!-- HSC -->
                <div>
                    <h4 class="font-medium text-gray-700 mb-3">HSC / Equivalent</h4>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <x-ui.text-input name="hsc_institute" label="Name of Institute" class="md:col-span-1" persist />
                        <x-ui.select name="hsc_board" label="Board" persist>
                            <option value="">Select Board</option>
                            <option value="Dhaka">Dhaka</option>
                            <option value="Rajshahi">Rajshahi</option>
                            <option value="Comilla">Comilla</option>
                            <option value="Jessore">Jessore</option>
                            <option value="Chittagong">Chittagong</option>
                            <option value="Barisal">Barisal</option>
                            <option value="Sylhet">Sylhet</option>
                            <option value="Dinajpur">Dinajpur</option>
                            <option value="Mymensingh">Mymensingh</option>
                            <option value="Madrasah">Madrasah</option>
                            <option value="Technical">Technical</option>
                            <option value="Brou">BOU</option>
                           <option value="Other">Other</option>
                        </x-ui.select>
                        <x-ui.text-input name="hsc_year" label="Passing Year" type="number" placeholder="YYYY" min="1990" max="{{ date('Y') }}" persist />
                        <x-ui.text-input name="hsc_gpa" label="GPA" type="number" step="0.01" min="1.00" max="5.00" placeholder="5.00" persist />
                        <x-ui.select name="hsc_group" label="Group" persist>
                            <option value="">Select Group</option>
                            <option value="Science">Science</option>
                            <option value="Business Studies">Business Studies</option>
                            <option value="Humanities">Humanities</option>
                        </x-ui.select>
                    </div>
                </div>

                <!-- Undergraduate -->
                <div>
                    <h4 class="font-medium text-gray-700 mb-3">Under Graduate / Equivalent</h4>
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <x-ui.text-input name="undergrad_institute" label="Name of Institute" class="md:col-span-1" persist />
                        <x-ui.text-input name="undergrad_board" label="Board/University" persist />
                        <x-ui.text-input name="undergrad_year" label="Passing Year" type="number" placeholder="YYYY" min="1990" max="{{ date('Y') }}" persist />
                        <x-ui.text-input name="undergrad_gpa" label="CGPA/Class" type="number" step="0.01" min="0.00" max="4.00" placeholder="4.00" persist />
                        <x-ui.text-input name="undergrad_group" label="Group/Unit" placeholder="e.g. A Unit" persist />
                        <x-ui.text-input name="undergrad_department" label="Department" placeholder="e.g. CSE" persist />
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Course & Batch Information -->
        <div class="bg-white rounded-xl shadow-md border-gray-200 border mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Course & Admission Information</h3>
                <p class="mt-1 text-sm text-gray-500">Choose online or offline first; only matching courses will be available.</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-ui.select name="admission_mode" label="Student Type / Class Mode" required persist
                    :options="['online' => 'Online Student', 'offline' => 'Offline Student']"
                    :selected="old('admission_mode', $defaultMode)" />
                <!-- Class -->
                <x-ui.select name="class" label="School Class (Optional)" persist>
                    <option value="">Not applicable</option>
                    @foreach($classes as $c)
                        <option value="{{ $c }}" @selected((string) old('class') === (string) $c)>Class {{ $c }}</option>
                    @endforeach
                </x-ui.select>

                <!-- Course -->
                <x-ui.select name="course_id" label="Select Course" persist>
                    <option value="">Select Course</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" data-mode="{{ $course->delivery_mode }}" @selected((string) old('course_id') === (string) $course->id)>
                            {{ $course->name }} — {{ ucfirst($course->delivery_mode) }}
                        </option>
                    @endforeach
                </x-ui.select>

                <!-- Batch -->
                <x-ui.select name="batch_id" label="Batch (Optional)" persist>
                    <option value="">Assign later</option>
                    <!-- Populated by JS -->
                </x-ui.select>

                <!-- Class Day -->
                <x-ui.select name="class_days" label="Class Days" persist>
                    <option value="">Select Days</option>
                    <!-- Populated by JS -->
                </x-ui.select>

                <!-- Class Time -->
                <x-ui.select name="class_time" label="Class Time" persist>
                    <option value="">Select Time</option>
                    <!-- Populated by JS -->
                </x-ui.select>
            </div>
        </div>

        <!-- 6. Payment Information -->
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
                class: document.getElementById('class'),
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
                class: '{{ old("class", "") }}',
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

                resetSelect(els.batch, 'Select Batch');
                resetSelect(els.day, 'Select Days');
                resetSelect(els.time, 'Select Time');
                currentBatches = [];

                updateUI(els.course);
            }

            els.mode.addEventListener('change', () => filterCourses(true));

            // 2. Course Change -> Fetch Batches
            els.course.addEventListener('change', function() {
                const courseId = this.value;
                resetSelect(els.batch, 'Select Batch');
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
