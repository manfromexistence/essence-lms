<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Course extends Model
{
    use HasFactory;

    protected $appends = [
        'image_url',
    ];

    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'duration',
        'duration_unit',
        'status',
        'delivery_mode',
        'online_details',
        'offline_details',
        'image',
        'start_date',
        'end_date',
        'max_students',
        'category',
        'class',
        'level',
        'prerequisites',
        'objectives',
        'syllabus',
        'materials_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'max_students' => 'integer',
        'prerequisites' => 'array',
        'objectives' => 'array',
        'syllabus' => 'array',
    ];

    /**
     * Return a display-ready course image, with a professional IT fallback.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            if (filter_var($this->image, FILTER_VALIDATE_URL)) {
                return $this->image;
            }

            return asset('storage/' . ltrim($this->image, '/'));
        }

        $searchableText = strtolower(implode(' ', array_filter([
            $this->name,
            $this->category,
            $this->code,
        ])));

        $fallbacks = [
            'office' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=82',
            'marketing' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1200&q=82',
            'design' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1200&q=82',
            'development' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=1200&q=82',
        ];

        foreach ($fallbacks as $keyword => $url) {
            if (str_contains($searchableText, $keyword)) {
                return $url;
            }
        }

        return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=82';
    }

    /**
     * Get the batches for the course.
     */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /**
     * Get the materials for the course.
     */
    public function materials(): HasMany
    {
        return $this->hasMany(CourseMaterial::class)->orderBy('order');
    }

    /**
     * Get the videos for the course.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(CourseVideo::class)->orderBy('order');
    }

    /**
     * Get the schedules for the course through batches.
     */
    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(ClassSchedule::class, Batch::class);
    }

    /**
     * Get the exams for the course.
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Get the students enrolled in this course through batches.
     */
    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(
            Student::class,
            Batch::class,
            'course_id',
            'batch_id',
            'id',
            'id'
        );
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    /**
     * Get the teachers for the course (via batches, using teacher_batch pivot).
     * A course's teachers are the teachers assigned to any of its batches.
     */
    public function teachers(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            Teacher::class,
            \App\Models\Batch::class,
            'course_id',    // batches.course_id
            'id',           // teachers.id
            'id',           // courses.id
            'id'            // batches.id — matched against teacher_batch.batch_id below
        )->join('teacher_batch', 'teacher_batch.batch_id', '=', 'batches.id')
         ->distinct();
    }

    /**
     * Get the count of students enrolled in this course.
     */
    public function getStudentsCountAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Get the count of materials for the course.
     *
     * @return int
     */
    public function getMaterialsCountAttribute(): int
    {
        return $this->materials()->count();
    }

    /**
     * Scope a query to only include active courses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive courses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope a query to only include completed courses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
