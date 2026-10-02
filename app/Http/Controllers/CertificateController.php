<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateVerification;
use App\Models\Student;
use App\Services\SettingsService;
use App\Services\StudentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Public, unauthenticated certificate and student verification.
 *
 * Reached by scanning the QR code printed on a certificate, or by entering the
 * verification code shown on its face. Both exist so that a printed certificate
 * can be checked without contacting the institute.
 *
 * QR rendering deliberately does not happen here. This controller must stay
 * constructible without the GD extension, because code-based verification has
 * no need of it and a hard dependency would take the page down wherever GD is
 * missing.
 */
class CertificateController extends Controller
{
    public function __construct(
        protected StudentVerificationService $verification,
    ) {}

    public function index()
    {
        $student = auth()->user()->student;

        if (! $student) {
            return view('student.certificates.index', ['certificates' => collect()]);
        }

        $certificates = Certificate::with('course')
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->latest('issued_at')
            ->get();

        return view('student.certificates.index', compact('certificates'));
    }

    public function show(Certificate $certificate)
    {
        $student = auth()->user()->student;

        abort_unless($student, 404, 'Student profile not found.');
        abort_unless($certificate->student_id === $student->id, 403);
        abort_unless($certificate->status === 'active', 404, 'This certificate has been revoked.');

        $certificate->load(['student.user', 'course', 'issuer']);

        return view('certificates.show', compact('certificate'));
    }

    /**
     * Verify a certificate by the code printed on it.
     *
     * Codes are stored upper-case but people retype them from paper in whatever
     * case their keyboard produced, so the lookup is case-insensitive.
     */
    public function verify(Request $request, ?string $code = null)
    {
        $code = trim($code ?: (string) $request->string('code'));

        $certificate = $code !== ''
            ? Certificate::with(['student.user', 'course'])
                ->whereRaw('UPPER(verification_code) = ?', [mb_strtoupper($code)])
                ->first()
            : null;

        if ($certificate) {
            $this->recordScan($request, $certificate);
        }

        return view('certificates.verify', compact('certificate', 'code'));
    }

    /**
     * The public profile a certificate's QR code resolves to.
     *
     * Identified by an unguessable per-student token rather than a registration
     * number, because registration numbers are sequential and would let anyone
     * walk the whole student body by incrementing a number.
     */
    public function studentProfile(string $token, Request $request): View
    {
        $student = Student::where('verification_token', $token)
            ->with(['user', 'batch.course'])
            ->first();

        // A miss is reported the same way as a hit for the existence of the
        // token: both are simply "no such student".
        abort_if($student === null, 404, 'No student matches that verification link.');

        $profile = $this->verification->profile($student);

        $this->recordScan($request, null, $student);

        return view('certificates.student-profile', [
            'student' => $student,
            'profile' => $profile,
            'institution' => app(SettingsService::class)->get('institution_name', config('app.name')),
        ]);
    }

    /**
     * Note that a certificate's QR was scanned.
     *
     * Recording the certificate makes verification auditable. Failures are
     * swallowed: losing an audit row must never stop a member of the public from
     * checking a certificate.
     */
    private function recordScan(Request $request, ?Certificate $certificate, ?Student $student = null): void
    {
        try {
            $certificateId = $certificate?->id;

            // A QR scan resolves to the student's profile rather than to a single
            // certificate, so attribute it to the student's most recent active
            // certificate when one exists.
            if ($certificateId === null && $student) {
                $certificateId = $student->certificates()
                    ->where('status', 'active')
                    ->latest('issued_at')
                    ->value('id');
            }

            if ($certificateId === null) {
                return;
            }

            CertificateVerification::create([
                'certificate_id' => $certificateId,
                'verified_at' => now(),
                'ip_address' => $request->ip(),
                // Truncated: the column is a text field but there is no value in
                // storing a kilobyte of header per scan.
                'user_agent' => substr((string) $request->userAgent(), 0, 512) ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Could not record certificate verification.', ['error' => $e->getMessage()]);
        }
    }
}
