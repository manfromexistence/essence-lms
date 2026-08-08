<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Services\StudentService;
use App\Services\StudentIdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class StudentController extends Controller
{
    public function __construct(
        protected StudentService $studentService,
        protected StudentIdGenerator $idGenerator
    ) {}

    /**
     * Display a listing of students.
     */
    public function index(Request $request): View
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'search_field' => 'nullable|in:all,name,number,batch,area,blood_group',
            'mode' => 'nullable|in:online,offline',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'year' => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
            'class' => 'nullable|string|max:50',
            'sort' => 'nullable|in:created_at,name_bn,phone,blood_group,admission_mode,status',
            'direction' => 'nullable|in:asc,desc',
        ]);

        $filters = [
            'search' => $request->input('search'),
            'search_field' => $request->input('search_field', 'all'),
            'mode' => $request->input('mode'),
            'year' => $request->input('year'),
            'batch_id' => $request->input('batch_id'),
            'class' => $request->input('class'),
            'with_dues' => $request->boolean('with_dues'),
            'featured' => $request->boolean('featured'),
            'sort_by' => $request->input('sort', 'created_at'),
            'sort_dir' => $request->input('direction', 'desc'),
        ];

        $students = $this->studentService->getPaginated($filters, 15);
        $batches = Batch::select('id', 'name', 'code')->orderBy('name')->get();
        $years = range(date('Y'), date('Y') - 5);
        $classes = range(1, 12);
        $studentCounts = [
            'all' => Student::count(),
            'online' => Student::where('admission_mode', 'online')->count(),
            'offline' => Student::where('admission_mode', 'offline')->count(),
        ];

        return view('dashboard.students.index', compact('students', 'batches', 'years', 'classes', 'filters', 'studentCounts'));
    }

    /**
     * Show the form for creating a new student.
     */
    public function create(Request $request): View
    {
        $batches = Batch::select('id', 'name', 'code', 'course_id', 'schedule')->get();
        $courses = Course::active()->orderBy('delivery_mode')->orderBy('name')->get();
        $classes = range(1, 12);
        $defaultMode = in_array($request->input('mode'), ['online', 'offline'], true)
            ? $request->input('mode')
            : 'offline';

        return view('dashboard.students.create', compact('batches', 'courses', 'classes', 'defaultMode'));
    }

    /**
     * Store a newly created student.
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $course = !empty($validated['course_id']) ? Course::findOrFail($validated['course_id']) : null;
        if ($course && $course->delivery_mode !== $validated['admission_mode']) {
            return back()->withInput()->withErrors([
                'course_id' => 'The selected course does not match the chosen online/offline mode.',
            ]);
        }

        if (!empty($validated['batch_id'])) {
            $batch = Batch::with('course')->findOrFail($validated['batch_id']);
            if ($course && $batch->course_id !== $course->id) {
                return back()->withInput()->withErrors(['batch_id' => 'The selected batch does not belong to this course.']);
            }
            if ($batch->course && $batch->course->delivery_mode !== $validated['admission_mode']) {
                return back()->withInput()->withErrors(['batch_id' => 'The selected batch does not match the chosen online/offline mode.']);
            }
        }

        // Use a password the admin supplied, otherwise generate one and have
        // the broker email a reset link so the student sets it themselves.
        $password = $validated['password'] ?? \Illuminate\Support\Str::random(12);
        $hasOwnPassword = !empty($validated['password']);

        // Handle profile image - file upload takes priority over URL
        $imagePath = $this->handleImageInput($request, 'profile_image', 'students/profiles');
        if ($imagePath) {
            $validated['profile_image'] = $imagePath;
        }

        // Handle course name from course_id
        if ($request->filled('course_id')) {
            $course = Course::find($request->course_id);
            if ($course) {
                $validated['course_name'] = $course->name;
            }
        }

        DB::transaction(function () use ($validated, $request, $password, $hasOwnPassword) {
            $user = User::create([
                'name' => $request->input('name') ?: $validated['name_bn'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
                'is_active' => true,
                'must_change_password' => ! $hasOwnPassword,
            ]);

            $studentRole = Role::where('slug', 'student')->first();
            abort_unless($studentRole, 500, 'The "student" role is missing. Run the role seeder first.');
            $user->roles()->attach($studentRole->id);

            $validated['user_id'] = $user->id;
            $validated['applied_at'] = now();
            $approved = !empty($validated['batch_id']);
            $validated['admission_status'] = $approved ? 'approved' : 'pending';
            $validated['status'] = $approved ? 'active' : 'pending';
            $this->studentService->create($validated);
        });

        // Only email a reset link when the student was NOT given a known password.
        if (! $hasOwnPassword) {
            Password::sendResetLink(['email' => $validated['email']]);
            $message = 'Student created successfully. A secure password setup link was sent by email.';
        } else {
            $message = 'Student created successfully. The student can log in with their email and the password you set.';
        }

        return redirect()->route('dashboard.students.index')
            ->with('success', $message);
    }

    /**
     * Display the specified student.
     */
    public function show(Student $student): View
    {
        $student = $this->studentService->getWithRelations($student->id);

        return view('dashboard.students.show', compact('student'));
    }

    /**
     * Show the form for editing the specified student.
     */
    public function edit(Student $student): View
    {
        $student->load('user', 'batch');
        $batches = Batch::select('id', 'name', 'code', 'course_id', 'schedule')->get();
        $courses = Course::active()->orderBy('delivery_mode')->orderBy('name')->get(['id', 'name', 'delivery_mode']);
        $defaultMode = $student->admission_mode ?? 'offline';

        return view('dashboard.students.edit', compact('student', 'batches', 'courses', 'defaultMode'));
    }

    /**
     * Update the specified student.
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();

        // Sync the linked user account: name + email can be edited here.
        if ($student->user) {
            $userData = [];
            if (!empty($validated['name'])) {
                $userData['name'] = $validated['name'];
            }
            if (!empty($validated['email']) && $validated['email'] !== $student->user->email) {
                $userData['email'] = $validated['email'];
            }
            if ($userData) {
                $student->user->update($userData);
            }
        }

        // Handle profile image - file upload takes priority over URL
        $imagePath = $this->handleImageInput($request, 'profile_image', 'students/profiles');
        if ($imagePath) {
            $validated['profile_image'] = $imagePath;
        } elseif ($request->hasFile('profile_image')) {
            // Fallback for direct file input (legacy)
            $validated['profile_image'] = $this->uploadFile($request, 'profile_image', 'students/profiles');
        }

        // Handle course name from course_id
        if ($request->filled('course_id')) {
            $course = Course::find($request->course_id);
            if ($course) {
                $validated['course_name'] = $course->name;
            }
        }

        // Keep the lifecycle status in sync when admission_status is edited.
        if (isset($validated['admission_status'])) {
            $validated['status'] = $validated['admission_status'] === 'approved' ? 'active' : $validated['admission_status'];
            $student->user?->update([
                'is_active' => $validated['admission_status'] === 'approved',
            ]);
        } elseif (array_key_exists('batch_id', $validated)) {
            // Assigning a batch in the edit form = approved/enrolled.
            $approved = (bool) $validated['batch_id'];
            $validated['admission_status'] = $approved ? 'approved' : 'pending';
            $validated['status'] = $approved ? 'active' : 'pending';
            $student->user?->update(['is_active' => $approved]);
        }

        // Update student using service
        $this->studentService->update($student, $validated);

        return redirect()->route('dashboard.students.index')
            ->with('success', 'Student updated successfully.');
    }

    /**
     * Remove the specified student.
     */
    public function destroy(Student $student): RedirectResponse
    {
        // Delete associated user if exists
        if ($student->user) {
            $student->user->delete();
        }

        $this->studentService->delete($student);

        return redirect()->route('dashboard.students.index')
            ->with('success', 'Student deleted successfully.');
    }

    /**
     * Display the admission form page.
     */
    public function admissionForm(Request $request): View
    {
        $query = Student::with(['user', 'batch', 'batch.course'])
            ->orderBy('created_at', 'desc');

        // Filter by course if specified
        if ($request->filled('course_id')) {
            $query->whereHas('batch', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        // Filter by admission status
        if ($request->filled('admission_status')) {
            switch ($request->admission_status) {
                case 'pending':
                    $query->where('admission_status', 'pending');
                    break;
                case 'approved':
                    $query->where('admission_status', 'approved');
                    break;
                case 'rejected':
                    $query->where('admission_status', 'rejected');
                    break;
                case 'recent':
                    $query->where('created_at', '>=', now()->subDays(30));
                    break;
            }
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('class', 'like', "%{$search}%")
                ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        // Sorting functionality
        if ($request->filled('sort')) {
            $direction = $request->get('direction', 'asc');
            
            switch ($request->sort) {
                case 'student':
                    $query->join('users', 'students.user_id', '=', 'users.id')
                        ->orderBy('users.name', $direction)
                        ->select('students.*');
                    break;
                case 'course':
                    $query->join('batches', 'students.batch_id', '=', 'batches.id')
                        ->join('courses', 'batches.course_id', '=', 'courses.id')
                        ->orderBy('courses.name', $direction)
                        ->select('students.*');
                    break;
                case 'admission_date':
                    $query->orderBy('created_at', $direction);
                    break;
                default:
                    $query->orderBy($request->sort, $direction);
            }
        }

        $students = $query->paginate(15);
        $courses = Course::select('id', 'name', 'class')->orderBy('name')->get();
        
        // Statistics for dashboard
        $stats = [
            'total_applications' => Student::count(),
            'pending_admissions' => Student::where('admission_status', 'pending')->count(),
            'approved_admissions' => Student::where('admission_status', 'approved')->count(),
            'rejected_admissions' => Student::where('admission_status', 'rejected')->count(),
            'recent_applications' => Student::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('dashboard.students.admission-form', compact('students', 'courses', 'stats'));
    }

    /**
     * Display the batch assignment page.
     */
    public function batchAssignment(): View
    {
        $students = Student::with(['user', 'batch'])->paginate(20);
        $batches = Batch::all();

        return view('dashboard.students.batch-assignment', compact('students', 'batches'));
    }

    /**
     * Update batch assignment for a single student.
     */
    public function updateBatchAssignment(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'batch_id' => 'nullable|exists:batches,id',
        ]);

        $student = Student::findOrFail($request->student_id);
        $wasInactive = !$student->user?->is_active;
        $approved = (bool) $request->batch_id;
        $student->update([
            'batch_id' => $request->batch_id,
            'admission_status' => $approved ? 'approved' : 'pending',
            'status' => $approved ? 'active' : 'pending',
        ]);
        $student->user?->update(['is_active' => $approved]);
        // Only send a reset link when the student doesn't already have a usable
        // password of their own (i.e. must_change_password is still true).
        if ($approved && $wasInactive && $student->user && $student->user->must_change_password) {
            Password::sendResetLink(['email' => $student->user->email]);
        }

        return redirect()->route('dashboard.students.batch-assignment')
            ->with('success', 'Student batch assignment updated successfully.');
    }

    /**
     * Approve or reject a student's admission (single source of truth).
     */
    public function updateAdmissionStatus(Request $request, Student $student): RedirectResponse
    {
        $request->validate([
            'admission_status' => 'required|in:pending,approved,rejected',
        ]);

        $status = $request->admission_status;
        $student->update([
            'admission_status' => $status,
            'status' => $status === 'approved' ? 'active' : $status,
        ]);

        // Rejected students lose login access; approved (even unbatched) gain it.
        $student->user?->update([
            'is_active' => $status === 'approved',
        ]);

        if ($status === 'approved' && $student->user) {
            $student->load('user');
            // Only send a password-reset link when the student does NOT already
            // have a usable password (i.e. one they set themselves or the admin
            // chose one). Otherwise they can just log in with their known creds.
            if ($student->user->must_change_password) {
                Password::sendResetLink(['email' => $student->user->email]);
            }

            try {
                $courseName = $student->batch?->course?->name ?? $student->course_name ?? 'your selected course';
                $resetLine = $student->user->must_change_password
                    ? 'A secure password reset link has been sent to this email — click it to set your password, then log in with your registered email address.'
                    : 'Your account is now active. Log in with your registered email address and the password you set.';
                $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;">'
                    . '<div style="background:#168536;padding:24px;border-radius:12px 12px 0 0;text-align:center;">'
                    . '<h2 style="color:#fff;margin:0;">Admission Approved</h2></div>'
                    . '<div style="border:1px solid #e5e7eb;border-top:0;padding:32px;border-radius:0 0 12px 12px;">'
                    . '<p>Dear <strong>' . e($student->user?->name ?? 'Student') . '</strong>,</p>'
                    . '<p>Congratulations! Your admission to <strong>' . e($courseName) . '</strong> at Dhaka IT Institute has been approved.</p>'
                    . '<p>' . $resetLine . '</p>'
                    . '<p style="margin-top:24px;color:#6b7280;font-size:13px;">Dhaka IT Institute — Let\'s Build Your Dream</p>'
                    . '</div></div>';

                app(\App\Services\BrevoEmailService::class)->send(
                    $student->user->email,
                    'Admission Approved — ' . $courseName,
                    $html,
                    ['type' => 'admission']
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Approval email failed', ['error' => $e->getMessage()]);
            }
        }

        return back()->with('success', 'Admission status updated to ' . ucfirst($status) . '.');
    }

    /**
     * Bulk update batch assignments for multiple students.
     */
    public function bulkBatchAssignment(Request $request): RedirectResponse
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'batch_id' => 'nullable|exists:batches,id',
        ]);

        $usersToInvite = User::whereHas('student', fn ($query) => $query->whereIn('id', $request->student_ids))
            ->where('is_active', false)->get();
        $approved = (bool) $request->batch_id;
        Student::whereIn('id', $request->student_ids)
            ->update([
                'batch_id' => $request->batch_id,
                'admission_status' => $approved ? 'approved' : 'pending',
                'status' => $approved ? 'active' : 'pending',
            ]);
        User::whereHas('student', fn ($query) => $query->whereIn('id', $request->student_ids))
            ->update(['is_active' => $approved]);
        if ($approved) {
            $usersToInvite->each(fn (User $user) => Password::sendResetLink(['email' => $user->email]));
        }

        $count = count($request->student_ids);
        return redirect()->route('dashboard.students.batch-assignment')
            ->with('success', "Successfully updated batch assignment for {$count} students.");
    }

    /**
     * Display the attendance tracking page.
     */
    public function attendance(Request $request): View
    {
        $query = \App\Models\Attendance::with(['student.user', 'batch'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by batch
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Search by student name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $attendances = $query->paginate(20);
        $batches = Batch::select('id', 'name')->orderBy('name')->get();

        // Statistics
        $stats = [
            'total_records' => \App\Models\Attendance::count(),
            'present_today' => \App\Models\Attendance::whereDate('date', today())->where('status', 'present')->count(),
            'absent_today' => \App\Models\Attendance::whereDate('date', today())->where('status', 'absent')->count(),
            'late_today' => \App\Models\Attendance::whereDate('date', today())->where('status', 'late')->count(),
        ];

        return view('dashboard.students.attendance', compact('attendances', 'batches', 'stats'));
    }

    /**
     * Display the SMS notification page.
     */
    public function sms(Request $request): View
    {
        // Get students with phone numbers
        $students = Student::with('user', 'batch')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get batches for filtering
        $batches = Batch::select('id', 'name')->orderBy('name')->get();

        // Get recent SMS logs
        $recentSms = \App\Models\SmsLog::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // SMS Statistics
        $stats = [
            'total_sent' => \App\Models\SmsLog::where('status', 'sent')->count(),
            'total_delivered' => \App\Models\SmsLog::where('status', 'delivered')->count(),
            'total_failed' => \App\Models\SmsLog::where('status', 'failed')->count(),
            'total_pending' => \App\Models\SmsLog::where('status', 'pending')->count(),
        ];

        return view('dashboard.students.sms', compact('students', 'batches', 'recentSms', 'stats'));
    }

    /**
     * Display the exam routine page.
     */
    public function routine(Request $request): View
    {
        $batches = Batch::all();
        
        // Get selected batch or first batch
        $selectedBatchId = $request->input('batch_id', $batches->first()->id ?? null);
        
        // Get class schedules for the selected batch
        $schedules = \App\Models\ClassSchedule::with(['batch', 'teacher'])
            ->where('batch_id', $selectedBatchId)
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return view('dashboard.students.routine', compact('batches', 'schedules', 'selectedBatchId'));
    }

    /**
     * Display the results page.
     */
    public function results(Request $request): View
    {
        $query = \App\Models\ExamResult::with(['student.user', 'student.batch', 'exam'])
            ->orderBy('created_at', 'desc');

        // Filter by student
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // Filter by exam
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        // Filter by grade
        if ($request->filled('grade')) {
            $query->where('grade', $request->grade);
        }

        // Search by student name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Filter by pass/fail
        if ($request->filled('status')) {
            if ($request->status === 'passed') {
                $query->whereColumn('obtained_marks', '>=', 'total_marks');
            } elseif ($request->status === 'failed') {
                $query->whereColumn('obtained_marks', '<', 'total_marks');
            }
        }

        $results = $query->paginate(20);
        
        // Get all students and exams for filters
        $students = Student::with('user')->orderBy('created_at', 'desc')->get();
        $exams = \App\Models\Exam::orderBy('created_at', 'desc')->get();

        // Statistics
        $stats = [
            'total_results' => \App\Models\ExamResult::count(),
            'average_score' => \App\Models\ExamResult::avg('obtained_marks') ?? 0,
            'highest_score' => \App\Models\ExamResult::max('obtained_marks') ?? 0,
            'total_exams' => \App\Models\Exam::count(),
        ];

        return view('dashboard.students.results', compact('results', 'students', 'exams', 'stats'));
    }

    /**
     * Get batches for a specific course (AJAX).
     */
    public function getBatches($courseId): JsonResponse
    {
        $batches = Batch::where('course_id', $courseId)->get(['id', 'name', 'code', 'schedule']);

        return response()->json($batches);
    }

    /**
     * Get courses for a specific class (AJAX).
     */
    public function getCourses($class): JsonResponse
    {
        $courses = Course::where('class', $class)->get(['id', 'name']);

        return response()->json($courses);
    }

    /**
     * Upload a file and return the path.
     */
    private function uploadFile(Request $request, string $key, string $directory): ?string
    {
        if ($request->hasFile($key)) {
            return $request->file($key)->store($directory, 'public');
        }

        return null;
    }

    /**
     * Handle image input from the reusable image-input component.
     * The component sends files as {name}_file and URLs as {name}_url.
     * File upload takes priority over URL.
     */
    private function handleImageInput(Request $request, string $name, string $directory): ?string
    {
        $fileKey = $name . '_file';
        $urlKey = $name . '_url';

        // File upload takes priority
        if ($request->hasFile($fileKey)) {
            $file = $request->file($fileKey);
            
            // Log for debugging
            \Log::info("Image upload attempt for {$name}", [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'is_valid' => $file->isValid(),
                'error' => $file->getError(),
            ]);

            if (!$file->isValid()) {
                \Log::error("Image upload failed for {$name}", [
                    'error_code' => $file->getError(),
                    'error_message' => $this->getUploadErrorMessage($file->getError()),
                ]);
                return null;
            }

            try {
                $path = $file->store($directory, 'public');
                \Log::info("Image stored successfully", ['path' => $path]);
                return $path;
            } catch (\Exception $e) {
                \Log::error("Image storage failed", ['error' => $e->getMessage()]);
                return null;
            }
        }

        // Fall back to URL if provided
        if ($request->filled($urlKey)) {
            $url = $request->input($urlKey);
            // For external URLs, just store the URL directly
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        }

        return null;
    }

    /**
     * Get human-readable upload error message.
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            default => 'Unknown upload error',
        };
    }
}
