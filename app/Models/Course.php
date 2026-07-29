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
     * Get the teachers for the course.
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'course_teacher');
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
