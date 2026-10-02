<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesHostedMedia;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Storage\CatboxStorage;
use App\Storage\CatboxUploadFailed;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MaterialController extends Controller
{
    use HandlesHostedMedia;

    public function index(Course $course)
    {
        $materials = $course->materials()->orderBy('order')->get();
        return view('dashboard.materials.index', compact('course', 'materials'));
    }

    public function create(Course $course)
    {
        return view('dashboard.materials.create', compact('course'));
    }

    public function store(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:pdf,video,document,link,image',
            'file' => [
                'required_unless:type,link', 'file',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,mp4,webm,zip', 'max:51200',
                new \App\Rules\SafeUpload,
            ],
            'file_path' => 'required_if:type,link|nullable|url',
        ]);

        $order = $course->materials()->max('order') + 1;

        if ($request->hasFile('file')) {
            $validated['file_path'] = $this->hostMaterial($request, $course);
        }

        $course->materials()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'file_path' => $validated['file_path'],
            'order' => $order,
        ]);

        return redirect()->route('dashboard.courses.materials.index', $course)
            ->with('success', 'Material uploaded successfully.');
    }

    /**
     * Stream / redirect to a single material.
     *
     * Route::resource registers GET .../materials/{material}, so this method must
     * exist. Hosted files are fetched through the media host rather than read
     * off local disk, so this issues a redirect instead of streaming bytes
     * through PHP.
     */
    public function show(Course $course, CourseMaterial $material)
    {
        abort_unless($material->course_id === $course->id, 404);

        if ($material->type === 'link') {
            abort_unless($material->file_path, 404, 'This material has no link.');

            return redirect()->away($material->file_path);
        }

        $storage = app(CatboxStorage::class);
        $url = $storage->url($material->file_path);

        abort_unless($url, 404, 'The file for this material is no longer available.');

        return redirect()->away($url);
    }

    public function edit(Course $course, CourseMaterial $material)
    {
        abort_unless($material->course_id === $course->id, 404);
        return view('dashboard.materials.edit', compact('course', 'material'));
    }

    public function update(Request $request, Course $course, CourseMaterial $material)
    {
        abort_unless($material->course_id === $course->id, 404);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:pdf,video,document,link,image',
            'file' => [
                'nullable', 'file',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,mp4,webm,zip', 'max:51200',
                new \App\Rules\SafeUpload,
            ],
            'file_path' => 'required_if:type,link|nullable|url',
        ]);

        if ($request->hasFile('file')) {
            $this->unlinkMedia($material->file_path);
            $validated['file_path'] = $this->hostMaterial($request, $course);
        } elseif ($validated['type'] === 'link') {
            $material->file_path = $validated['file_path'];
        }

        $material->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'file_path' => $validated['file_path'] ?? $material->file_path,
        ]);

        return redirect()->route('dashboard.courses.materials.index', $course)
            ->with('success', 'Material updated successfully.');
    }

    public function destroy(Course $course, CourseMaterial $material)
    {
        abort_unless($material->course_id === $course->id, 404);

        $this->unlinkMedia($material->file_path);

        $material->delete();

        return redirect()->route('dashboard.courses.materials.index', $course)
            ->with('success', 'Material deleted successfully.');
    }

    public function reorder(Request $request, Course $course)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:course_materials,id',
        ]);

        foreach ($request->order as $index => $materialId) {
            CourseMaterial::where('id', $materialId)
                ->where('course_id', $course->id)
                ->update(['order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Host a course material upload and return its URL.
     */
    private function hostMaterial(Request $request, Course $course): string
    {
        try {
            return app(CatboxStorage::class)->store(
                $request->file('file'),
                'materials/' . $course->id,
                $request->file('file')->getClientOriginalName()
            );
        } catch (CatboxUploadFailed $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }
    }
}
