@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'ড্যাশবোর্ড')
@section('page-description', 'Welcome back, ' . Auth::user()->name . '!')

@push('styles')
<style>
    .chart-container {
        position: relative;
        height: 280px;
        width: 100%;
    }
    .chart-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #6b7280;
    }
    .chart-loading .spinner {
        animation: spin 1s linear infinite;
        width: 24px;
        height: 24px;
        border: 2px solid #e5e7eb;
        border-top-color: #3d59f9;
        border-radius: 50%;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .chart-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #9ca3af;
        text-align: center;
        padding: 1rem;
    }
    .chart-empty svg {
        width: 48px;
        height: 48px;
        margin-bottom: 8px;
    }
    .chart-empty .font-medium {
        font-weight: 500;
        color: #6b7280;
    }
    .chart-empty .text-xs {
        font-size: 0.75rem;
        color: #9ca3af;
    }
</style>
@endpush

@section('content')
@if($role === 'super-admin')
    <!-- Admin Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <a href="{{ route('dashboard.students.index') }}" class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 hover:shadow-lg hover:-translate-y-0.5 transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">মোট শিক্ষার্থী</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2 group-hover:text-blue-600 transition-colors">{{ number_format($statistics['total_students'] ?? 0) }}</p>
                    <p class="text-sm text-green-600 mt-2">{{ $statistics['new_admissions_this_month'] ?? 0 }} this month</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
        </a>

        <a href="{{ route('dashboard.teachers.index') }}" class="bg-white rounded-xl shadow-md p-6 border-l-4 border-emerald-500 hover:shadow-lg hover:-translate-y-0.5 transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">মোট শিক্ষক</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2 group-hover:text-emerald-600 transition-colors">{{ number_format($statistics['total_teachers'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
        </a>

        <a href="{{ route('dashboard.payments.index') }}" class="bg-white rounded-xl shadow-md p-6 border-l-4 border-amber-500 hover:shadow-lg hover:-translate-y-0.5 transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">এই মাসের আয়</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2 group-hover:text-amber-600 transition-colors">৳{{ number_format($statistics['monthly_revenue'] ?? 0) }}</p>
                    <p class="text-sm text-red-600 mt-2">Due: ৳{{ number_format($statistics['total_due'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </a>

        <a href="{{ route('dashboard.batches.index') }}" class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500 hover:shadow-lg hover:-translate-y-0.5 transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">আজকের উপস্থিতি</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2 group-hover:text-purple-600 transition-colors">{{ $statistics['today_attendance_rate'] ?? 0 }}%</p>
                    <p class="text-sm text-gray-500 mt-2">{{ $statistics['total_batches'] ?? 0 }} batches</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
            </div>
        </a>
    </div>

    <!-- Dashboard Charts Section -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <h3 class="text-xl font-bold text-gray-900">📊 Analytics Dashboard</h3>
            
            <!-- Date Range and Filter Controls -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">From:</label>
                    <div class="w-40">
                        <x-ui.date-picker 
                            name="start_date" 
                            id="chart_start_date" 
                            :value="now()->subMonths(6)->format('Y-m-d')" 
                        />
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">To:</label>
                    <div class="w-40">
                        <x-ui.date-picker 
                            name="end_date" 
                            id="chart_end_date" 
                            :value="now()->format('Y-m-d')" 
                        />
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <label for="chart_batch_id" class="text-sm text-gray-600">Batch:</label>
                    <div class="w-48">
                        <x-ui.select name="batch_id" id="chart_batch_id">
                            <option value="">All Batches</option>
                            @foreach(\App\Models\Batch::active()->get() as $batch)
                                <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <label for="chart_course_id" class="text-sm text-gray-600">Course:</label>
                    <div class="w-48">
                        <x-ui.select name="course_id" id="chart_course_id">
                            <option value="">All Courses</option>
                            @foreach(\App\Models\Course::active()->get() as $course)
                                <option value="{{ $course->id }}">{{ $course->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                </div>
                <button type="button" id="applyChartFilters" 
                        class="px-4 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Apply Filters
                </button>
                <button type="button" id="resetChartFilters" 
                        class="px-4 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Reset
                </button>
            </div>
        </div>

        <!-- Charts Grid (2x2) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Payment Trends Chart -->
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-lg font-semibold text-gray-800">💰 Payment Trends</h4>
                    <span class="text-xs text-gray-500">Monthly revenue over time</span>
                </div>
                <div class="chart-container" id="paymentTrendsContainer">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                        <span class="ml-2">Loading chart...</span>
                    </div>
                    <canvas id="paymentTrendsChart"></canvas>
                </div>
            </div>

            <!-- Attendance Statistics Chart -->
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-lg font-semibold text-gray-800">📋 Attendance Statistics</h4>
                    <span class="text-xs text-gray-500">Attendance rates by batch</span>
                </div>
                <div class="chart-container" id="attendanceStatsContainer">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                        <span class="ml-2">Loading chart...</span>
                    </div>
                    <canvas id="attendanceStatsChart"></canvas>
                </div>
            </div>

            <!-- Enrollment Distribution Chart -->
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-lg font-semibold text-gray-800">👥 Enrollment Distribution</h4>
                    <span class="text-xs text-gray-500">Students per batch/course</span>
                </div>
                <div class="chart-container" id="enrollmentDistContainer">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                        <span class="ml-2">Loading chart...</span>
                    </div>
                    <canvas id="enrollmentDistChart"></canvas>
                </div>
            </div>

            <!-- Performance Distribution Chart -->
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-lg font-semibold text-gray-800">📈 Performance Distribution</h4>
                    <span class="text-xs text-gray-500">Grade distribution across exams</span>
                </div>
                <div class="chart-container" id="performanceDistContainer">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                        <span class="ml-2">Loading chart...</span>
                    </div>
                    <canvas id="performanceDistChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Original Charts & Activity Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">মাসিক আয়</h3>
            <div class="h-64 flex items-end justify-between gap-2">
                @foreach($chartData['revenue'] ?? [] as $data)
                    <div class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-blue-500 rounded-t" style="height: {{ max(10, ($data['amount'] / max(1, collect($chartData['revenue'])->max('amount'))) * 200) }}px"></div>
                        <span class="text-xs text-gray-500 mt-2">{{ $data['month'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">সাম্প্রতিক কার্যক্রম</h3>
            <div class="space-y-3">
                @forelse($recentActivities as $activity)
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $activity['type'] === 'payment' ? 'bg-green-100 text-green-600' : 'bg-blue-100 text-blue-600' }}">
                            @if($activity['type'] === 'payment')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                            @endif
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900">{{ $activity['message'] }}</p>
                            <p class="text-xs text-gray-500">{{ $activity['time']->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No recent activities</p>
                @endforelse
            </div>
        </div>
    </div>

@elseif($role === 'teacher')
    <!-- Teacher Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-sm font-medium text-gray-600">আমার ব্যাচ</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $statistics['my_batches'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-emerald-500">
            <p class="text-sm font-medium text-gray-600">মোট শিক্ষার্থী</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $statistics['total_students'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-amber-500">
            <p class="text-sm font-medium text-gray-600">আজকের ক্লাস</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $statistics['today_classes'] ?? 0 }}</p>
        </div>
    </div>

@elseif($role === 'student')
    <!-- Student Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-sm font-medium text-gray-600">উপস্থিতি হার</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $statistics['attendance_rate'] ?? 0 }}%</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-emerald-500">
            <p class="text-sm font-medium text-gray-600">পরীক্ষার ফলাফল</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $statistics['recent_results_count'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-{{ ($statistics['balance'] ?? 0) > 0 ? 'red' : 'green' }}-500">
            <p class="text-sm font-medium text-gray-600">ব্যালেন্স</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">৳{{ number_format(abs($statistics['balance'] ?? 0)) }}</p>
            <p class="text-sm text-gray-500">{{ ($statistics['balance'] ?? 0) > 0 ? 'Due' : 'Advance' }}</p>
        </div>
    </div>
@endif

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">দ্রুত কাজ</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @can('students.create')
            <a href="{{ route('dashboard.students.create') }}" class="p-4 bg-blue-50 hover:bg-blue-100 rounded-xl transition-all group">
                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <p class="font-semibold text-gray-900 text-sm">Add Student</p>
            </a>
            @endcan

            @can('courses.create')
            <a href="{{ route('dashboard.courses.create') }}" class="p-4 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition-all group">
                <div class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </div>
                <p class="font-semibold text-gray-900 text-sm">Add Course</p>
            </a>
            @endcan

            @can('communication.send')
            <a href="{{ route('dashboard.email.index') }}" class="p-4 bg-amber-50 hover:bg-amber-100 rounded-xl transition-all group">
                <div class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <p class="font-semibold text-gray-900 text-sm">Send Email</p>
            </a>
            @endcan

            @can('settings.manage')
            <a href="{{ route('dashboard.settings.index') }}" class="p-4 bg-purple-50 hover:bg-purple-100 rounded-xl transition-all group">
                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <p class="font-semibold text-gray-900 text-sm">Settings</p>
            </a>
            @endcan
        </div>
    </div>
@endsection

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize charts for super-admin role
    @if($role === 'super-admin')
    
    // Chart instances storage
    const charts = {
        paymentTrends: null,
        attendanceStats: null,
        enrollmentDist: null,
        performanceDist: null
    };

    // Chart colors
    const chartColors = {
        primary: 'rgba(59, 130, 246, 0.8)',
        primaryBorder: 'rgba(59, 130, 246, 1)',
        success: 'rgba(16, 185, 129, 0.8)',
        successBorder: 'rgba(16, 185, 129, 1)',
        warning: 'rgba(245, 158, 11, 0.8)',
        warningBorder: 'rgba(245, 158, 11, 1)',
        danger: 'rgba(239, 68, 68, 0.8)',
        dangerBorder: 'rgba(239, 68, 68, 1)',
        purple: 'rgba(139, 92, 246, 0.8)',
        purpleBorder: 'rgba(139, 92, 246, 1)',
        pink: 'rgba(236, 72, 153, 0.8)',
        pinkBorder: 'rgba(236, 72, 153, 1)',
        backgroundColors: [
            'rgba(59, 130, 246, 0.7)',
            'rgba(16, 185, 129, 0.7)',
            'rgba(245, 158, 11, 0.7)',
            'rgba(239, 68, 68, 0.7)',
            'rgba(139, 92, 246, 0.7)',
            'rgba(236, 72, 153, 0.7)',
            'rgba(20, 184, 166, 0.7)',
            'rgba(249, 115, 22, 0.7)'
        ],
        borderColors: [
            'rgba(59, 130, 246, 1)',
            'rgba(16, 185, 129, 1)',
            'rgba(245, 158, 11, 1)',
            'rgba(239, 68, 68, 1)',
            'rgba(139, 92, 246, 1)',
            'rgba(236, 72, 153, 1)',
            'rgba(20, 184, 166, 1)',
            'rgba(249, 115, 22, 1)'
        ]
    };

    // Get filter values
    function getFilters() {
        return {
            start_date: document.getElementById('chart_start_date')?.value || '',
            end_date: document.getElementById('chart_end_date')?.value || '',
            batch_id: document.getElementById('chart_batch_id')?.value || '',
            course_id: document.getElementById('chart_course_id')?.value || ''
        };
    }

    // Show loading state
    function showLoading(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            const loading = container.querySelector('.chart-loading');
            const canvas = container.querySelector('canvas');
            if (loading) loading.style.display = 'flex';
            if (canvas) canvas.style.display = 'none';
        }
    }

    // Hide loading state
    function hideLoading(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            const loading = container.querySelector('.chart-loading');
            const canvas = container.querySelector('canvas');
            if (loading) loading.style.display = 'none';
            if (canvas) canvas.style.display = 'block';
        }
    }

    // Show empty state
    function showEmpty(containerId, message = 'No data available') {
        const container = document.getElementById(containerId);
        if (container) {
            const loading = container.querySelector('.chart-loading');
            const canvas = container.querySelector('canvas');
            if (loading) loading.style.display = 'none';
            if (canvas) canvas.style.display = 'none';
            
            // Remove existing empty state if any
            const existingEmpty = container.querySelector('.chart-empty');
            if (existingEmpty) existingEmpty.remove();
            
            // Add empty state with helpful suggestion
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'chart-empty';
            emptyDiv.innerHTML = `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span class="font-medium">${message}</span>
                <span class="text-xs mt-1">Try adjusting your filters or date range</span>
            `;
            container.appendChild(emptyDiv);
        }
    }

    // Remove empty state
    function removeEmpty(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            const existingEmpty = container.querySelector('.chart-empty');
            if (existingEmpty) existingEmpty.remove();
        }
    }

    // Fetch dashboard data from API
    async function fetchDashboardData(filters = {}) {
        const params = new URLSearchParams();
        params.append('chart_type', 'all');
        
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                params.append(key, filters[key]);
            }
        });

        try {
            const response = await fetch(`/dashboard/reports/dashboard-data?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success) {
                return result.data;
            } else {
                console.error('API Error:', result.error);
                return null;
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            return null;
        }
    }

    // Create Payment Trends Chart (Line Chart)
    function createPaymentTrendsChart(data) {
        const ctx = document.getElementById('paymentTrendsChart');
        if (!ctx) return;

        removeEmpty('paymentTrendsContainer');
        
        // Handle the actual API response format
        const labels = data?.labels || [];
        const datasets = data?.datasets || [];
        
        if (labels.length === 0) {
            showEmpty('paymentTrendsContainer', 'No payment data available for the selected period');
            return;
        }

        if (charts.paymentTrends) {
            charts.paymentTrends.destroy();
        }

        hideLoading('paymentTrendsContainer');

        // Use the first dataset (Payment Amount) for the line chart
        const paymentData = datasets[0]?.data || [];

        charts.paymentTrends = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue (৳)',
                    data: paymentData,
                    borderColor: chartColors.primaryBorder,
                    backgroundColor: chartColors.primary,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '৳' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '৳' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Create Attendance Statistics Chart (Bar Chart)
    function createAttendanceStatsChart(data) {
        const ctx = document.getElementById('attendanceStatsChart');
        if (!ctx) return;

        removeEmpty('attendanceStatsContainer');
        
        // Handle the actual API response format - use pie_data for simpler visualization
        const pieData = data?.pie_data || {};
        const labels = pieData?.labels || [];
        const values = pieData?.data || [];
        
        if (labels.length === 0 || values.every(v => v === 0)) {
            showEmpty('attendanceStatsContainer', 'No attendance data available for the selected filters');
            return;
        }

        if (charts.attendanceStats) {
            charts.attendanceStats.destroy();
        }

        hideLoading('attendanceStatsContainer');

        charts.attendanceStats = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Attendance Count',
                    data: values,
                    backgroundColor: pieData?.backgroundColor || chartColors.backgroundColors,
                    borderColor: chartColors.borderColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed.y / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${context.parsed.y} (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Create Enrollment Distribution Chart (Doughnut Chart)
    function createEnrollmentDistChart(data) {
        const ctx = document.getElementById('enrollmentDistChart');
        if (!ctx) return;

        removeEmpty('enrollmentDistContainer');
        
        // Handle the actual API response format - use by_batch data
        const byBatch = data?.by_batch || {};
        const labels = byBatch?.labels || [];
        const datasets = byBatch?.datasets || [];
        const values = datasets[0]?.data || [];
        
        if (labels.length === 0 || values.length === 0) {
            showEmpty('enrollmentDistContainer', 'No enrollment data available for the selected filters');
            return;
        }

        if (charts.enrollmentDist) {
            charts.enrollmentDist.destroy();
        }

        hideLoading('enrollmentDistContainer');

        charts.enrollmentDist = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: datasets[0]?.backgroundColor || chartColors.backgroundColors,
                    borderColor: datasets[0]?.borderColor || chartColors.borderColors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Create Performance Distribution Chart (Pie Chart)
    function createPerformanceDistChart(data) {
        const ctx = document.getElementById('performanceDistChart');
        if (!ctx) return;

        removeEmpty('performanceDistContainer');
        
        // Handle the actual API response format - use grade_distribution data
        const gradeData = data?.grade_distribution || {};
        const labels = gradeData?.labels || [];
        const datasets = gradeData?.datasets || [];
        const values = datasets[0]?.data || [];
        
        if (labels.length === 0 || values.length === 0 || values.every(v => v === 0)) {
            showEmpty('performanceDistContainer', 'No performance data available for the selected filters');
            return;
        }

        if (charts.performanceDist) {
            charts.performanceDist.destroy();
        }

        hideLoading('performanceDistContainer');

        charts.performanceDist = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: datasets[0]?.backgroundColor || [
                        'rgba(16, 185, 129, 0.8)',  // A+ - Green
                        'rgba(59, 130, 246, 0.8)',  // A - Blue
                        'rgba(139, 92, 246, 0.8)',  // B - Purple
                        'rgba(245, 158, 11, 0.8)',  // C - Yellow
                        'rgba(249, 115, 22, 0.8)',  // D - Orange
                        'rgba(239, 68, 68, 0.8)'   // F - Red
                    ],
                    borderColor: datasets[0]?.borderColor || [
                        'rgba(16, 185, 129, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(139, 92, 246, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(249, 115, 22, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} students (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Load all charts
    async function loadAllCharts() {
        const filters = getFilters();
        
        // Show loading states
        showLoading('paymentTrendsContainer');
        showLoading('attendanceStatsContainer');
        showLoading('enrollmentDistContainer');
        showLoading('performanceDistContainer');

        const data = await fetchDashboardData(filters);

        if (data) {
            createPaymentTrendsChart(data.payment_trends);
            createAttendanceStatsChart(data.attendance_stats);
            createEnrollmentDistChart(data.enrollment_distribution);
            createPerformanceDistChart(data.performance_distribution);
        } else {
            // Show empty states on error
            showEmpty('paymentTrendsContainer', 'Failed to load data');
            showEmpty('attendanceStatsContainer', 'Failed to load data');
            showEmpty('enrollmentDistContainer', 'Failed to load data');
            showEmpty('performanceDistContainer', 'Failed to load data');
        }
    }

    // Event listeners for filter controls
    document.getElementById('applyChartFilters')?.addEventListener('click', function() {
        loadAllCharts();
    });

    document.getElementById('resetChartFilters')?.addEventListener('click', function() {
        // Reset filter values
        const startDate = document.getElementById('chart_start_date');
        const endDate = document.getElementById('chart_end_date');
        const batchId = document.getElementById('chart_batch_id');
        const courseId = document.getElementById('chart_course_id');

        if (startDate) {
            const sixMonthsAgo = new Date();
            sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6);
            startDate.value = sixMonthsAgo.toISOString().split('T')[0];
        }
        if (endDate) {
            endDate.value = new Date().toISOString().split('T')[0];
        }
        if (batchId) batchId.value = '';
        if (courseId) courseId.value = '';

        // Reload charts
        loadAllCharts();
    });

    // Initial load
    loadAllCharts();

    @endif
});
</script>
@endpush

