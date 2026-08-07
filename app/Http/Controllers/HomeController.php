<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\CourseVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index()
    {
        $featuredStudents = Student::with(['user', 'batch.course'])
            ->featured()
            ->take(8)
            ->get();

        $randomStudents = Student::with(['user', 'batch.course'])
            ->inRandomOrder()
            ->take(12)
            ->get();

        $popularCourses = \App\Models\Course::active()
            ->with(['batches.students', 'videos'])
            ->withCount('videos')
            ->take(8)
            ->get();

        $announcements = Announcement::active()
            ->where('target_type', 'all')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $page = Page::findBySlug('home');

        return view('welcome', compact('featuredStudents', 'randomStudents', 'popularCourses', 'announcements', 'page'));
    }

    public function showAnnouncement(Announcement $announcement)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // According to requirements:
        // - Authenticated: Redirect to announcement details
        // - Bought course: Redirect to announcement details
        // - Not bought: Redirect to announcement details
        // - Bought + Batch: Redirect to announcement details
        
        // Basically, if logged in, show it.
        return view('announcement-details', compact('announcement'));
    }

    public function about()
    {
        $page = Page::findBySlug('about');
        return view('about', compact('page'));
    }

    public function contact()
    {
        $page = Page::findBySlug('contact');
        return view('contact', compact('page'));
    }

    public function submitContact(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        try {
            $settingsService = app(\App\Services\SettingsService::class);
            $to = $settingsService->get('institution_email', 'dhakaitinstitute@gmail.com');

            $html = "<p><strong>Name:</strong> " . e($data['name']) . "</p>"
                  . "<p><strong>Email:</strong> " . e($data['email']) . "</p>"
                  . "<p><strong>Subject:</strong> " . e($data['subject']) . "</p>"
                  . "<hr><p>" . nl2br(e($data['message'])) . "</p>";

            app(\App\Services\BrevoEmailService::class)->send(
                $to,
                'Contact form: ' . $data['subject'],
                $html,
                ['type' => 'contact']
            );

            return back()->with('success', 'Your message has been sent. We will get back to you soon.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Contact form send failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Sorry, your message could not be sent. Please try again.');
        }
    }

    public function services()
    {
        $page = Page::findBySlug('services');
        $services = \App\Models\Service::active()->ordered()->get();

        return view('services', compact('page', 'services'));
    }

    public function team()
    {
        $page = Page::findBySlug('team');
        $team = Teacher::with(['user', 'category'])
            ->where('status', 'active')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return view('team', compact('page', 'team'));
    }

    public function courseDemo(Course $course)
    {
        abort_unless($course->status === 'active', 404);
        $video = $course->videos()->where('is_preview', true)->orderBy('order')->orderBy('id')->firstOrFail();

        return view('course-demo', compact('course', 'video'));
    }

    public function streamCourseDemo(Course $course, CourseVideo $video)
    {
        abort_unless($course->status === 'active' && $video->course_id === $course->id && $video->is_preview, 404);
        $disk = Storage::disk(config('filesystems.private'));
        abort_unless($video->video_path && $disk->exists($video->video_path), 404);

        return $disk->response($video->video_path, null, [
            'Content-Type' => $disk->mimeType($video->video_path) ?: 'video/mp4',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function courses()
    {
        $page = Page::findBySlug('courses');
        
        $query = \App\Models\Course::active()
            ->with(['batches.students', 'videos'])
            ->withCount('videos');

        if (request()->filled('mode')) {
            request()->validate(['mode' => 'in:online,offline']);
            $query->where('delivery_mode', request('mode'));
        }
        
        // Apply search filter
        if (request()->filled('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        
        // Apply category filter
        if (request()->filled('category')) {
            $query->where('category', request('category'));
        }
        
        $courses = $query->paginate(12)->withQueryString();
        
        // Get unique categories for filter
        $categories = \App\Models\Course::active()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values();
        
        return view('courses', compact('page', 'courses', 'categories'));
    }

    public function teachers()
    {
        $page = Page::findBySlug('teachers');
        $teachers = Teacher::with('user')->get();
        return view('teachers', compact('page', 'teachers'));
    }

    public function students()
    {
        $page = Page::findBySlug('students');
        
        // Get statistics
        $totalStudents = Student::count();
        $maleStudents = Student::where('gender', 'Male')->count();
        $femaleStudents = Student::where('gender', 'Female')->count();
        
        // Calculate attendance rate
        $attendanceRate = 0;
        $studentsWithAttendance = Student::has('attendances')->get();
        if ($studentsWithAttendance->count() > 0) {
            $attendanceRate = round($studentsWithAttendance->avg(function($student) {
                return $student->attendance_percentage;
            }), 0);
        }
        
        // Get class-wise distribution
        $classDistribution = Student::selectRaw('class, COUNT(*) as count')
            ->whereNotNull('class')
            ->groupBy('class')
            ->orderBy('class')
            ->get();
        
        return view('students', compact('page', 'totalStudents', 'maleStudents', 'femaleStudents', 'attendanceRate', 'classDistribution'));
    }

    public function results(Request $request)
    {
        $page = Page::findBySlug('results');
        
        // Get all exams with results for the dropdown
        $exams = \App\Models\Exam::with(['batch', 'course'])
            ->has('results')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get recent exam statistics
        $recentExams = \App\Models\Exam::with(['results', 'batch', 'course'])
            ->has('results')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get()
            ->map(function($exam) {
                $results = $exam->results;
                $totalStudents = $results->count();
                $passedStudents = $results->filter(fn($r) => $r->hasPassed())->count();
                $passRate = $totalStudents > 0 ? round(($passedStudents / $totalStudents) * 100, 1) : 0;
                
                $gpa5Count = $results->filter(fn($r) => $r->percentage >= 90)->count();
                $gpa4Count = $results->filter(fn($r) => $r->percentage >= 80)->count();
                
                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'type' => $exam->type,
                    'pass_rate' => $passRate,
                    'gpa5_count' => $gpa5Count,
                    'gpa4_count' => $gpa4Count,
                    'total_students' => $totalStudents,
                ];
            });
        
        // Handle search
        $searchResult = null;
        if ($request->filled('exam_id') && $request->filled('registration_no')) {
            $student = \App\Models\Student::where('registration_no', $request->registration_no)->first();
            
            if ($student) {
                $searchResult = \App\Models\ExamResult::with(['exam', 'student'])
                    ->where('exam_id', $request->exam_id)
                    ->where('student_id', $student->id)
                    ->first();
            }
        }
        
        return view('results', compact('page', 'exams', 'recentExams', 'searchResult'));
    }
}
