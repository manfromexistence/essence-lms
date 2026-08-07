<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    public function index()
    {
        $student = Auth::user()->student;

        // A role-less or admin user without a student profile should not get a
        // bare 403 — show the empty state instead, like the other student pages.
        if (!$student) {
            $certificates = collect();
            return view('student.certificates.index', compact('certificates'));
        }

        $certificates = Certificate::with('course')->where('student_id', $student->id)->latest('issued_at')->get();

        return view('student.certificates.index', compact('certificates'));
    }

    public function show(Certificate $certificate)
    {
        $student = Auth::user()->student;

        // Only block when there is a real ownership violation; users without a
        // student profile simply cannot own a certificate.
        if (!$student) {
            abort(404);
        }

        abort_unless($certificate->student_id === $student->id, 403);
        $certificate->load(['student.user', 'course', 'issuer']);

        return view('certificates.show', compact('certificate'));
    }

    public function verify(Request $request, ?string $code = null)
    {
        $code = $code ?: $request->string('code')->trim()->toString();
        $certificate = $code !== ''
            ? Certificate::with(['student.user', 'course'])->where('verification_code', $code)->first()
            : null;

        return view('certificates.verify', compact('certificate', 'code'));
    }
}
