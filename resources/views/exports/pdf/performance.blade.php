<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'Performance Report' }}</title>
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
            color: {{ $primaryColor ?? '#006A4E' }};
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
            border-left: 4px solid {{ $primaryColor ?? '#006A4E' }};
        }
        
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: {{ $primaryColor ?? '#006A4E' }};
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
        
        /* Performance Summary Cards */
        .performance-summary {
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
            width: 20%;
            padding: 12px;
            text-align: center;
            border-radius: 6px;
            vertical-align: top;
        }
        
        .summary-card.students {
            background: #cce5ff;
            border: 1px solid #b8daff;
        }
        
        .summary-card.average {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .summary-card.highest {
            background: #fff3cd;
            border: 1px solid #ffeeba;
        }
        
        .summary-card.pass-rate {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
        }
        
        .summary-card.exams {
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
        
        /* Score Styling */
        .score {
            font-weight: bold;
            text-align: center;
        }
        
        .score-excellent {
            color: #155724;
            background: #d4edda;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .score-good {
            color: #0c5460;
            background: #d1ecf1;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .score-average {
            color: #856404;
            background: #fff3cd;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .score-poor {
            color: #721c24;
            background: #f8d7da;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        /* Grade Badges */
        .grade-badge {
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .grade-a-plus {
            background: #155724;
            color: #fff;
        }
        
        .grade-a {
            background: #28a745;
            color: #fff;
        }
        
        .grade-b {
            background: #17a2b8;
            color: #fff;
        }
        
        .grade-c {
            background: #ffc107;
            color: #333;
        }
        
        .grade-d {
            background: #fd7e14;
            color: #fff;
        }
        
        .grade-f {
            background: #dc3545;
            color: #fff;
        }
        
        /* Rank Badge */
        .rank-badge {
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            border-radius: 50%;
            font-weight: bold;
            font-size: 10px;
        }
        
        .rank-1 {
            background: #ffd700;
            color: #333;
        }
        
        .rank-2 {
            background: #c0c0c0;
            color: #333;
        }
        
        .rank-3 {
            background: #cd7f32;
            color: #fff;
        }
        
        .rank-default {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #e0e0e0;
        }
        
        /* Grade Distribution */
        .grade-distribution {
            margin-bottom: 20px;
        }
        
        .distribution-title {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .distribution-grid {
            display: table;
            width: 100%;
        }
        
        .distribution-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
            border: 1px solid #e0e0e0;
        }
        
        .distribution-grade {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .distribution-count {
            font-size: 18px;
            font-weight: bold;
            color: #006A4E;
        }
        
        .distribution-percent {
            font-size: 9px;
            color: #666;
        }
        
        /* Progress Bar */
        .progress-bar {
            width: 80px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }
        
        .progress-excellent {
            background: #28a745;
        }
        
        .progress-good {
            background: #17a2b8;
        }
        
        .progress-average {
            background: #ffc107;
        }
        
        .progress-poor {
            background: #dc3545;
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
                    <div>Report Type: {{ $reportType ?? 'Performance' }}</div>
                </div>
            </div>
        </div>
        
        <!-- Report Title -->
        <div class="report-title-section">
            <div class="report-title">{{ $title ?? 'Performance Report' }}</div>
            <div class="report-subtitle">Student exam results, scores, and academic rankings</div>
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
                
                @if(!empty($filters['course_id']) || !empty($filters['course']))
                <div class="filter-item">
                    <span class="filter-label">Course:</span>
                    <span class="filter-value">{{ $filters['course_name'] ?? $filters['course'] ?? 'ID: ' . ($filters['course_id'] ?? 'N/A') }}</span>
                </div>
                @endif
                
                @if(!empty($filters['exam_id']) || !empty($filters['exam']))
                <div class="filter-item">
                    <span class="filter-label">Exam:</span>
                    <span class="filter-value">{{ $filters['exam_name'] ?? $filters['exam'] ?? 'ID: ' . ($filters['exam_id'] ?? 'N/A') }}</span>
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
            </div>
        </div>
        @endif
        
        @if($data && $data->count() > 0)
        <!-- Performance Summary -->
        @php
            $totalStudents = $data->count();
            $scores = $data->pluck('score')->filter()->map(fn($s) => floatval($s));
            $averageScore = $scores->count() > 0 ? $scores->avg() : 0;
            $highestScore = $scores->count() > 0 ? $scores->max() : 0;
            $lowestScore = $scores->count() > 0 ? $scores->min() : 0;
            $passCount = $data->filter(function($item) {
                $score = floatval($item->score ?? 0);
                $passMark = floatval($item->pass_mark ?? 40);
                return $score >= $passMark;
            })->count();
            $passRate = $totalStudents > 0 ? ($passCount / $totalStudents) * 100 : 0;
        @endphp
        <div class="performance-summary">
            <div class="summary-grid">
                <div class="summary-card students">
                    <div class="summary-value">{{ $totalStudents }}</div>
                    <div class="summary-label">Total Students</div>
                </div>
                <div class="summary-card average">
                    <div class="summary-value">{{ number_format($averageScore, 1) }}</div>
                    <div class="summary-label">Average Score</div>
                </div>
                <div class="summary-card highest">
                    <div class="summary-value">{{ number_format($highestScore, 1) }}</div>
                    <div class="summary-label">Highest Score</div>
                </div>
                <div class="summary-card pass-rate">
                    <div class="summary-value">{{ number_format($passRate, 1) }}%</div>
                    <div class="summary-label">Pass Rate</div>
                </div>
                <div class="summary-card exams">
                    <div class="summary-value">{{ $data->pluck('exam_id')->unique()->count() ?: 1 }}</div>
                    <div class="summary-label">Exams</div>
                </div>
            </div>
        </div>
        
        <!-- Grade Distribution -->
        @php
            $gradeDistribution = $data->groupBy(function($item) {
                $score = floatval($item->score ?? 0);
                if ($score >= 90) return 'A+';
                if ($score >= 80) return 'A';
                if ($score >= 70) return 'B';
                if ($score >= 60) return 'C';
                if ($score >= 50) return 'D';
                return 'F';
            })->map->count();
            
            $grades = ['A+', 'A', 'B', 'C', 'D', 'F'];
        @endphp
        <div class="grade-distribution">
            <div class="distribution-title">Grade Distribution</div>
            <div class="distribution-grid">
                @foreach($grades as $grade)
                <div class="distribution-item">
                    <div class="distribution-grade">{{ $grade }}</div>
                    <div class="distribution-count">{{ $gradeDistribution[$grade] ?? 0 }}</div>
                    <div class="distribution-percent">
                        {{ $totalStudents > 0 ? number_format((($gradeDistribution[$grade] ?? 0) / $totalStudents) * 100, 1) : 0 }}%
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        
        <!-- Performance Data Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">Rank</th>
                    <th style="width: 20%;">Student Name</th>
                    <th style="width: 12%;">Student ID</th>
                    <th style="width: 15%;">Exam</th>
                    <th style="width: 12%;">Course</th>
                    <th style="width: 12%;">Score</th>
                    <th style="width: 8%;">Grade</th>
                    <th style="width: 16%;">Performance</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sortedData = $data->sortByDesc('score')->values();
                @endphp
                @foreach($sortedData as $index => $result)
                @php
                    $rank = $index + 1;
                    $score = floatval($result->score ?? 0);
                    $maxScore = floatval($result->max_score ?? $result->total_marks ?? 100);
                    $percentage = $maxScore > 0 ? ($score / $maxScore) * 100 : 0;
                    
                    // Determine grade
                    if ($percentage >= 90) $grade = 'A+';
                    elseif ($percentage >= 80) $grade = 'A';
                    elseif ($percentage >= 70) $grade = 'B';
                    elseif ($percentage >= 60) $grade = 'C';
                    elseif ($percentage >= 50) $grade = 'D';
                    else $grade = 'F';
                    
                    // Determine grade class
                    $gradeClass = match($grade) {
                        'A+' => 'grade-a-plus',
                        'A' => 'grade-a',
                        'B' => 'grade-b',
                        'C' => 'grade-c',
                        'D' => 'grade-d',
                        default => 'grade-f'
                    };
                    
                    // Determine progress class
                    if ($percentage >= 80) $progressClass = 'progress-excellent';
                    elseif ($percentage >= 60) $progressClass = 'progress-good';
                    elseif ($percentage >= 40) $progressClass = 'progress-average';
                    else $progressClass = 'progress-poor';
                @endphp
                <tr>
                    <td style="text-align: center;">
                        @if($rank === 1)
                            <span class="rank-badge rank-1">1</span>
                        @elseif($rank === 2)
                            <span class="rank-badge rank-2">2</span>
                        @elseif($rank === 3)
                            <span class="rank-badge rank-3">3</span>
                        @else
                            <span class="rank-badge rank-default">{{ $rank }}</span>
                        @endif
                    </td>
                    <td>{{ $result->student->name ?? $result->student_name ?? 'N/A' }}</td>
                    <td>{{ $result->student->student_id ?? $result->student_id ?? 'N/A' }}</td>
                    <td>{{ $result->exam->name ?? $result->exam_name ?? 'N/A' }}</td>
                    <td>{{ $result->course->name ?? $result->course_name ?? 'N/A' }}</td>
                    <td class="score">
                        <span class="score-{{ $percentage >= 80 ? 'excellent' : ($percentage >= 60 ? 'good' : ($percentage >= 40 ? 'average' : 'poor')) }}">
                            {{ number_format($score, 1) }} / {{ number_format($maxScore, 0) }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <span class="grade-badge {{ $gradeClass }}">{{ $grade }}</span>
                    </td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill {{ $progressClass }}" style="width: {{ min($percentage, 100) }}%;"></div>
                        </div>
                        <span style="margin-left: 5px; font-weight: bold;">{{ number_format($percentage, 1) }}%</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <div class="empty-state-icon">📊</div>
            <div>No performance records found for the selected criteria.</div>
        </div>
        @endif
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-left">
                Dhaka IT Institute - Performance Report
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
