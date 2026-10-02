<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseMaterial extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'course_id',
        'title',
        'type',
        'file_path',
        'external_url',
        'description',
        'order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    /**
     * Get the course that owns the material.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the URL for the material (either file path or external URL).
     *
     * @return string|null
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->external_url) {
            return $this->external_url;
        }

        if ($this->file_path) {
            return media_url($this->file_path);
        }

        return null;
    }

    /**
     * Check if the material is a PDF.
     *
     * @return bool
     */
    public function isPdf(): bool
    {
        return $this->type === 'pdf';
    }

    /**
     * Check if the material is a video.
     *
     * @return bool
     */
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    /**
     * Check if the material is a document.
     *
     * @return bool
     */
    public function isDocument(): bool
    {
        return $this->type === 'document';
    }

    /**
     * Check if the material is an external link.
     *
     * @return bool
     */
    public function isLink(): bool
    {
        return $this->type === 'link';
    }

    /**
     * Check if the material has a file.
     *
     * @return bool
     */
    public function hasFile(): bool
    {
        return !empty($this->file_path);
    }

    /**
     * Check if the material has an external URL.
     *
     * @return bool
     */
    public function hasExternalUrl(): bool
    {
        return !empty($this->external_url);
    }

    /**
     * Scope a query to only include materials of a given type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to order by the material order.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
