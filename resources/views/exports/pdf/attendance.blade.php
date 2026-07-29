<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'Attendance Report' }}</title>
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
            background: #006A4E;
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
        
        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }
        
        .data-table thead {
            background: #006A4E;
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
        .status-present {
            background: #d4edda;
            color: #155724;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .status-absent {
            background: #f8d7da;
            color: #721c24;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .status-late {
            background: #fff3cd;
            color: #856404;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        /* Statistics Summary */
        .stats-section {
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: table;
            width: 100%;
        }
        
        .stat-box {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            border: 1px solid #e0e0e0;
            background: #f8f9fa;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #006A4E;
        }
        
        .stat-label {
            font-size: 9px;
            color: #666;
            margin-top: 3px;
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
        
        /* Page Break */
        .page-break {
            page-break-after: always;
        }
        
        /* Percentage Bar */
        .percentage-bar {
            width: 60px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
        }
        
        .percentage-fill {
            height: 100%;
            background: #006A4E;
            border-radius: 4px;
        }
        
        .percentage-text {
            display: inline-block;
            width: 35px;
            text-align: right;
            font-weight: bold;
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
                    <div>Report Type: {{ $reportType ?? 'Attendance' }}</div>
                </div>
            </div>
        </div>
        
        <!-- Report Title -->
        <div class="report-title-section">
            <div class="report-title">{{ $title ?? 'Attendance Report' }}</div>
            <div class="report-subtitle">Comprehensive attendance records and statistics</div>
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
                
                @if(!empty($filters['student_id']) || !empty($filters['student']))
                <div class="filter-item">
                    <span class="filter-label">Student:</span>
                    <span class="filter-value">{{ $filters['student_name'] ?? $filters['student'] ?? 'ID: ' . ($filters['student_id'] ?? 'N/A') }}</span>
                </div>
                @endif
                
                @if(!empty($filters['date_from']) || !empty($filters['start_date']))
                <div class="filter-item">
                    <span class="filter-label">From:</span>
                    <span class="filter-value">{{ $filters['date_from'] ?? $filters['start_date'] }}</span>
                </div>
                @endif
                
                @if(!empty($filters['date_to']) || !empty($filters['end_date']))
                <div class="filter-item">
                    <span class="filter-label">To:</span>
                    <span class="filter-value">{{ $filters['date_to'] ?? $filters['end_date'] }}</span>
                </div>
                @endif
                
                @if(!empty($filters['status']))
                <div class="filter-item">
                    <span class="filter-label">Status:</span>
                    <span class="filter-value">{{ ucfirst($filters['status']) }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif
        
        <!-- Attendance Data Table -->
        @if($data && $data->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 20%;">Student Name</th>
                    <th style="width: 15%;">Student ID</th>
                    <th style="width: 15%;">Batch</th>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 23%;">Attendance Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $index => $record)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $record->student->name ?? $record->student_name ?? 'N/A' }}</td>
                    <td>{{ $record->student->student_id ?? $record->student_id ?? 'N/A' }}</td>
                    <td>{{ $record->batch->name ?? $record->batch_name ?? 'N/A' }}</td>
                    <td>{{ isset($record->date) ? \Carbon\Carbon::parse($record->date)->format('d M Y') : 'N/A' }}</td>
                    <td>
                        @php
                            $status = strtolower($record->status ?? 'unknown');
                        @endphp
                        @if($status === 'present')
                            <span class="status-present">Present</span>
                        @elseif($status === 'absent')
                            <span class="status-absent">Absent</span>
                        @elseif($status === 'late')
                            <span class="status-late">Late</span>
                        @else
                            <span>{{ ucfirst($status) }}</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $rate = $record->attendance_rate ?? $record->attendance_percentage ?? 0;
                        @endphp
                        <span class="percentage-text">{{ number_format($rate, 1) }}%</span>
                        <div class="percentage-bar">
                            <div class="percentage-fill" style="width: {{ min($rate, 100) }}%;"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Summary Statistics -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value">{{ $data->count() }}</div>
                    <div class="stat-label">Total Records</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">{{ $data->where('status', 'present')->count() }}</div>
                    <div class="stat-label">Present</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">{{ $data->where('status', 'absent')->count() }}</div>
                    <div class="stat-label">Absent</div>
                </div>
                <div class="stat-box">
                    @php
                        $totalRecords = $data->count();
                        $presentCount = $data->where('status', 'present')->count();
                        $overallRate = $totalRecords > 0 ? ($presentCount / $totalRecords) * 100 : 0;
                    @endphp
                    <div class="stat-value">{{ number_format($overallRate, 1) }}%</div>
                    <div class="stat-label">Overall Rate</div>
                </div>
            </div>
        </div>
        @else
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <div>No attendance records found for the selected criteria.</div>
        </div>
        @endif
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-left">
                Dhaka IT Institute - Attendance Report
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
