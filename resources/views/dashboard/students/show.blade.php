@extends('layouts.admin')

@section('title', 'Student Profile')
@section('page-title', 'Student Profile')
@section('page-description', 'View student details and performance')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar Profile Card -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6 text-center border-b border-gray-100">
                    <div class="relative inline-block">
                        @if($student->profile_image)
                            <img class="w-24 h-24 rounded-full mx-auto object-cover border-4 border-white shadow-lg"
                                src="{{ Str::startsWith($student->profile_image, 'http') ? $student->profile_image : asset('storage/' . $student->profile_image) }}"
                                alt="{{ $student->user->name }}">
                        @else
                            <div
                                class="w-24 h-24 bg-bd-green rounded-full flex items-center justify-center text-white text-3xl font-bold mx-auto border-4 border-white shadow-lg">
                                {{ strtoupper(substr($student->user->name ?? 'S', 0, 1)) }}
                            </div>
                        @endif
                        <span class="absolute bottom-1 right-1 w-5 h-5 bg-green-500 border-4 border-white rounded-full"></span>
                    </div>
                    <h2 class="mt-4 text-xl font-bold text-gray-900">{{ $student->user->name ?? 'N/A' }}</h2>
                    <p class="text-sm text-gray-500">{{ $student->name_bn ?? '' }}</p>
                    <p class="text-sm text-gray-500 font-medium mt-1">{{ $student->batch->name ?? 'Unassigned' }}</p>
                    
                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        @if($student->registration_no)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Reg: {{ $student->registration_no }}
                        </span>
                        @endif
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            ID: {{ str_pad($student->id, 5, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>
                </div>
                <div class="p-6 bg-gray-50">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Contact Info</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <span class="text-gray-400 w-6"><i class="fas fa-envelope"></i></span>
                            <span class="text-gray-700 text-sm break-all">{{ $student->user->email ?? 'N/A' }}</span>
                        </li>
                        <li class="flex items-center">
                            <span class="text-gray-400 w-6"><i class="fas fa-phone"></i></span>
                            <span class="text-gray-700 text-sm">{{ $student->phone ?? 'N/A' }}</span>
                        </li>
                        @if($student->present_dist)
                        <li class="flex items-start">
                            <span class="text-gray-400 w-6"><i class="fas fa-map-marker-alt"></i></span>
                            <span class="text-gray-700 text-sm">
                                {{ $student->present_village }}, {{ $student->present_dist }}
                            </span>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>

            <!-- Payment Status Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden p-6">
                 <h4 class="text-sm font-medium text-gray-900 mb-4 border-b pb-2">Payment Status</h4>
                 <div class="space-y-3">
                     <div class="flex justify-between items-center border-b border-dashed pb-2">
                         <span class="text-sm text-gray-500">Total Amount</span>
                         <span class="text-sm font-bold text-gray-800">{{ number_format($student->total_amount, 2) }}</span>
                     </div>
                     <div class="flex justify-between items-center border-b border-dashed pb-2">
                         <span class="text-sm text-gray-500">Paid Amount</span>
                         <span class="text-sm font-bold text-green-600">{{ number_format($student->paid_amount, 2) }}</span>
                     </div>
                     <div class="flex justify-between items-center border-b border-dashed pb-2">
                         <span class="text-sm text-gray-500">Due Amount</span>
                         <span class="text-sm font-bold text-red-600">{{ number_format($student->due_amount, 2) }}</span>
                     </div>
                     <div class="flex justify-between items-center pt-1">
                         <span class="text-sm text-gray-500">Method</span>
                         <span class="text-sm font-medium text-gray-800">{{ $student->payment_method ?? 'N/A' }}</span>
                     </div>
                 </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden p-6">
                <h4 class="text-sm font-medium text-gray-900 mb-4">Quick Actions</h4>
                <div class="space-y-3">
                    <a href="{{ route('dashboard.students.edit', $student) }}"
                        class="flex items-center w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        Edit Profile
                    </a>
                    <form action="{{ route('dashboard.students.destroy', $student) }}" method="POST"
                        onsubmit="return confirmDelete(this, 'Are you sure you want to remove this student? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="flex items-center w-full px-4 py-2 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                            Delete Student
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content (Details tabs) -->
        <div class="lg:col-span-2 space-y-6">
             <!-- Identity & Course -->
             <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Personal & Course Details</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Registration No.</span>
                        <span class="block text-sm font-medium text-gray-900">{{ $student->registration_no ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Course Name</span>
                        <span class="block text-sm font-medium text-gray-900">{{ $student->course_name ?? ($student->batch?->course?->name ?? 'N/A') }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Student Type</span>
                        <span class="block text-sm font-medium text-gray-900">{{ ucfirst($student->admission_mode ?? 'offline') }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Admission Status</span>
                        @php
                            $admissionStatus = $student->admission_status ?? ($student->batch_id ? 'approved' : 'pending');
                            $statusBadge = [
                                'approved' => ['bg-green-100 text-green-800', 'Admitted'],
                                'rejected' => ['bg-red-100 text-red-800', 'Rejected'],
                                'pending' => ['bg-yellow-100 text-yellow-800', 'Pending'],
                            ][$admissionStatus] ?? ['bg-gray-100 text-gray-800', ucfirst($admissionStatus)];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Date of Birth</span>
                        <span class="block text-sm font-medium text-gray-900">{{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d M, Y') : 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Gender</span>
                        <span class="block text-sm font-medium text-gray-900">{{ $student->gender ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Blood Group</span>
                        <span class="block text-sm font-medium text-gray-900">{{ $student->blood_group ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Guardian Phone</span>
                        <span class="block text-sm font-medium text-gray-900">{{ $student->guardian_phone ?? 'N/A' }}</span>
                    </div>
                </div>
             </div>

             <!-- Family Info (only when present) -->
             @if($student->father_name || $student->mother_name || $student->guardian_name)
             <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-md font-semibold text-gray-800">Family Information</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-2">Father</h4>
                        <div class="space-y-1">
                            <p class="text-sm"><span class="text-gray-500">Name:</span> {{ $student->father_name ?? 'N/A' }}</p>
                            <p class="text-sm"><span class="text-gray-500">Occupation:</span> {{ $student->father_occupation ?? 'N/A' }}</p>
                            <p class="text-sm"><span class="text-gray-500">Mobile:</span> {{ $student->father_phone ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-2">Mother</h4>
                        <div class="space-y-1">
                            <p class="text-sm"><span class="text-gray-500">Name:</span> {{ $student->mother_name ?? 'N/A' }}</p>
                            <p class="text-sm"><span class="text-gray-500">Occupation:</span> {{ $student->mother_occupation ?? 'N/A' }}</p>
                            <p class="text-sm"><span class="text-gray-500">Mobile:</span> {{ $student->mother_phone ?? 'N/A' }}</p>
                        </div>
                    </div>
                    @if($student->guardian_name || $student->guardian_phone)
                    <div class="md:col-span-2 border-t pt-4">
                        <h4 class="text-sm font-bold text-gray-700 mb-2">Guardian</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                             <p class="text-sm"><span class="text-gray-500">Name:</span> {{ $student->guardian_name ?? 'N/A' }}</p>
                             <p class="text-sm"><span class="text-gray-500">Mobile:</span> {{ $student->guardian_phone ?? 'N/A' }}</p>
                        </div>
                    </div>
                    @endif
                </div>
             </div>
             @endif

             <!-- Addresses -->
             <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-md font-semibold text-gray-800">Addresses</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-1">Present Address</h4>
                        <div class="space-y-2 text-sm text-gray-600">
                            <p><span class="w-20 inline-block font-medium">Holding:</span> {{ $student->present_holding ?? '-' }}</p>
                            <p><span class="w-20 inline-block font-medium">Vill/Area:</span> {{ $student->present_village ?? '-' }}</p>
                            <p><span class="w-20 inline-block font-medium">PO:</span> {{ $student->present_po ?? '-' }}</p>
                            <p><span class="w-20 inline-block font-medium">PS:</span> {{ $student->present_ps ?? '-' }}</p>
                            <p><span class="w-20 inline-block font-medium">District:</span> {{ $student->present_dist ?? '-' }}</p>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-1">Permanent Address</h4>
                        <div class="space-y-2 text-sm text-gray-600">
                             <p><span class="w-20 inline-block font-medium">Holding:</span> {{ $student->permanent_holding ?? '-' }}</p>
                            <p><span class="w-20 inline-block font-medium">Vill/Area:</span> {{ $student->permanent_village ?? '-' }}</p>
                            <p><span class="w-20 inline-block font-medium">PO:</span> {{ $student->permanent_po ?? '-' }}</p>
                            <p><span class="w-20 inline-block font-medium">PS:</span> {{ $student->permanent_ps ?? '-' }}</p>
                            <p><span class="w-20 inline-block font-medium">District:</span> {{ $student->permanent_dist ?? '-' }}</p>
                        </div>
                    </div>
                </div>
             </div>

             <!-- Academic Info (only when present) -->
             @if($student->ssc_institute || $student->hsc_institute || $student->undergrad_institute)
             <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-md font-semibold text-gray-800">Academic Qualification</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Institute</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Board</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result/GPA</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">SSC</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->ssc_institute ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->ssc_board ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->ssc_year ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->ssc_gpa ? $student->ssc_gpa . ' (' . $student->ssc_group . ')' : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">HSC</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->hsc_institute ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->hsc_board ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->hsc_year ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->hsc_gpa ? $student->hsc_gpa . ' (' . $student->hsc_group . ')' : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Undergrad</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->undergrad_institute ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->undergrad_board ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->undergrad_year ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->undergrad_gpa ? $student->undergrad_gpa . ' (' . $student->undergrad_department . ')' : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
             </div>
             @endif

        </div>
    </div>
@endsection