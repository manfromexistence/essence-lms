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

class AdmissionController extends Controller
{
    public function __construct(protected StudentService $studentService) {}

    public function create(): View
    {
        $courses = Course::active()->orderBy('delivery_mode')->orderBy('name')->get();

        return view('admission.create', compact('courses'));
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $password = Str::password(12);

        DB::transaction(function () use ($validated, $password) {
            $course = !empty($validated['course_id']) ? Course::findOrFail($validated['course_id']) : null;
            abort_if($course && $course->delivery_mode !== $validated['admission_mode'], 422, 'Selected course does not match the admission mode.');

            $user = User::create([
                'name' => ($validated['name'] ?? null) ?: $validated['name_bn'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
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

        return redirect()->route('login')->with([
            'success' => 'Admission submitted. The office will review your application.',
            'generated_password' => $password,
        ]);
    }
}
