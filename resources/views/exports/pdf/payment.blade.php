<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'Payment Report' }}</title>
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
        
        /* Financial Summary Cards */
        .financial-summary {
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
            padding: 15px;
            text-align: center;
            border-radius: 6px;
            vertical-align: top;
        }
        
        .summary-card.revenue {
            background: {{ $primaryColor ?? '#d4edda' }};
            border: 1px solid {{ $primaryColor ?? '#c3e6cb' }};
        }
        
        .summary-card.pending {
            background: #fff3cd;
            border: 1px solid #ffeeba;
        }
        
        .summary-card.transactions {
            background: #cce5ff;
            border: 1px solid #b8daff;
        }
        
        .summary-card.average {
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
            border: 1px solid {{ $primaryColor ?? '#005840' }};
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
        
        /* Amount Styling */
        .amount {
            font-weight: bold;
            text-align: right;
        }
        
        .amount-positive {
            color: {{ $primaryColor ?? '#155724' }};
        }
        
        .amount-negative {
            color: #721c24;
        }
        
        /* Payment Method Badges */
        .method-badge {
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .method-cash {
            background: #d4edda;
            color: #155724;
        }
        
        .method-bkash {
            background: #e83e8c20;
            color: #e83e8c;
        }
        
        .method-nagad {
            background: #fd7e1420;
            color: #fd7e14;
        }
        
        .method-bank {
            background: #cce5ff;
            color: #004085;
        }
        
        .method-default {
            background: #f8f9fa;
            color: #666;
        }
        
        /* Status Badges */
        .status-completed {
            background: #d4edda;
            color: #155724;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .status-failed {
            background: #f8d7da;
            color: #721c24;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        /* Payment Method Breakdown */
        .method-breakdown {
            margin-bottom: 20px;
        }
        
        .breakdown-title {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .breakdown-table {
            width: 50%;
            border-collapse: collapse;
            font-size: 9px;
        }
        
        .breakdown-table th,
        .breakdown-table td {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
        }
        
        .breakdown-table th {
            background: #f8f9fa;
            text-align: left;
            font-weight: bold;
        }
        
        .breakdown-table td:last-child {
            text-align: right;
            font-weight: bold;
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
        
        /* Currency Symbol */
        .currency {
            font-family: 'DejaVu Sans', Arial, sans-serif;
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
                    <div>Report Type: {{ $reportType ?? 'Payment' }}</div>
                </div>
            </div>
        </div>
        
        <!-- Report Title -->
        <div class="report-title-section">
            <div class="report-title">{{ $title ?? 'Payment Report' }}</div>
            <div class="report-subtitle">Financial transactions and payment summary</div>
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
                
                @if(!empty($filters['payment_method']) || !empty($filters['method']))
                <div class="filter-item">
                    <span class="filter-label">Payment Method:</span>
                    <span class="filter-value">{{ ucfirst($filters['payment_method'] ?? $filters['method'] ?? 'All') }}</span>
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
        
        @if($data && $data->count() > 0)
        <!-- Financial Summary -->
        @php
            $totalRevenue = $data->sum('amount');
            $transactionCount = $data->count();
            $averagePayment = $transactionCount > 0 ? $totalRevenue / $transactionCount : 0;
            $pendingAmount = $data->where('status', 'pending')->sum('amount');
        @endphp
        <div class="financial-summary">
            <div class="summary-grid">
                <div class="summary-card revenue">
                    <div class="summary-value"><span class="currency">৳</span> {{ number_format($totalRevenue, 2) }}</div>
                    <div class="summary-label">Total Revenue</div>
                </div>
                <div class="summary-card pending">
                    <div class="summary-value"><span class="currency">৳</span> {{ number_format($pendingAmount, 2) }}</div>
                    <div class="summary-label">Pending Amount</div>
                </div>
                <div class="summary-card transactions">
                    <div class="summary-value">{{ $transactionCount }}</div>
                    <div class="summary-label">Total Transactions</div>
                </div>
                <div class="summary-card average">
                    <div class="summary-value"><span class="currency">৳</span> {{ number_format($averagePayment, 2) }}</div>
                    <div class="summary-label">Average Payment</div>
                </div>
            </div>
        </div>
        
        <!-- Payment Method Breakdown -->
        @php
            $methodBreakdown = $data->groupBy(function($item) {
                return $item->payment_method ?? $item->method ?? 'Other';
            })->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount')
                ];
            });
        @endphp
        @if($methodBreakdown->count() > 0)
        <div class="method-breakdown">
            <div class="breakdown-title">Payment Method Breakdown</div>
            <table class="breakdown-table">
                <thead>
                    <tr>
                        <th>Payment Method</th>
                        <th>Transactions</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($methodBreakdown as $method => $stats)
                    <tr>
                        <td>{{ ucfirst($method) }}</td>
                        <td>{{ $stats['count'] }}</td>
                        <td><span class="currency">৳</span> {{ number_format($stats['total'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        
        <!-- Payment Data Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 12%;">Invoice #</th>
                    <th style="width: 18%;">Student Name</th>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 12%;">Method</th>
                    <th style="width: 13%;">Amount</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 18%;">Reference</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $index => $payment)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $payment->invoice_number ?? $payment->invoice->invoice_number ?? 'N/A' }}</td>
                    <td>{{ $payment->student->name ?? $payment->student_name ?? 'N/A' }}</td>
                    <td>{{ isset($payment->payment_date) ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : (isset($payment->created_at) ? \Carbon\Carbon::parse($payment->created_at)->format('d M Y') : 'N/A') }}</td>
                    <td>
                        @php
                            $method = strtolower($payment->payment_method ?? $payment->method ?? 'other');
                        @endphp
                        @if($method === 'cash')
                            <span class="method-badge method-cash">Cash</span>
                        @elseif($method === 'bkash')
                            <span class="method-badge method-bkash">bKash</span>
                        @elseif($method === 'nagad')
                            <span class="method-badge method-nagad">Nagad</span>
                        @elseif($method === 'bank' || $method === 'bank_transfer')
                            <span class="method-badge method-bank">Bank</span>
                        @else
                            <span class="method-badge method-default">{{ ucfirst($method) }}</span>
                        @endif
                    </td>
                    <td class="amount amount-positive">
                        <span class="currency">৳</span> {{ number_format($payment->amount ?? 0, 2) }}
                    </td>
                    <td>
                        @php
                            $status = strtolower($payment->status ?? 'completed');
                        @endphp
                        @if($status === 'completed' || $status === 'paid')
                            <span class="status-completed">Completed</span>
                        @elseif($status === 'pending')
                            <span class="status-pending">Pending</span>
                        @elseif($status === 'failed')
                            <span class="status-failed">Failed</span>
                        @else
                            <span>{{ ucfirst($status) }}</span>
                        @endif
                    </td>
                    <td>{{ $payment->transaction_reference ?? $payment->reference ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Total Row -->
        <div style="text-align: right; margin-top: 10px; padding: 10px; background: #f8f9fa; border: 1px solid #e0e0e0;">
            <strong>Grand Total: <span class="currency">৳</span> {{ number_format($totalRevenue, 2) }}</strong>
        </div>
        @else
        <div class="empty-state">
            <div class="empty-state-icon">💰</div>
            <div>No payment records found for the selected criteria.</div>
        </div>
        @endif
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-left">
                Dhaka IT Institute - Payment Report
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
