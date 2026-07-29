<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::query();
        $mode = $request->session()->get('course_mode', 'online');
        $query->where('delivery_mode', $mode);

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

        $courses = $query->with('batches')->withCount(['videos', 'students'])->paginate(12);

        return view('dashboard.courses.index', compact('courses'));
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

        return redirect()->route('dashboard.courses.index')
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
            // Delete old image if it's a local file
            if ($course->image && !filter_var($course->image, FILTER_VALIDATE_URL) && Storage::disk('public')->exists($course->image)) {
                Storage::disk('public')->delete($course->image);
            }
            $validated['image'] = $imagePath;
        }

        $course->update($validated);

        return redirect()->route('dashboard.courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        // Delete associated image
        if ($course->image && Storage::disk('public')->exists($course->image)) {
            Storage::disk('public')->delete($course->image);
        }

        $course->delete();

        return redirect()->route('dashboard.courses.index')
            ->with('success', 'Course deleted successfully.');
    }

    public function routine()
    {
        $batches = \App\Models\Batch::with(['course', 'teachers'])->active()->get();
        return view('dashboard.courses.routine', compact('batches'));
    }

    public function materials()
    {
        $courses = Course::active()->get();
        return view('dashboard.courses.materials', compact('courses'));
    }

    public function groups()
    {
        $batches = \App\Models\Batch::with(['course', 'teachers'])->active()->get();
        return view('dashboard.courses.groups', compact('batches'));
    }

    public function attendance()
    {
        $batches = \App\Models\Batch::with(['course', 'students'])->active()->get();
        return view('dashboard.courses.attendance', compact('batches'));
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
            
            \Log::info("Image upload attempt for {$name}", [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'is_valid' => $file->isValid(),
            ]);

            if (!$file->isValid()) {
                \Log::error("Image upload failed for {$name}", ['error_code' => $file->getError()]);
                return null;
            }

            try {
                return $file->store($directory, 'public');
            } catch (\Exception $e) {
                \Log::error("Image storage failed", ['error' => $e->getMessage()]);
                return null;
            }
        }

        // Fall back to URL if provided
        if ($request->filled($urlKey)) {
            $url = $request->input($urlKey);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        }

        return null;
    }
}
