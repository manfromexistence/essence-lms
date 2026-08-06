<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'department',
        'designation',
        'subjects',
        'status',
        'profile_image',
        'social_links',
        'bio',
        'display_order',
        'is_featured',
        'salary',
        'category_id'
    ];

    protected $casts = [
        'subjects' => 'array',
        'social_links' => 'array',
        'is_featured' => 'boolean',
        'display_order' => 'integer',
        'salary' => 'decimal:2'
    ];

    public function getAvatarUrlAttribute(): string
    {
        if ($this->profile_image) {
            if (filter_var($this->profile_image, FILTER_VALIDATE_URL)) {
                return $this->profile_image;
            }
            return asset('storage/' . ltrim($this->profile_image, '/'));
        }
        $name = $this->user?->name ?? 'Team Member';
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=168536&color=fff&size=512&bold=true';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function batches()
    {
        return $this->belongsToMany(Batch::class, 'teacher_batch');
    }

    public function category()
    {
        return $this->belongsTo(TeacherCategory::class, 'category_id');
    }

    public function salaries()
    {
        return $this->hasMany(TeacherSalary::class);
    }
}
