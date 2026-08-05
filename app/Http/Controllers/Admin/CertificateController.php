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
        $certificates = Certificate::with(['student.user', 'course'])->latest('issued_at')->paginate(20);
        $enrollments = CourseEnrollment::with(['student.user', 'course'])->latest()->get();

        return view('dashboard.certificates.index', compact('certificates', 'enrollments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['enrollment_id' => 'required|exists:course_enrollments,id']);
        $enrollment = CourseEnrollment::with(['student', 'course'])->findOrFail($data['enrollment_id']);
        $certificate = $this->certificateService->issue($enrollment->student, $enrollment->course, $request->user());

        return back()->with('success', "Certificate {$certificate->certificate_number} is ready.");
    }

    public function revoke(Request $request, Certificate $certificate)
    {
        $data = $request->validate(['reason' => 'required|string|max:500']);
        $certificate->update(['status' => 'revoked', 'revoked_at' => now(), 'revocation_reason' => $data['reason']]);

        return back()->with('success', 'Certificate revoked.');
    }
}
