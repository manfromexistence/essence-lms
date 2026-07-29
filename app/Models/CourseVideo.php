<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseVideo extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'video_path',
        'video_type',
        'external_id',
        'thumbnail',
        'duration',
        'order',
        'is_preview',
    ];

    protected $casts = [
        'is_preview' => 'boolean',
        'duration' => 'integer',
        'order' => 'integer',
    ];

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function views()
    {
        return $this->hasMany(VideoView::class);
    }

    // Helpers
    public function getVideoUrl()
    {
        if ($this->video_type === 'youtube') {
            return "https://www.youtube.com/embed/{$this->external_id}";
        } elseif ($this->video_type === 'vimeo') {
            return "https://player.vimeo.com/video/{$this->external_id}";
        } elseif ($this->video_type === 'facebook') {
            return "https://www.facebook.com/plugins/video.php?href=" . urlencode($this->external_id);
        } else {
            // Local upload
            return $this->video_path
                ? route('student.course.video.stream', [$this->course_id, $this->id])
                : null;
        }
    }

    public function getThumbnailUrl()
    {
        if ($this->thumbnail) {
            return asset('storage/' . $this->thumbnail);
        }
        
        // Default thumbnail based on video type
        if ($this->video_type === 'youtube' && $this->external_id) {
            return "https://img.youtube.com/vi/{$this->external_id}/hqdefault.jpg";
        }
        
        return asset('images/video-placeholder.png');
    }
}
