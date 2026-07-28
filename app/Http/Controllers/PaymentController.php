<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Batch;
use App\Models\CourseEnrollment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    /**
     * Show payment form for course enrollment.
     * Task 12.1
     * Requirements: 11.1, 11.2
     */
    public function showForm(Course $course): View
    {
        abort_unless(Auth::user()?->isStudent() && Auth::user()->student, 403);

        if (CourseEnrollment::where('student_id', Auth::user()->student->id)->where('course_id', $course->id)->exists()) {
            abort(409, 'You are already enrolled in this course.');
        }

        // Load payment methods from config
        $paymentMethods = config('payment-methods.methods');
        
        return view('student.payment-form', compact('course', 'paymentMethods'));
    }

    /**
     * Submit payment with screenshot.
     * Task 12.3
     * Requirements: 12.1, 12.2, 12.3, 12.4
     */
    public function submit(Request $request): RedirectResponse
    {
        // Validate inputs
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'payment_method' => 'required|in:bkash,nagad,rocket,bank_transfer',
            'transaction_id' => [
                'required', 'string', 'max:255',
                Rule::unique('payments')->where(fn ($query) => $query->where('payment_method', $request->payment_method)),
            ],
            'sender_number' => 'required_if:payment_method,bkash|nullable|regex:/^01[3-9][0-9]{8}$/',
            'screenshot' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
            'notes' => 'nullable|string|max:1000',
        ]);

        // Get authenticated student
        $student = Auth::user()->student;
        
        if (!$student) {
            return redirect()->back()->with('error', 'Student profile not found.');
        }

        $course = Course::active()->findOrFail($validated['course_id']);

        if (CourseEnrollment::where('student_id', $student->id)->where('course_id', $course->id)->exists()) {
            return back()->with('error', 'You are already enrolled in this course.');
        }

        if (Payment::where('student_id', $student->id)->where('course_id', $course->id)
            ->where('status', Payment::STATUS_PENDING)->exists()) {
            return back()->with('error', 'You already have a payment awaiting review for this course.');
        }

        // Store screenshot
        $screenshotPath = $request->file('screenshot')->store('payment-screenshots', 'public');

        // Create payment record with pending status
        $payment = Payment::create([
            'student_id' => $student->id,
            'course_id' => $validated['course_id'],
            'payment_method' => $validated['payment_method'],
            'transaction_id' => strtoupper(trim($validated['transaction_id'])),
            'sender_number' => $validated['sender_number'] ?? null,
            'transaction_reference' => $validated['payment_method'] . ':' . strtoupper(trim($validated['transaction_id'])),
            'screenshot_path' => $screenshotPath,
            'amount' => $course->price,
            'payment_date' => today(),
            'status' => Payment::STATUS_PENDING,
            'submitted_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        User::whereHas('roles', fn ($query) => $query->whereIn('slug', ['admin', 'super-admin']))
            ->pluck('id')->each(function ($userId) use ($payment, $course, $student) {
                Notification::create([
                    'user_id' => $userId,
                    'user_type' => 'admin',
                    'type' => 'course_payment_submitted',
                    'title' => 'New course payment to review',
                    'message' => "{$student->user->name} submitted payment for {$course->name} ({$payment->transaction_id}).",
                    'data' => ['payment_id' => $payment->id, 'course_id' => $course->id],
                    'action_url' => route('payment.review.detail', $payment),
                ]);
            });

        return redirect()->route('student.payment.dashboard')
            ->with('success', 'Payment submitted successfully. Your payment is under review.');
    }

    /**
     * Display list of pending payments for admin review.
     * Task 13.1
     * Requirements: 13.1
     */
    public function reviewList(): View
    {
        // Fetch all pending payments with relationships
        $pendingPayments = Payment::with(['student.user', 'course'])
            ->where('status', Payment::STATUS_PENDING)
            ->orderBy('submitted_at', 'desc')
            ->paginate(20);

        return view('admin.payment-review', compact('pendingPayments'));
    }

    /**
     * Display payment detail for admin review.
     * Task 13.3
     * Requirements: 13.2
     */
    public function reviewDetail(Payment $payment): View
    {
        // Load relationships
        $payment->load(['student.user', 'course']);

        return view('admin.payment-detail', compact('payment'));
    }

    /**
     * Approve payment and enroll student in course.
     * Task 13.5
     * Requirements: 13.3
     */
    public function approve(Payment $payment, Request $request): RedirectResponse
    {
        // Validate payment is pending
        if (!$payment->isPending()) {
            return redirect()->back()->with('error', 'Payment has already been processed.');
        }

        $request->validate(['admin_notes' => 'nullable|string|max:1000']);

        DB::transaction(function () use ($payment, $request) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            abort_unless($payment->isPending(), 409, 'Payment has already been processed.');

            $batch = Batch::where('course_id', $payment->course_id)->active()
                ->where(function ($query) {
                    $query->whereNull('max_students')
                        ->orWhereRaw('(SELECT COUNT(*) FROM students WHERE students.batch_id = batches.id) < batches.max_students');
                })->first();

            $payment->update([
                'status' => Payment::STATUS_APPROVED,
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id(),
                'admin_notes' => $request->input('admin_notes'),
            ]);

            CourseEnrollment::updateOrCreate(
                ['student_id' => $payment->student_id, 'course_id' => $payment->course_id],
                ['batch_id' => $batch?->id, 'payment_id' => $payment->id, 'enrolled_at' => now()]
            );
            if ($batch && !$payment->student->batch_id) {
                $payment->student->update(['batch_id' => $batch->id]);
            }

            Notification::create([
                'user_id' => $payment->student->user_id,
                'user_type' => 'student',
                'type' => 'course_payment_approved',
                'title' => 'Course payment approved',
                'message' => "Your payment for {$payment->course->name} was verified. You now have course access.",
                'data' => ['payment_id' => $payment->id, 'course_id' => $payment->course_id],
                'action_url' => route('student.course.watch', $payment->course_id),
            ]);
        });

        return redirect()->route('payment.review.list')
            ->with('success', 'Payment approved and student enrolled successfully.');
    }

    /**
     * Reject payment.
     * Task 13.6
     * Requirements: 13.4
     */
    public function reject(Payment $payment, Request $request): RedirectResponse
    {
        // Validate payment is pending
        if (!$payment->isPending()) {
            return redirect()->back()->with('error', 'Payment has already been processed.');
        }

        // Validate admin notes are provided
        $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        // Update payment status
        $payment->update([
            'status' => Payment::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
            'admin_notes' => $request->input('admin_notes'),
        ]);

        Notification::create([
            'user_id' => $payment->student->user_id,
            'user_type' => 'student',
            'type' => 'course_payment_rejected',
            'title' => 'Course payment needs attention',
            'message' => "Your payment for {$payment->course->name} was not approved: {$request->input('admin_notes')}",
            'data' => ['payment_id' => $payment->id, 'course_id' => $payment->course_id],
            'action_url' => route('student.payment.dashboard'),
        ]);

        return redirect()->route('payment.review.list')
            ->with('success', 'Payment rejected successfully.');
    }

    /**
     * Display student payment dashboard.
     * Task 14.1
     * Requirements: 14.1, 14.2, 14.3
     */
    public function dashboard(): View
    {
        // Get authenticated student
        $student = Auth::user()->student;
        
        if (!$student) {
            abort(403, 'Student profile not found.');
        }

        // Fetch all payments for student
        $payments = Payment::with('course')
            ->where('student_id', $student->id)
            ->orderBy('submitted_at', 'desc')
            ->get();

        // Group payments by course
        $enrollments = [];
        $paymentsByCourse = $payments->groupBy('course_id');

        foreach ($paymentsByCourse as $courseId => $coursePayments) {
            $course = $coursePayments->first()->course;
            $totalFee = $course->price ?? 0;
            $amountDeposited = $coursePayments->where('status', Payment::STATUS_APPROVED)->sum('amount');
            $pendingAmount = $coursePayments->where('status', Payment::STATUS_PENDING)->sum('amount');

            $enrollments[] = [
                'course' => $course,
                'total_fee' => $totalFee,
                'amount_deposited' => $amountDeposited,
                'pending_amount' => $pendingAmount,
                'payments' => $coursePayments,
            ];
        }

        // Calculate summary
        $totalCourses = count($enrollments);
        $totalFees = collect($enrollments)->sum('total_fee');
        $totalPaid = $payments->where('status', Payment::STATUS_APPROVED)->sum('amount');
        $totalPending = $payments->where('status', Payment::STATUS_PENDING)->sum('amount');

        return view('student.payment-dashboard', compact(
            'enrollments',
            'payments',
            'totalCourses',
            'totalFees',
            'totalPaid',
            'totalPending'
        ));
    }
}
