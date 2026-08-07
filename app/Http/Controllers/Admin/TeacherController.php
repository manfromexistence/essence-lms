<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    public function index()
    {
        $query = Teacher::with('user');

        if (request()->filled('search')) {
            $search = request('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('phone', 'like', "%{$search}%");
        }

        $teachers = $query->paginate(15);
        return view('dashboard.teachers.index', compact('teachers'));
    }

    public function create()
    {
        return view('dashboard.teachers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'salary' => 'nullable|numeric|min:0',
            'subjects' => 'nullable|array',
            'subjects.*' => 'string|max:255',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:500',
            'social_links.linkedin' => 'nullable|url|max:500',
            'social_links.twitter' => 'nullable|url|max:500',
            'social_links.instagram' => 'nullable|url|max:500',
            'social_links.github' => 'nullable|url|max:500',
            'social_links.website' => 'nullable|url|max:500',
            'is_featured' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
            'profile_image_file' => 'nullable|image|max:20480',
            'profile_image_url' => 'nullable|url|max:500',
        ]);

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(Str::password(32)),
            'is_active' => true,
            'must_change_password' => true,
        ]);
        Password::sendResetLink(['email' => $user->email]);

        // Assign teacher role
        $teacherRole = Role::where('slug', 'teacher')->first();
        abort_unless($teacherRole, 500, 'The "teacher" role is missing. Run the role seeder first.');
        $user->roles()->attach($teacherRole->id);

        // Handle profile image
        $profileImage = $this->handleImageInput($request, 'profile_image', 'teachers/profiles');

        // Create teacher profile
        Teacher::create([
            'user_id' => $user->id,
            'phone' => $request->phone,
            'department' => $request->department,
            'designation' => $request->designation,
            'bio' => $request->bio,
            'salary' => $request->salary ?? 0,
            'status' => 'active',
            'subjects' => $request->subjects,
            'social_links' => array_filter($request->input('social_links', [])),
            'is_featured' => $request->boolean('is_featured'),
            'display_order' => $request->input('display_order', 0),
            'profile_image' => $profileImage,
        ]);

        return redirect()->route('dashboard.teachers.index')
            ->with('success', 'Teacher created successfully.');
    }

    public function show(Teacher $teacher)
    {
        $teacher->load(['user', 'batches.students', 'category', 'salaries' => function($query) {
            $query->latest()->take(6);
        }]);
        return view('dashboard.teachers.show', compact('teacher'));
    }

    public function edit(Teacher $teacher)
    {
        $teacher->load('user');
        return view('dashboard.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $teacher->user_id,
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'salary' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'subjects' => 'nullable|array',
            'subjects.*' => 'string|max:255',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:500',
            'social_links.linkedin' => 'nullable|url|max:500',
            'social_links.twitter' => 'nullable|url|max:500',
            'social_links.instagram' => 'nullable|url|max:500',
            'social_links.github' => 'nullable|url|max:500',
            'social_links.website' => 'nullable|url|max:500',
            'is_featured' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
            'profile_image_file' => 'nullable|image|max:20480',
            'profile_image_url' => 'nullable|url|max:500',
        ]);

        // Update user information
        $teacher->user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Handle profile image
        $profileImage = $this->handleImageInput($request, 'profile_image', 'teachers/profiles');

        // Update teacher information
        $updateData = [
            'phone' => $request->phone,
            'department' => $request->department ?? 'General',
            'designation' => $request->designation,
            'bio' => $request->bio,
            'salary' => $request->salary ?? 0,
            'status' => $request->status,
            'subjects' => $request->subjects,
            'social_links' => array_filter($request->input('social_links', [])),
            'is_featured' => $request->boolean('is_featured'),
            'display_order' => $request->input('display_order', 0),
        ];

        if ($profileImage) {
            $updateData['profile_image'] = $profileImage;
        }

        $teacher->update($updateData);

        return redirect()->route('dashboard.teachers.show', $teacher)
            ->with('success', 'Teacher updated successfully.');
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();

        return redirect()->route('dashboard.teachers.index')
            ->with('success', 'Teacher deleted successfully.');
    }

    public function assignments()
    {
        $query = Teacher::with(['user', 'batches.course']);

        if (request()->filled('search')) {
            $search = request('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $teachers = $query->paginate(15);
        return view('dashboard.teachers.assignments', compact('teachers'));
    }

    public function assignmentEdit(Teacher $teacher)
    {
        $teacher->load(['batches']);
        $batches = \App\Models\Batch::with('course')->where('status', 'active')->get();
        return view('dashboard.teachers.assignment-edit', compact('teacher', 'batches'));
    }

    public function assignmentUpdate(Request $request, Teacher $teacher)
    {
        $request->validate([
            'batch_ids' => 'nullable|array',
            'batch_ids.*' => 'exists:batches,id',
            'subjects' => 'nullable|array',
            'subjects.*' => 'string|max:255',
        ]);

        // Sync batches
        $teacher->batches()->sync($request->batch_ids ?? []);

        // Update subjects
        $teacher->update([
            'subjects' => $request->subjects ?? []
        ]);

        return redirect()->route('dashboard.teachers.assignments')
            ->with('success', 'Teacher assignments updated successfully.');
    }

    public function assignmentRemove(Teacher $teacher)
    {
        // Remove all batch assignments
        $teacher->batches()->detach();
        
        // Clear subjects
        $teacher->update(['subjects' => []]);

        return redirect()->route('dashboard.teachers.assignments')
            ->with('success', 'All assignments removed from teacher.');
    }

    public function categories()
    {
        $categories = \App\Models\TeacherCategory::withCount('teachers')->get();
        return view('dashboard.teachers.categories', compact('categories'));
    }

    public function categoryStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:teacher_categories,name',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
        ]);

        \App\Models\TeacherCategory::create([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name),
            'description' => $request->description,
            'color' => $request->color ?? '#6366f1',
        ]);

        return redirect()->route('dashboard.teachers.categories')
            ->with('success', 'Category created successfully.');
    }

    public function categoryUpdate(Request $request, \App\Models\TeacherCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:teacher_categories,name,' . $category->id,
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name),
            'description' => $request->description,
            'color' => $request->color ?? $category->color,
        ]);

        return redirect()->route('dashboard.teachers.categories')
            ->with('success', 'Category updated successfully.');
    }

    public function categoryDestroy(\App\Models\TeacherCategory $category)
    {
        if ($category->teachers()->count() > 0) {
            return redirect()->route('dashboard.teachers.categories')
                ->with('error', 'Cannot delete category with assigned teachers.');
        }

        $category->delete();

        return redirect()->route('dashboard.teachers.categories')
            ->with('success', 'Category deleted successfully.');
    }

    public function salary()
    {
        $query = \App\Models\TeacherSalary::with('teacher.user')->latest();

        if (request()->filled('search')) {
            $search = request('search');
            $query->whereHas('teacher.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $salaries = $query->paginate(15);
        return view('dashboard.teachers.salary', compact('salaries'));
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
