<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesHostedMedia;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use HandlesHostedMedia;
    public function index(Request $request)
    {
        $query = Course::query();
        // Delivery-mode filter: explicit ?delivery_mode=all|online|offline wins,
        // otherwise fall back to the header toggle stored in session.
        // Default is 'all' so a newly uploaded course is never hidden by a
        // silent single-mode filter right after creation.
        $modeFilter = $request->get('delivery_mode', $request->session()->get('course_mode', 'all'));
        if (! in_array($modeFilter, ['all', 'online', 'offline'], true)) {
            $modeFilter = 'all';
        }
        if ($modeFilter !== 'all') {
            $query->where('delivery_mode', $modeFilter);
        }

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where(function ($subQ) use ($search) {
                $subQ->where('name', 'like', "%{$search}%")
                     ->orWhere('code', 'like', "%{$search}%")
                     ->orWhere('description', 'like', "%{$search}%");
            });
        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        $query->when($request->filled('category'), function ($q) use ($request) {
            $q->where('category', $request->category);
        });

        $courses = $query->with('batches')->withCount(['videos', 'students'])->orderByDesc('courses.id')->paginate(12);

        return view('dashboard.courses.index', compact('courses', 'modeFilter'));
    }

    public function create()
    {
        return view('dashboard.courses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses,code',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:hours,days,weeks,months',
            'status' => 'required|in:active,inactive,draft',
            'delivery_mode' => 'required|in:online,offline',
            'online_details' => 'nullable|string|max:5000',
            'offline_details' => 'nullable|string|max:5000',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480',
            'image_url' => 'nullable|url|max:500',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'max_students' => 'nullable|integer|min:1',
            'category' => 'nullable|string|max:100',
            'class' => 'nullable|string',
            'level' => 'required|in:beginner,intermediate,advanced',
            'prerequisites' => 'nullable|array',
            'objectives' => 'nullable|array',
            'syllabus' => 'nullable|array',
            'materials_url' => 'nullable|url'
        ]);

        // Handle image upload - file takes priority over URL
        $imagePath = $this->handleImageInput($request, 'image', 'courses');
        if ($imagePath) {
            $validated['image'] = $imagePath;
        }

        Course::create($validated);

        // Switch the header mode toggle to the new course's mode so the
        // admin list shows the just-created course instead of hiding it
        // behind the previous single-mode filter.
        $request->session()->put('course_mode', $validated['delivery_mode']);

        return redirect()->route('dashboard.courses.index', ['delivery_mode' => $validated['delivery_mode']])
            ->with('success', 'Course created successfully.');
    }

    public function show(Course $course)
    {
        $course->load(['batches.students', 'batches.teachers']);
        return view('dashboard.courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        return view('dashboard.courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses,code,' . $course->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:hours,days,weeks,months',
            'status' => 'required|in:active,inactive,draft',
            'delivery_mode' => 'required|in:online,offline',
            'online_details' => 'nullable|string|max:5000',
            'offline_details' => 'nullable|string|max:5000',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480',
            'image_url' => 'nullable|url|max:500',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'max_students' => 'nullable|integer|min:1',
            'category' => 'nullable|string|max:100',
            'class' => 'nullable|string',
            'level' => 'required|in:beginner,intermediate,advanced',
            'prerequisites' => 'nullable|array',
            'objectives' => 'nullable|array',
            'syllabus' => 'nullable|array',
            'materials_url' => 'nullable|url'
        ]);

        // Handle image upload - file takes priority over URL
        $imagePath = $this->handleImageInput($request, 'image', 'courses');
        if ($imagePath) {
            $this->unlinkMedia($course->image);
            $validated['image'] = $imagePath;
        }

        $course->update($validated);

        $request->session()->put('course_mode', $validated['delivery_mode']);

        return redirect()->route('dashboard.courses.index', ['delivery_mode' => $validated['delivery_mode']])
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $this->unlinkMedia($course->image);

        $course->delete();

        return redirect()->route('dashboard.courses.index')
            ->with('success', 'Course deleted successfully.');
    }

    public function routine()
    {
        $batches = \App\Models\Batch::with(['course', 'teachers'])->active()->get();
        return view('dashboard.courses.routine', compact('batches'));
    }

    public function materials(Request $request)
    {
        $courses = Course::active()->get();

        // If a course is selected, show its materials via the real materials view
        if ($request->filled('course_id')) {
            $course = Course::findOrFail($request->course_id);
            $materials = $course->materials()->orderBy('order')->get();
            return view('dashboard.materials.index', compact('course', 'materials'));
        }

        return view('dashboard.courses.materials', compact('courses'));
    }

    public function groups()
    {
        $batches = \App\Models\Batch::with(['course', 'teachers', 'students.user'])->active()->get();
        return view('dashboard.courses.groups', compact('batches'));
    }

    public function attendance(Request $request)
    {
        $batches = \App\Models\Batch::with(['course', 'students'])->active()->get();
        if ($request->filled('batch_id')) {
            $batches = $batches->where('id', (int) $request->batch_id)->values();
        }
        return view('dashboard.courses.attendance', compact('batches'));
    }

    /**
     * Handle image input from the reusable image-input component.
     * The component sends files as {name}_file and URLs as {name}_url.
     * File upload takes priority over URL.
     */
    private function handleImageInput(Request $request, string $name, string $directory): ?string
    {
        return $this->resolveImageInput($request, $name, $directory);
    }
}
