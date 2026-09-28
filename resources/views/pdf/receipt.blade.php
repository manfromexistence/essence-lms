<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 24px;
            border-bottom: 2px solid #333;
            padding-bottom: 16px;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
        }

        .header p {
            margin: 4px 0;
            color: #666;
        }

        .receipt-meta {
            width: 100%;
            margin-bottom: 18px;
        }

        .receipt-meta td {
            padding: 3px 0;
            vertical-align: top;
        }

        .receipt-meta .label {
            color: #666;
            width: 38%;
        }

        .receipt-meta .value {
            font-weight: bold;
        }

        .status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #999;
        }

        .status-paid {
            color: #15803d;
            border-color: #86efac;
            background: #f0fdf4;
        }

        .status-pending {
            color: #a16207;
            border-color: #fde68a;
            background: #fefce8;
        }

        .status-rejected {
            color: #b91c1c;
            border-color: #fca5a5;
            background: #fef2f2;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 18px 0;
        }

        table.items th,
        table.items td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        table.items th {
            background-color: #f5f5f5;
        }

        table.items td.amount,
        table.items th.amount {
            text-align: right;
        }

        .total-box {
            text-align: right;
            margin-top: 10px;
            padding: 12px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }

        .total-box .total-label {
            font-size: 13px;
            color: #666;
        }

        .total-box .total-value {
            font-size: 20px;
            font-weight: bold;
        }

        .note {
            margin-top: 16px;
            padding: 10px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            font-size: 11px;
            color: #78350f;
        }

        .footer {
            margin-top: 36px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }

        .signature-section {
            margin-top: 50px;
            width: 100%;
        }

        .signature-box {
            width: 50%;
            text-align: center;
            float: left;
        }

        .signature-line {
            border-top: 1px solid #333;
            width: 70%;
            margin: 0 auto;
            padding-top: 4px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>

<body>
    @php
        $statusClass = match ($payment->status) {
            'approved', 'completed', 'paid' => 'status-paid',
            'rejected' => 'status-rejected',
            default => 'status-pending',
        };
        $statusLabel = ucfirst($payment->status ?? 'unknown');
        $studentName = $student->user->name ?? ($student->name_bn ?? 'Student');
        $courseName = $payment->course->name ?? ($student->batch?->course?->name ?? null);
    @endphp

    <div class="header">
        <h1>{{ config('app.name', 'Dhaka IT Institute') }}</h1>
        <p>Official Payment Receipt</p>
    </div>

    <table class="receipt-meta">
        <tr>
            <td class="label">Receipt No.</td>
            <td class="value">{{ $payment->receipt_number ?: 'RCPT-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}
            </td>
        </tr>
        <tr>
            <td class="label">Student Name</td>
            <td class="value">{{ $studentName }}</td>
        </tr>
        <tr>
            <td class="label">Student ID</td>
            <td class="value">{{ $student->registration_no ?: ($student->id ? 'STU-' . $student->id : '—') }}</td>
        </tr>
        @if ($courseName)
            <tr>
                <td class="label">Course</td>
                <td class="value">{{ $courseName }}</td>
            </tr>
        @endif
        @if ($student->batch)
            <tr>
                <td class="label">Batch</td>
                <td class="value">{{ $student->batch->name }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">Payment Date</td>
            <td class="value">
                {{ optional($payment->payment_date ?? $payment->submitted_at)->format('M d, Y') ?? '—' }}
            </td>
        </tr>
        <tr>
            <td class="label">Payment Method</td>
            <td class="value">{{ strtoupper($payment->payment_method ?? '—') }}</td>
        </tr>
        @if ($payment->transaction_id || $payment->transaction_reference)
            <tr>
                <td class="label">Transaction Ref.</td>
                <td class="value">{{ $payment->transaction_id ?: $payment->transaction_reference }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">Status</td>
            <td class="value"><span class="status {{ $statusClass }}">{{ $statusLabel }}</span></td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 70%;">Description</th>
                <th class="amount" style="width: 30%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $courseName ? 'Course fee payment — ' . $courseName : 'Course fee payment' }}</td>
                <td class="amount">৳{{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="total-box">
        <span class="total-label">Total Paid</span><br>
        <span class="total-value">৳{{ number_format((float) $payment->amount, 2) }}</span>
    </div>

    @if ($payment->status === 'rejected' && $payment->admin_notes)
        <div class="note">
            <strong>Note from the office:</strong> {{ $payment->admin_notes }}
        </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">Received By</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Authorised Signature</div>
        </div>
    </div>

    <div class="footer">
        This is a computer-generated receipt issued by {{ config('app.name', 'Dhaka IT Institute') }}.
        Generated on {{ now()->format('M d, Y g:i A') }}.
    </div>
</body>

</html>
