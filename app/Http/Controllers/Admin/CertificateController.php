<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CourseEnrollment;
use App\Services\CertificateService;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(private CertificateService $certificateService) {}

    public function index()
    {
        $certificates = Certificate::with(['student.user', 'course'])
            ->latest('issued_at')
            ->paginate(20);

        // Only include enrollments whose student and course can be resolved,
        // so the "issue" dropdown never tries to read a missing relation.
        $enrollments = CourseEnrollment::with(['student.user', 'course'])
            ->latest()
            ->get()
            ->filter(fn ($e) => $e->student?->user && $e->course);

        return view('dashboard.certificates.index', compact('certificates', 'enrollments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['enrollment_id' => 'required|exists:course_enrollments,id']);
        $enrollment = CourseEnrollment::with(['student', 'course'])->findOrFail($data['enrollment_id']);
        $certificate = $this->certificateService->issue($enrollment->student, $enrollment->course, $request->user());

        return back()->with('success', "Certificate {$certificate->certificate_number} is ready.");
    }

    /**
     * Update certificate details (grade, issued date, status).
     */
    public function update(Request $request, Certificate $certificate)
    {
        $data = $request->validate([
            'grade' => 'nullable|string|max:10',
            'issued_at' => 'nullable|date',
            'status' => 'required|in:active,revoked',
        ]);

        $updateData = [
            'grade' => $data['grade'] ?? $certificate->grade,
            'issued_at' => $data['issued_at'] ?? $certificate->issued_at,
        ];

        if ($data['status'] === 'revoked' && $certificate->status !== 'revoked') {
            $updateData['status'] = 'revoked';
            $updateData['revoked_at'] = now();
            $updateData['revocation_reason'] = $request->input('revocation_reason') ?: 'Updated by admin';
        } elseif ($data['status'] === 'active' && $certificate->status !== 'active') {
            $updateData['status'] = 'active';
            $updateData['revoked_at'] = null;
            $updateData['revocation_reason'] = null;
        }

        $certificate->update($updateData);

        return back()->with('success', 'Certificate updated successfully.');
    }

    /**
     * Email the certificate (with verification link) to the student via Brevo.
     */
    public function email(Request $request, Certificate $certificate)
    {
        $certificate->load(['student.user', 'course']);

        if (!$certificate->student?->user?->email) {
            return back()->with('error', 'Student has no email address on file.');
        }

        $studentName = $certificate->student->user->name ?? 'Student';
        $courseName = $certificate->course?->name ?? 'your course';
        $verifyUrl = $certificate->verification_code
            ? route('certificates.verify', $certificate->verification_code)
            : null;

        $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;">'
            . '<div style="background:#168536;padding:24px;border-radius:12px 12px 0 0;text-align:center;">'
            . '<h2 style="color:#fff;margin:0;">Congratulations, ' . e($studentName) . '!</h2></div>'
            . '<div style="border:1px solid #e5e7eb;border-top:0;padding:32px;border-radius:0 0 12px 12px;">'
            . '<p>You have successfully completed <strong>' . e($courseName) . '</strong> at Dhaka IT Institute.</p>'
            . '<p>Your certificate number is <strong>' . e($certificate->certificate_number) . '</strong>.</p>'
            . ($verifyUrl ? '<p style="margin-top:24px;"><a href="' . e($verifyUrl) . '" style="background:#168536;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;">View / Verify Certificate</a></p>' : '')
            . '<p style="margin-top:24px;color:#6b7280;font-size:13px;">This certificate is verifiable online. Keep it safe and share it with pride!</p>'
            . '</div></div>';

        $log = app(\App\Services\BrevoEmailService::class)->send(
            $certificate->student->user->email,
            "🎓 Your Course Certificate — {$courseName}",
            $html,
            ['type' => 'certificate', 'related' => $certificate->student]
        );

        if ($log->isSent()) {
            return back()->with('success', "Certificate emailed to {$certificate->student->user->email}.");
        }

        return back()->with('error', 'Failed to email certificate: ' . ($log->error_message ?? 'unknown error'));
    }

    public function revoke(Request $request, Certificate $certificate)
    {
        $data = $request->validate(['reason' => 'required|string|max:500']);
        $certificate->update(['status' => 'revoked', 'revoked_at' => now(), 'revocation_reason' => $data['reason']]);

        return back()->with('success', 'Certificate revoked.');
    }
}
