<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Services\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\Request;

class AdmissionController extends Controller
{
    public function __construct(protected StudentService $studentService) {}

    public function create(Request $request): View
    {
        $selectedMode = in_array($request->input('mode'), ['online', 'offline'], true)
            ? $request->input('mode')
            : 'offline';
        $lockedMode = null;
        // Use enrollable() (active + draft) so a newly uploaded course shows up
        // immediately instead of being hidden and blocking the required field.
        $courses = Course::enrollable()->orderBy('delivery_mode')->orderBy('name')->get();

        return view('admission.create', compact('courses', 'selectedMode', 'lockedMode'));
    }

    public function createOffline(): View
    {
        $selectedMode = 'offline';
        $lockedMode = 'offline';
        $courses = Course::enrollable()->where('delivery_mode', 'offline')->orderBy('name')->get();

        return view('admission.create', compact('courses', 'selectedMode', 'lockedMode'));
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // The applicant no longer chooses a password. We store an unguessable
        // random placeholder that nobody knows; a real, usable password is
        // generated and emailed only when an administrator approves the
        // application (see StudentController::updateAdmissionStatus). The
        // account starts inactive, so the placeholder can never be used.
        $placeholder = Str::random(64);
        $course = null;

        DB::transaction(function () use ($validated, $placeholder, &$course) {
            $course = !empty($validated['course_id']) ? Course::findOrFail($validated['course_id']) : null;
            abort_if($course && $course->delivery_mode !== $validated['admission_mode'], 422, 'Selected course does not match the admission mode.');

            $user = User::create([
                'name' => ($validated['name'] ?? null) ?: $validated['name_bn'],
                'email' => $validated['email'],
                'password' => Hash::make($placeholder),
                'is_active' => false,
                'must_change_password' => true,
            ]);
            $studentRole = Role::where('slug', 'student')->first();
            abort_unless($studentRole, 500, 'The "student" role is missing. Run the role seeder first.');
            $user->roles()->attach($studentRole->id);

            $validated['user_id'] = $user->id;
            $validated['course_name'] = $course?->name;
            $validated['admission_status'] = 'pending';
            $validated['status'] = 'pending';
            $validated['applied_at'] = now();
            $this->studentService->create($validated);
        });

        // Notify the applicant by email (Brevo) — queued so the request
        // returns immediately even when the mail API is slow.
        $applicantName = ($validated['name'] ?? null) ?: ($validated['name_bn'] ?? 'Applicant');
        $courseName = $course?->name ?? 'your selected course';
        $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;">'
            . '<div style="background:#168536;padding:24px;border-radius:12px 12px 0 0;text-align:center;">'
            . '<h2 style="color:#fff;margin:0;">Admission Received</h2></div>'
            . '<div style="border:1px solid #e5e7eb;border-top:0;padding:32px;border-radius:0 0 12px 12px;">'
            . '<p>Dear <strong>' . e($applicantName) . '</strong>,</p>'
            . '<p>Thank you for applying to <strong>' . e($courseName) . '</strong> at Dhaka IT Institute.</p>'
            . '<p>Your application is now under review. Once your admission is approved by our office, you will receive an email with your login details and course access.</p>'
            . '<p style="margin-top:24px;color:#6b7280;font-size:13px;">Dhaka IT Institute — Let\'s Build Your Dream</p>'
            . '</div></div>';

        \App\Jobs\SendEmailJob::dispatch(
            $validated['email'],
            'Admission Received — ' . $courseName,
            $html,
            ['type' => 'admission']
        );

        return redirect()->route('login')->with(
            'success',
            'Admission submitted. Your account will be activated after office approval.'
        );
    }
}
