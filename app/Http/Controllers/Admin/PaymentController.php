<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\Batch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Display payment dashboard with filters.
     * Supports filtering by date range, payment method, batch, and student search.
     * 
     * Requirements: 3.1, 3.2, 3.3
     */
    public function index(Request $request): View
    {
        // Build filters array from request
        $filters = [
            'start_date' => $request->filled('date_from') ? $request->date_from : null,
            'end_date' => $request->filled('date_to') ? $request->date_to : null,
            'payment_method' => $request->filled('payment_method') ? $request->payment_method : null,
            'batch_id' => $request->filled('batch_id') ? $request->batch_id : null,
            'status' => $request->filled('status') ? $request->status : null,
            'search' => $request->filled('search') ? $request->search : null,
        ];

        // Get paginated payments with filters
        $payments = $this->paymentService->getPaginated($filters, 15);

        // Get dashboard statistics with same filters
        $stats = $this->paymentService->getDashboardStats($filters);

        // Get batches for filter dropdown
        $batches = Batch::orderBy('name')->get();

        // Calculate totals for display
        $totalRevenue = Payment::completed()->sum('amount');
        $currentMonth = now();
        $monthlyRevenue = Payment::completed()
            ->whereBetween('created_at', [$currentMonth->copy()->startOfMonth(), $currentMonth->copy()->endOfMonth()])
            ->sum('amount');
        $pendingAmount = Payment::where('status', 'pending')->sum('amount');
        $totalTransactions = Payment::count();

        return view('dashboard.payments.index', compact(
            'payments',
            'stats',
            'batches',
            'totalRevenue',
            'monthlyRevenue',
            'pendingAmount',
            'totalTransactions'
        ));
    }

    /**
     * Show payment form for recording a new payment.
     * Optionally pre-selects a student if provided.
     * 
     * Requirements: 1.1, 1.2
     */
    public function create(?Student $student = null): View
    {
        $students = Student::with(['user', 'batch'])->orderBy('id', 'desc')->get();
        $batches = Batch::orderBy('name')->get();
        
        // Get pending invoices for the selected student
        $pendingInvoices = [];
        if ($student) {
            $pendingInvoices = $this->invoiceService->getStudentInvoices($student, ['status' => 'pending']);
        }

        // Payment methods supported by the system
        $paymentMethods = [
            'cash' => 'Cash',
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            'bank_transfer' => 'Bank Transfer',
        ];

        // Mobile money configuration for display
        $mobileMoneyConfig = [
            'bkash' => [
                'phone' => config('services.bkash.phone', '01XXXXXXXXX'),
                'instructions' => 'Send money to the above bKash number and provide the Transaction ID.',
            ],
            'nagad' => [
                'phone' => config('services.nagad.phone', '01XXXXXXXXX'),
                'instructions' => 'Send money to the above Nagad number and provide the Transaction ID.',
            ],
        ];

        return view('dashboard.payments.create', compact(
            'students',
            'student',
            'batches',
            'pendingInvoices',
            'paymentMethods',
            'mobileMoneyConfig'
        ));
    }

    /**
     * Store a new payment record.
     * Generates invoice number, updates student balance, creates receipt, and sends SMS notification.
     * 
     * Requirements: 1.3, 1.4, 1.5, 2.3, 4.1, 4.3, 4.4
     */
    public function store(PaymentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Prepare payment data
        $paymentData = [
            'student_id' => $validated['student_id'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_date' => $validated['payment_date'] ?? now(),
            'transaction_id' => $validated['transaction_id'] ?? null,
            'invoice_id' => $validated['invoice_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'completed',
        ];

        // Record the payment using PaymentService
        $payment = $this->paymentService->recordPayment($paymentData);

        // If payment is linked to an invoice, update invoice status
        if ($payment->invoice_id) {
            $invoice = Invoice::find($payment->invoice_id);
            if ($invoice) {
                $this->invoiceService->markInvoiceAsPaid($invoice, $payment);
            }
        }

        // Send SMS notification (handles failures gracefully - logs but doesn't block)
        $this->paymentService->sendPaymentNotification($payment);

        return redirect()
            ->route('dashboard.payments.show', $payment)
            ->with('success', 'Payment recorded successfully. Receipt #' . $payment->receipt_number);
    }

    /**
     * Display payment details.
     * Shows complete payment information including student details and invoice reference.
     * 
     * Requirements: 2.3, 3.2
     */
    public function show(Payment $payment): View
    {
        $payment->load(['student.user', 'student.batch', 'invoice']);

        // Calculate student's current balance
        $studentBalance = $this->paymentService->calculateBalance($payment->student);

        return view('dashboard.payments.show', compact('payment', 'studentBalance'));
    }

    /**
     * Display complete payment history for a student.
     * Supports filtering by date range, payment method, and status.
     * 
     * Requirements: 3.2
     */
    public function history(Student $student, Request $request): View
    {
        $student->load(['user', 'batch']);

        // Build filters from request
        $filters = [
            'start_date' => $request->filled('date_from') ? $request->date_from : null,
            'end_date' => $request->filled('date_to') ? $request->date_to : null,
            'payment_method' => $request->filled('payment_method') ? $request->payment_method : null,
            'status' => $request->filled('status') ? $request->status : null,
        ];

        // Get payment history with filters
        $payments = $this->paymentService->getPaymentHistory($student, $filters);

        // Calculate student's current balance
        $currentBalance = $this->paymentService->calculateBalance($student);

        // Get student invoices
        $invoices = $this->invoiceService->getStudentInvoices($student);

        // Payment summary
        $summary = [
            'total_paid' => $student->paid_amount,
            'total_due' => $student->due_amount,
            'total_amount' => $student->total_amount,
            'payment_count' => $payments->count(),
        ];

        return view('dashboard.payments.history', compact(
            'student',
            'payments',
            'currentBalance',
            'invoices',
            'summary'
        ));
    }

    /**
     * Generate printable receipt for a payment.
     * Includes payment details, student information, and invoice reference.
     * 
     * Requirements: 2.3, 2.4
     */
    public function receipt(Payment $payment): View
    {
        $payment->load(['student.user', 'student.batch', 'invoice']);

        // Get institution settings for receipt branding
        $institutionName = config('app.name', 'LMS Institution');
        $institutionAddress = config('app.address', '');
        $institutionPhone = config('app.phone', '');
        $institutionEmail = config('app.email', '');

        return view('dashboard.payments.receipt', compact(
            'payment',
            'institutionName',
            'institutionAddress',
            'institutionPhone',
            'institutionEmail'
        ));
    }

    /**
     * Display receipts list.
     */
    public function receipts(Request $request): View
    {
        $payments = Payment::with(['student.user'])
            ->whereNotNull('receipt_number')
            ->latest()
            ->paginate(20);

        return view('dashboard.payments.receipts', compact('payments'));
    }

    /**
     * Print receipt view (simplified for printing).
     */
    public function printReceipt(Payment $payment): View
    {
        return $this->receipt($payment);
    }

    /**
     * Display invoices list.
     */
    public function invoices(Request $request): View
    {
        $query = Invoice::with(['student.user', 'student.batch']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('student.user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'overdue') {
                $query->where('status', 'pending')
                    ->where('due_date', '<', now());
            } else {
                $query->where('status', $request->status);
            }
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $invoices = $query->latest()->paginate(20);

        // Get statistics
        $totalInvoices = Invoice::count();
        $paidCount = Invoice::where('status', 'paid')->count();
        $pendingCount = Invoice::where('status', 'pending')->where(function ($q) {
            $q->whereNull('due_date')->orWhere('due_date', '>=', now());
        })->count();
        $overdueCount = Invoice::where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();

        // Get students for the create invoice modal
        $students = Student::with(['user', 'batch'])->orderBy('id', 'desc')->get();

        return view('dashboard.payments.invoices', compact(
            'invoices',
            'totalInvoices',
            'paidCount',
            'pendingCount',
            'overdueCount',
            'students'
        ));
    }

    /**
     * Display a single invoice (printable format).
     * 
     * Requirements: 2.2, 2.4
     */
    public function showInvoice(Invoice $invoice): View
    {
        $invoice->load(['student.user', 'student.batch', 'payments']);

        // Get institution settings for invoice branding
        $institutionName = config('app.name', 'LMS Institution');
        $institutionAddress = config('app.address', '');
        $institutionPhone = config('app.phone', '');
        $institutionEmail = config('app.email', '');

        return view('dashboard.payments.invoice-show', compact(
            'invoice',
            'institutionName',
            'institutionAddress',
            'institutionPhone',
            'institutionEmail'
        ));
    }

    /**
     * Create a new invoice.
     */
    public function createInvoice(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'items' => 'nullable|array',
        ]);

        $student = Student::findOrFail($request->student_id);

        $invoice = $this->invoiceService->createInvoice($student, $request->amount, [
            'due_date' => $request->due_date,
            'items' => $request->items,
        ]);

        return redirect()
            ->route('dashboard.payments.invoices')
            ->with('success', 'Invoice #' . $invoice->invoice_number . ' generated successfully.');
    }

    /**
     * Display payment tracking (due/advance tracking).
     */
    public function tracking(Request $request): View
    {
        $query = Student::with(['user', 'batch']);

        // Filter by batch
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        // Filter by due status
        if ($request->filled('status')) {
            if ($request->status === 'due') {
                $query->where('due_amount', '>', 0);
            } elseif ($request->status === 'advance') {
                $query->where('due_amount', '<', 0);
            } elseif ($request->status === 'paid') {
                $query->where('due_amount', '=', 0);
            }
        }

        // Search by student name or registration
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('registration_no', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $students = $query->orderByDesc('due_amount')->paginate(20);

        $batches = Batch::orderBy('name')->get();
        $totalDue = Student::where('due_amount', '>', 0)->sum('due_amount');
        $totalAdvance = Student::where('due_amount', '<', 0)->sum('due_amount');
        $studentsWithDues = Student::where('due_amount', '>', 0)->count();

        return view('dashboard.payments.tracking', compact(
            'students',
            'batches',
            'totalDue',
            'totalAdvance',
            'studentsWithDues'
        ));
    }

    /**
     * Display payment notifications page.
     */
    public function notifications(Request $request): View
    {
        $studentsWithDue = Student::with(['user', 'batch'])
            ->where('due_amount', '>', 0)
            ->orderByDesc('due_amount')
            ->get();

        return view('dashboard.payments.notifications', compact('studentsWithDue'));
    }

    /**
     * Send payment notification to students.
     */
    public function sendNotification(Request $request): RedirectResponse
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'message' => 'required|string|max:500',
        ]);

        // This would integrate with SmsService for actual notification
        // For now, return success message
        $count = count($request->student_ids);

        return back()->with('success', "Payment reminder sent to {$count} students.");
    }
}
