<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\Teacher;
use App\Storage\CatboxStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class CourseService
{
    /**
     * Create a new course.
     */
    public function create(array $data): Course
    {
        return DB::transaction(function () use ($data) {
            $course = Course::create($data);

            // Assign teachers if provided
            if (!empty($data['teacher_ids'])) {
                $course->teachers()->sync($data['teacher_ids']);
            }

            return $course;
        });
    }

    /**
     * Update an existing course.
     */
    public function update(Course $course, array $data): Course
    {
        return DB::transaction(function () use ($course, $data) {
            $course->update($data);

            // Update teacher assignments if provided
            if (isset($data['teacher_ids'])) {
                $course->teachers()->sync($data['teacher_ids']);
            }

            return $course->fresh();
        });
    }

    /**
     * Delete a course with cascade handling.
     */
    public function delete(Course $course): bool
    {
        return DB::transaction(function () use ($course) {
            // Check if course has active batches
            $activeBatches = $course->batches()->where('status', 'active')->count();
            if ($activeBatches > 0) {
                throw new \RuntimeException('Cannot delete course with active batches');
            }

            // Unlink materials. Media is hosted on Catbox, where deletion needs
            // an account key this deployment does not use, so this only stops the
            // application pointing at the object.
            $storage = app(CatboxStorage::class);

            foreach ($course->materials as $material) {
                if ($material->file_path && ! $storage->isRemote($material->file_path)) {
                    $storage->forget($material->file_path);
                }
            }
            $course->materials()->delete();

            // Detach teachers
            $course->teachers()->detach();

            // Soft delete or cascade batches
            $course->batches()->update(['status' => 'inactive']);

            return $course->delete();
        });
    }

    /**
     * Assign teachers to a course.
     */
    public function assignTeachers(Course $course, array $teacherIds): Course
    {
        $course->teachers()->sync($teacherIds);
        return $course->fresh();
    }

    /**
     * Upload course material.
     */
    public function uploadMaterial(Course $course, UploadedFile $file, array $data = []): CourseMaterial
    {
        $url = app(CatboxStorage::class)->store(
            $file,
            'courses/' . $course->id . '/materials',
            $file->getClientOriginalName()
        );

        // Determine type from file extension
        $extension = strtolower($file->getClientOriginalExtension());
        $type = match ($extension) {
            'pdf' => 'pdf',
            'mp4', 'webm', 'mov', 'avi' => 'video',
            'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx' => 'document',
            default => 'file',
        };

        // Get next order
        $maxOrder = $course->materials()->max('order') ?? 0;

        return CourseMaterial::create([
            'course_id' => $course->id,
            'title' => $data['title'] ?? $file->getClientOriginalName(),
            'type' => $data['type'] ?? $type,
            'file_path' => $url,
            'description' => $data['description'] ?? null,
            'order' => $data['order'] ?? $maxOrder + 1,
        ]);
    }

    /**
     * Add external link as material.
     */
    public function addExternalMaterial(Course $course, array $data): CourseMaterial
    {
        $maxOrder = $course->materials()->max('order') ?? 0;

        return CourseMaterial::create([
            'course_id' => $course->id,
            'title' => $data['title'],
            'type' => $data['type'] ?? 'link',
            'external_url' => $data['url'],
            'description' => $data['description'] ?? null,
            'order' => $data['order'] ?? $maxOrder + 1,
        ]);
    }

    /**
     * Delete a course material.
     */
    public function deleteMaterial(CourseMaterial $material): bool
    {
        $storage = app(CatboxStorage::class);

        if ($material->file_path && ! $storage->isRemote($material->file_path)) {
            $storage->forget($material->file_path);
        }

        return $material->delete();
    }

    /**
     * Reorder course materials.
     */
    public function reorderMaterials(Course $course, array $order): void
    {
        foreach ($order as $index => $materialId) {
            CourseMaterial::where('id', $materialId)
                ->where('course_id', $course->id)
                ->update(['order' => $index + 1]);
        }
    }

    /**
     * Get active courses.
     */
    public function getActive(): Collection
    {
        return Course::active()
            ->with(['batches', 'teachers'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get course with all relations.
     */
    public function getWithRelations(int $id): Course
    {
        return Course::with([
            'batches' => function ($query) {
                $query->withCount('students');
            },
            'materials',
            'teachers.user',
            'exams',
        ])->findOrFail($id);
    }

    /**
     * Get paginated courses with optional filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Course::with(['batches', 'teachers']);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by class
        if (!empty($filters['class'])) {
            $query->where('class', $filters['class']);
        }

        // Filter by category
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Filter by search term
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Get courses by class.
     */
    public function getByClass(string $class): Collection
    {
        return Course::where('class', $class)
            ->active()
            ->with(['batches'])
            ->get();
    }

    /**
     * Get course statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total' => Course::count(),
            'active' => Course::active()->count(),
            'inactive' => Course::inactive()->count(),
            'completed' => Course::completed()->count(),
            'by_category' => Course::select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->get(),
        ];
    }

    /**
     * Duplicate a course.
     */
    public function duplicate(Course $course, array $overrides = []): Course
    {
        return DB::transaction(function () use ($course, $overrides) {
            $newData = array_merge($course->toArray(), $overrides);
            unset($newData['id'], $newData['created_at'], $newData['updated_at']);

            // Ensure unique code
            $newData['code'] = ($newData['code'] ?? $course->code) . '-copy';

            $newCourse = Course::create($newData);

            // Copy teachers
            $newCourse->teachers()->sync($course->teachers->pluck('id'));

            return $newCourse;
        });
    }
}
