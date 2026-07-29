<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'Student Report' }}</title>
    <style>
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }
        
        /* Page Layout */
        .page {
            padding: 20px 30px;
        }
        
        /* Header with Branding */
        .header {
            border-bottom: 3px solid {{ $primaryColor ?? '#006A4E' }};
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header-content {
            display: table;
            width: 100%;
        }
        
        .logo-section {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
        }
        
        .logo-placeholder {
            width: 60px;
            height: 60px;
            background: {{ $primaryColor ?? '#006A4E' }};
            border-radius: 8px;
            text-align: center;
            line-height: 60px;
            color: #fff;
            font-weight: bold;
            font-size: 24px;
        }
        
        .institution-info {
            display: table-cell;
            vertical-align: middle;
            padding-left: 15px;
        }
        
        .institution-name {
            font-size: 20px;
            font-weight: bold;
            color: #006A4E;
            margin-bottom: 3px;
        }
        
        .institution-tagline {
            font-size: 11px;
            color: #666;
        }
        
        .report-meta {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
            font-size: 9px;
            color: #666;
        }
        
        /* Report Title Section */
        .report-title-section {
            background: #f8f9fa;
            padding: 12px 15px;
            margin-bottom: 15px;
            border-left: 4px solid #006A4E;
        }
        
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #006A4E;
            margin-bottom: 5px;
        }
        
        .report-subtitle {
            font-size: 10px;
            color: #666;
        }
        
        /* Filters Summary */
        .filters-section {
            background: #fff;
            border: 1px solid #e0e0e0;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .filters-title {
            font-size: 11px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .filters-grid {
            display: table;
            width: 100%;
        }
        
        .filter-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
        }
        
        .filter-label {
            font-weight: bold;
            color: #666;
        }
        
        .filter-value {
            color: #333;
        }
        
        /* Summary Statistics */
        .summary-section {
            margin-bottom: 20px;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
        }
        
        .summary-card {
            display: table-cell;
            width: 25%;
            padding: 12px;
            text-align: center;
            border-radius: 6px;
            vertical-align: top;
        }
        
        .summary-card.total {
            background: #cce5ff;
            border: 1px solid #b8daff;
        }
        
        .summary-card.active {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .summary-card.batches {
            background: #fff3cd;
            border: 1px solid #ffeeba;
        }
        
        .summary-card.courses {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
        }
        
        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        
        .summary-label {
            font-size: 9px;
            color: #666;
            margin-top: 5px;
        }
        
        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }
        
        .data-table thead {
            background: {{ $primaryColor ?? '#006A4E' }};
        }
        
        .data-table th {
            color: #fff;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #005840;
        }
        
        .data-table td {
            padding: 8px;
            border: 1px solid #e0e0e0;
            vertical-align: middle;
        }
        
        .data-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .data-table tbody tr:hover {
            background: #f0f7f5;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-graduated {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-suspended {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-pending {
            background: #e2e3e5;
            color: #383d41;
        }
        
        /* Amount Styling */
        .amount {
            font-weight: bold;
            text-align: right;
        }
        
        .amount-positive {
            color: #155724;
        }
        
        .amount-negative {
            color: #721c24;
        }
        
        .amount-neutral {
            color: #333;
        }
        
        /* Performance Indicator */
        .performance-indicator {
            display: inline-block;
            width: 60px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            vertical-align: middle;
        }
        
        .performance-fill {
            height: 100%;
            border-radius: 4px;
        }
        
        .performance-excellent {
            background: #28a745;
        }
        
        .performance-good {
            background: #17a2b8;
        }
        
        .performance-average {
            background: #ffc107;
        }
        
        .performance-poor {
            background: #dc3545;
        }
        
        /* Contact Info */
        .contact-info {
            font-size: 8px;
            color: #666;
        }
        
        /* Enrollment Info */
        .enrollment-info {
            font-size: 8px;
            color: #666;
        }
        
        /* Section Headers */
        .section-header {
            font-size: 12px;
            font-weight: bold;
            color: #006A4E;
            margin: 15px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        /* Currency Symbol */
        .currency {
            font-family: 'DejaVu Sans', Arial, sans-serif;
        }
        
        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 30px;
            border-top: 1px solid #e0e0e0;
            background: #fff;
            font-size: 8px;
            color: #666;
        }
        
        .footer-content {
            display: table;
            width: 100%;
        }
        
        .footer-left {
            display: table-cell;
            text-align: left;
        }
        
        .footer-center {
            display: table-cell;
            text-align: center;
        }
        
        .footer-right {
            display: table-cell;
            text-align: right;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        /* Batch Badge */
        .batch-badge {
            background: #e9ecef;
            color: #495057;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
        }
        
        /* Course List */
        .course-list {
            font-size: 8px;
            color: #666;
        }
        
        .course-item {
            display: inline-block;
            background: #f8f9fa;
            padding: 1px 5px;
            margin: 1px;
            border-radius: 2px;
            border: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header with Institution Branding -->
        <div class="header">
            <div class="header-content">
                @php
                    $settingsService = app(\App\Services\SettingsService::class);
                    $logoUrl = $settingsService->getLogo();
                    $institutionName = $settingsService->get('institution_name', 'Dhaka IT Institute');
                @endphp
                <div class="logo-section">
                    <img src="{{ $logoUrl }}" alt="{{ $institutionName }}" style="max-width: 60px; max-height: 60px; object-fit: contain;">
                </div>
                <div class="institution-info">
                    <div class="institution-name">{{ $institutionName }}</div>
                    <div class="institution-tagline">Learning Management System</div>
                </div>
                <div class="report-meta">
                    <div>Generated: {{ $generatedAt ?? now()->format('Y-m-d H:i:s') }}</div>
                    <div>Report Type: {{ $reportType ?? 'Student' }}</div>
                </div>
            </div>
        </div>
        
        <!-- Report Title -->
        <div class="report-title-section">
            <div class="report-title">{{ $title ?? 'Student Report' }}</div>
            <div class="report-subtitle">Comprehensive student information including enrollment, payments, and performance</div>
        </div>
        
        <!-- Applied Filters -->
        @if(!empty($filters))
        <div class="filters-section">
            <div class="filters-title">Applied Filters</div>
            <div class="filters-grid">
                @if(!empty($filters['batch_id']) || !empty($filters['batch']))
                <div class="filter-item">
                    <span class="filter-label">Batch:</span>
                    <span class="filter-value">{{ $filters['batch_name'] ?? $filters['batch'] ?? 'ID: ' . ($filters['batch_id'] ?? 'N/A') }}</span>
                </div>
                @endif
                
                @if(!empty($filters['status']))
                <div class="filter-item">
                    <span class="filter-label">Status:</span>
                    <span class="filter-value">{{ ucfirst($filters['status']) }}</span>
                </div>
                @endif
                
                @if(!empty($filters['search']) || !empty($filters['name']))
                <div class="filter-item">
                    <span class="filter-label">Search:</span>
                    <span class="filter-value">{{ $filters['search'] ?? $filters['name'] }}</span>
                </div>
                @endif
                
                @if(!empty($filters['enrollment_date_from']))
                <div class="filter-item">
                    <span class="filter-label">Enrolled From:</span>
                    <span class="filter-value">{{ $filters['enrollment_date_from'] }}</span>
                </div>
                @endif
                
                @if(!empty($filters['enrollment_date_to']))
                <div class="filter-item">
                    <span class="filter-label">Enrolled To:</span>
                    <span class="filter-value">{{ $filters['enrollment_date_to'] }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif
        
        @if($data && $data->count() > 0)
        <!-- Summary Statistics -->
        @php
            $totalStudents = $data->count();
            $activeStudents = $data->where('status', 'active')->count();
            $uniqueBatches = $data->pluck('batch_id')->unique()->count();
            $uniqueCourses = $data->flatMap(function($student) {
                return $student->courses ?? collect();
            })->unique('id')->count();
            
            // Calculate total payments and dues
            $totalPayments = $data->sum(function($student) {
                return $student->payments ? $student->payments->sum('amount') : ($student->total_paid ?? 0);
            });
            $totalDues = $data->sum(function($student) {
                return $student->balance ?? $student->due_amount ?? 0;
            });
        @endphp
        <div class="summary-section">
            <div class="summary-grid">
                <div class="summary-card total">
                    <div class="summary-value">{{ $totalStudents }}</div>
                    <div class="summary-label">Total Students</div>
                </div>
                <div class="summary-card active">
                    <div class="summary-value">{{ $activeStudents }}</div>
                    <div class="summary-label">Active Students</div>
                </div>
                <div class="summary-card batches">
                    <div class="summary-value">{{ $uniqueBatches }}</div>
                    <div class="summary-label">Batches</div>
                </div>
                <div class="summary-card courses">
                    <div class="summary-value"><span class="currency">৳</span> {{ number_format($totalDues, 0) }}</div>
                    <div class="summary-label">Total Outstanding</div>
                </div>
            </div>
        </div>
        
        <!-- Student Data Table -->
        <div class="section-header">Student Details</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 14%;">Student Name</th>
                    <th style="width: 10%;">Student ID</th>
                    <th style="width: 12%;">Batch</th>
                    <th style="width: 10%;">Enrolled</th>
                    <th style="width: 8%;">Status</th>
                    <th style="width: 10%;">Total Paid</th>
                    <th style="width: 10%;">Balance</th>
                    <th style="width: 10%;">Avg. Score</th>
                    <th style="width: 12%;">Contact</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $index => $student)
                @php
                    // Calculate student metrics
                    $totalPaid = $student->payments ? $student->payments->sum('amount') : ($student->total_paid ?? 0);
                    $balance = $student->balance ?? $student->due_amount ?? 0;
                    
                    // Calculate average score
                    $avgScore = 0;
                    if ($student->examResults && $student->examResults->count() > 0) {
                        $avgScore = $student->examResults->avg('score');
                    } elseif (isset($student->average_score)) {
                        $avgScore = $student->average_score;
                    }
                    
                    // Determine performance class
                    if ($avgScore >= 80) $perfClass = 'performance-excellent';
                    elseif ($avgScore >= 60) $perfClass = 'performance-good';
                    elseif ($avgScore >= 40) $perfClass = 'performance-average';
                    else $perfClass = 'performance-poor';
                    
                    // Status class
                    $status = strtolower($student->status ?? 'active');
                    $statusClass = match($status) {
                        'active' => 'status-active',
                        'inactive' => 'status-inactive',
                        'graduated' => 'status-graduated',
                        'suspended' => 'status-suspended',
                        default => 'status-pending'
                    };
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $student->name ?? 'N/A' }}</strong>
                        @if($student->email)
                        <div class="contact-info">{{ $student->email }}</div>
                        @endif
                    </td>
                    <td>{{ $student->student_id ?? $student->id ?? 'N/A' }}</td>
                    <td>
                        @if($student->batch)
                            <span class="batch-badge">{{ $student->batch->name ?? 'N/A' }}</span>
                        @elseif($student->batch_name)
                            <span class="batch-badge">{{ $student->batch_name }}</span>
                        @else
                            <span class="batch-badge">N/A</span>
                        @endif
                    </td>
                    <td>
                        @if($student->enrollment_date ?? $student->created_at)
                            {{ \Carbon\Carbon::parse($student->enrollment_date ?? $student->created_at)->format('d M Y') }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        <span class="status-badge {{ $statusClass }}">{{ ucfirst($status) }}</span>
                    </td>
                    <td class="amount amount-positive">
                        <span class="currency">৳</span> {{ number_format($totalPaid, 0) }}
                    </td>
                    <td class="amount {{ $balance > 0 ? 'amount-negative' : 'amount-neutral' }}">
                        <span class="currency">৳</span> {{ number_format($balance, 0) }}
                    </td>
                    <td>
                        @if($avgScore > 0)
                        <div class="performance-indicator">
                            <div class="performance-fill {{ $perfClass }}" style="width: {{ min($avgScore, 100) }}%;"></div>
                        </div>
                        <span style="margin-left: 3px;">{{ number_format($avgScore, 1) }}%</span>
                        @else
                        <span style="color: #999;">N/A</span>
                        @endif
                    </td>
                    <td class="contact-info">
                        @if($student->phone)
                            {{ $student->phone }}
                        @elseif($student->mobile)
                            {{ $student->mobile }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Financial Summary -->
        <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 4px;">
            <table style="width: 100%; font-size: 10px;">
                <tr>
                    <td style="width: 50%;">
                        <strong>Total Students:</strong> {{ $totalStudents }}
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <strong>Total Payments Received:</strong> <span class="currency">৳</span> {{ number_format($totalPayments, 2) }}
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>Active Students:</strong> {{ $activeStudents }} ({{ $totalStudents > 0 ? number_format(($activeStudents / $totalStudents) * 100, 1) : 0 }}%)
                    </td>
                    <td style="text-align: right;">
                        <strong>Total Outstanding Dues:</strong> <span class="currency">৳</span> {{ number_format($totalDues, 2) }}
                    </td>
                </tr>
            </table>
        </div>
        @else
        <div class="empty-state">
            <div class="empty-state-icon">👨‍🎓</div>
            <div>No student records found for the selected criteria.</div>
        </div>
        @endif
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-left">
                Dhaka IT Institute - Student Report
            </div>
            <div class="footer-center">
                Confidential - For Internal Use Only
            </div>
            <div class="footer-right">
                Page <span class="pagenum"></span>
            </div>
        </div>
    </div>
</body>
</html>
