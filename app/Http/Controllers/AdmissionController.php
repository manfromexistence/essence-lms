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
        $courses = Course::active()->orderBy('delivery_mode')->orderBy('name')->get();

        return view('admission.create', compact('courses', 'selectedMode', 'lockedMode'));
    }

    public function createOffline(): View
    {
        $selectedMode = 'offline';
        $lockedMode = 'offline';
        $courses = Course::active()->where('delivery_mode', 'offline')->orderBy('name')->get();

        return view('admission.create', compact('courses', 'selectedMode', 'lockedMode'));
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $password = Str::password(12);
        $course = null;

        DB::transaction(function () use ($validated, $password, &$course) {
            $course = !empty($validated['course_id']) ? Course::findOrFail($validated['course_id']) : null;
            abort_if($course && $course->delivery_mode !== $validated['admission_mode'], 422, 'Selected course does not match the admission mode.');

            $user = User::create([
                'name' => ($validated['name'] ?? null) ?: $validated['name_bn'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
                'is_active' => false,
                'must_change_password' => true,
            ]);
            if ($role = Role::where('slug', 'student')->first()) {
                $user->roles()->attach($role);
            }

            $validated['user_id'] = $user->id;
            $validated['course_name'] = $course?->name;
            $validated['admission_status'] = 'pending';
            $validated['applied_at'] = now();
            $this->studentService->create($validated);
        });

        // Notify the applicant by email (Brevo)
        try {
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

            app(\App\Services\BrevoEmailService::class)->send(
                $validated['email'],
                'Admission Received — ' . $courseName,
                $html,
                ['type' => 'admission']
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Admission email failed', ['error' => $e->getMessage()]);
        }

        return redirect()->route('login')->with(
            'success',
            'Admission submitted. Your account will be activated after office approval.'
        );
    }
}
