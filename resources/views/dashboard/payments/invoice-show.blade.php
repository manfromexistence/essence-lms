<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $invoice->invoice_number ?? 'N/A' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            background: #f5f5f5;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #3d59f9 0%, #1d4ed8 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .invoice-header .company-info h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .invoice-header .company-info p {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .invoice-header .invoice-info {
            text-align: right;
        }
        
        .invoice-header .invoice-info h2 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .invoice-header .invoice-info p {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .invoice-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 10px;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-overdue {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-cancelled {
            background: #e5e7eb;
            color: #374151;
        }
        
        .invoice-body {
            padding: 30px;
        }
        
        .billing-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .billing-box h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .billing-box p {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .billing-box .name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .invoice-details {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .invoice-details table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .invoice-details th {
            background: #f9fafb;
            padding: 12px 15px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 600;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .invoice-details td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .invoice-details tr:last-child td {
            border-bottom: none;
        }
        
        .invoice-details .description {
            font-weight: 500;
        }
        
        .invoice-details .amount {
            text-align: right;
            font-weight: 600;
        }
        
        .invoice-summary {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        
        .summary-box {
            width: 300px;
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            padding-top: 12px;
            margin-top: 8px;
            border-top: 2px solid #3d59f9;
        }
        
        .summary-row .label {
            color: #6b7280;
        }
        
        .summary-row .value {
            font-weight: 600;
        }
        
        .summary-row.total .label,
        .summary-row.total .value {
            font-size: 18px;
            color: #3d59f9;
        }
        
        .payment-instructions {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .payment-instructions h4 {
            font-size: 14px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 10px;
        }
        
        .payment-instructions p {
            font-size: 13px;
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .payment-method {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 6px;
            border: 1px solid #bfdbfe;
        }
        
        .payment-method .icon {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .payment-method .name {
            font-size: 11px;
            font-weight: 500;
            color: #1e40af;
        }
        
        .invoice-footer {
            background: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .invoice-footer p {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        
        .invoice-footer .thank-you {
            font-size: 14px;
            color: #3d59f9;
            font-weight: 600;
        }
        
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
        }
        
        .btn-primary {
            background: #3d59f9;
            color: white;
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .invoice-container {
                box-shadow: none;
                margin: 0;
                max-width: 100%;
            }
            
            .action-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="action-buttons">
        <a href="{{ route('dashboard.payments.invoices') }}" class="btn btn-secondary">← Back</a>
        @if($invoice->status === 'pending')
            <a href="{{ route('dashboard.payments.create') }}?student_id={{ $invoice->student_id }}&invoice_id={{ $invoice->id }}" class="btn btn-success">
                💳 Record Payment
            </a>
        @endif
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print Invoice</button>
    </div>
    
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h1>{{ $institutionName }}</h1>
                @if($institutionAddress)
                    <p>{{ $institutionAddress }}</p>
                @endif
                @if($institutionPhone || $institutionEmail)
                    <p>
                        @if($institutionPhone) Phone: {{ $institutionPhone }} @endif
                        @if($institutionPhone && $institutionEmail) | @endif
                        @if($institutionEmail) Email: {{ $institutionEmail }} @endif
                    </p>
                @endif
            </div>
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Date:</strong> {{ $invoice->created_at->format('F d, Y') }}</p>
                <p><strong>Due Date:</strong> {{ $invoice->due_date ? $invoice->due_date->format('F d, Y') : 'N/A' }}</p>
                @php
                    $isOverdue = $invoice->status === 'pending' && $invoice->due_date && $invoice->due_date->isPast();
                    $displayStatus = $isOverdue ? 'overdue' : $invoice->status;
                @endphp
                <span class="invoice-status status-{{ $displayStatus }}">
                    {{ ucfirst($displayStatus) }}
                </span>
            </div>
        </div>
        
        <!-- Body -->
        <div class="invoice-body">
            <!-- Billing Information -->
            <div class="billing-section">
                <div class="billing-box">
                    <h3>Bill To</h3>
                    <p class="name">{{ $invoice->student->user->name ?? 'N/A' }}</p>
                    <p>Registration: {{ $invoice->student->registration_no ?? 'N/A' }}</p>
                    <p>Batch: {{ $invoice->student->batch->name ?? 'N/A' }}</p>
                    @if($invoice->student->phone)
                        <p>Phone: {{ $invoice->student->phone }}</p>
                    @endif
                    @if($invoice->student->user->email ?? null)
                        <p>Email: {{ $invoice->student->user->email }}</p>
                    @endif
                </div>
                <div class="billing-box">
                    <h3>Invoice Details</h3>
                    <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
                    <p><strong>Issue Date:</strong> {{ $invoice->created_at->format('F d, Y') }}</p>
                    <p><strong>Due Date:</strong> {{ $invoice->due_date ? $invoice->due_date->format('F d, Y') : 'N/A' }}</p>
                    @if($isOverdue)
                        <p style="color: #dc2626;"><strong>Overdue by:</strong> {{ $invoice->due_date->diffForHumans() }}</p>
                    @endif
                </div>
            </div>
            
            <!-- Invoice Items -->
            <div class="invoice-details">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60%;">Description</th>
                            <th style="width: 20%;">Quantity</th>
                            <th style="width: 20%; text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Older rows stored `items` as a JSON string (double-encoded by
                            // the array cast), which made count() throw. Normalise to an
                            // array so both shapes render safely.
                            $invoiceItems = $invoice->items;
                            if (is_string($invoiceItems)) {
                                $decoded = json_decode($invoiceItems, true);
                                $invoiceItems = is_array($decoded) ? $decoded : [];
                            }
                            if (!is_array($invoiceItems)) {
                                $invoiceItems = [];
                            }
                        @endphp
                        @if(count($invoiceItems) > 0)
                            @foreach($invoiceItems as $item)
                                <tr>
                                    <td class="description">{{ $item['description'] ?? 'Fee' }}</td>
                                    <td>{{ $item['quantity'] ?? 1 }}</td>
                                    <td class="amount">৳{{ number_format($item['amount'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="description">{{ $invoice->description ?? 'Course Fee / Tuition Fee' }}</td>
                                <td>1</td>
                                <td class="amount">৳{{ number_format($invoice->amount, 2) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            
            <!-- Summary -->
            <div class="invoice-summary">
                <div class="summary-box">
                    <div class="summary-row">
                        <span class="label">Subtotal</span>
                        <span class="value">৳{{ number_format($invoice->amount, 2) }}</span>
                    </div>
                    @if(isset($invoice->discount) && $invoice->discount > 0)
                        <div class="summary-row">
                            <span class="label">Discount</span>
                            <span class="value">-৳{{ number_format($invoice->discount, 2) }}</span>
                        </div>
                    @endif
                    <div class="summary-row total">
                        <span class="label">Total Due</span>
                        <span class="value">৳{{ number_format($invoice->amount - ($invoice->discount ?? 0), 2) }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Instructions -->
            @if($invoice->status === 'pending')
                <div class="payment-instructions">
                    <h4>Payment Instructions</h4>
                    <p>Please make payment before the due date to avoid late fees. You can pay using any of the following methods:</p>
                    
                    <div class="payment-methods">
                        <div class="payment-method">
                            <div class="icon">💵</div>
                            <div class="name">Cash</div>
                        </div>
                        <div class="payment-method">
                            <div class="icon" style="color: #e2136e;">bK</div>
                            <div class="name">bKash</div>
                        </div>
                        <div class="payment-method">
                            <div class="icon" style="color: #f26522;">N</div>
                            <div class="name">Nagad</div>
                        </div>
                        <div class="payment-method">
                            <div class="icon">🏦</div>
                            <div class="name">Bank Transfer</div>
                        </div>
                    </div>
                    
                    @if(config('services.bkash.phone') || config('services.nagad.phone'))
                        <p style="margin-top: 15px;">
                            <strong>Mobile Money:</strong>
                            @if(config('services.bkash.phone'))
                                bKash: {{ config('services.bkash.phone') }}
                            @endif
                            @if(config('services.bkash.phone') && config('services.nagad.phone')) | @endif
                            @if(config('services.nagad.phone'))
                                Nagad: {{ config('services.nagad.phone') }}
                            @endif
                        </p>
                    @endif
                </div>
            @endif
            
            @if($invoice->status === 'paid' && $invoice->payments && $invoice->payments->count() > 0)
                <div class="payment-instructions" style="background: #d1fae5; border-color: #6ee7b7;">
                    <h4 style="color: #065f46;">Payment Received</h4>
                    @foreach($invoice->payments as $payment)
                        <p style="color: #065f46;">
                            <strong>Receipt #{{ $payment->receipt_number }}</strong> - 
                            ৳{{ number_format($payment->amount, 2) }} via {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                            on {{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : $payment->created_at->format('M d, Y') }}
                        </p>
                    @endforeach
                </div>
            @endif
        </div>
        
        <!-- Footer -->
        <div class="invoice-footer">
            <p class="thank-you">Thank you for your business!</p>
            <p>This is a computer-generated invoice. For any queries, please contact the administration office.</p>
            <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
        </div>
    </div>
</body>
</html>

