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
        $filters = [
            'search' => $request->input('search'),
            'year' => $request->input('year'),
            'batch_id' => $request->input('batch_id'),
            'class' => $request->input('class'),
            'with_dues' => $request->boolean('with_dues'),
            'featured' => $request->boolean('featured'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_dir' => $request->input('sort_dir', 'desc'),
        ];

        $students = $this->studentService->getPaginated($filters, 15);
        $batches = Batch::select('id', 'name')->get();
        $years = range(date('Y'), date('Y') - 5);
        $classes = range(1, 12);

        return view('dashboard.students.index', compact('students', 'batches', 'years', 'classes', 'filters'));
    }

    /**
     * Show the form for creating a new student.
     */
    public function create(): View
    {
        $batches = Batch::select('id', 'name', 'code', 'course_id', 'schedule')->get();
        $classes = range(1, 12);

        return view('dashboard.students.create', compact('batches', 'classes'));
    }

    /**
     * Store a newly created student.
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Generate secure random password
        $password = \Illuminate\Support\Str::random(12);
        
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

        DB::transaction(function () use ($validated, $request, $password) {
            $user = User::create([
                'name' => $request->input('name') ?: $validated['name_bn'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
            ]);

            if ($studentRole = Role::where('slug', 'student')->first()) {
                $user->roles()->attach($studentRole->id);
            }

            $validated['user_id'] = $user->id;
            $validated['applied_at'] = now();
            $validated['admission_status'] = !empty($validated['batch_id']) ? 'approved' : 'pending';
            $this->studentService->create($validated);
        });

        // Store password temporarily in session for display (will be cleared after showing)
        session()->flash('generated_password', $password);

        return redirect()->route('dashboard.students.index')
            ->with('success', 'Student created successfully. Please save the temporary password shown below.');
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
        $classes = range(1, 12);
        $courses = Course::where('class', $student->class)->get(['id', 'name']);

        return view('dashboard.students.edit', compact('student', 'batches', 'courses', 'classes'));
    }

    /**
     * Update the specified student.
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();

        // Update user name if provided
        if ($student->user && isset($validated['name_bn'])) {
            $student->user->update(['name' => $validated['name_bn']]);
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
                    $query->whereNull('batch_id');
                    break;
                case 'approved':
                    $query->whereNotNull('batch_id')->where('status', 'active');
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
            'pending_admissions' => Student::whereNull('batch_id')->count(),
            'approved_admissions' => Student::whereNotNull('batch_id')->where('status', 'active')->count(),
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
        $student->update(['batch_id' => $request->batch_id]);

        return redirect()->route('dashboard.students.batch-assignment')
            ->with('success', 'Student batch assignment updated successfully.');
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

        Student::whereIn('id', $request->student_ids)
            ->update(['batch_id' => $request->batch_id]);

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
