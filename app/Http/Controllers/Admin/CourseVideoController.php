<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseVideoController extends Controller
{
    public function index(Course $course)
    {
        $videos = $course->videos()->orderBy('order')->get();
        return view('dashboard.courses.videos.index', compact('course', 'videos'));
    }

    public function create(Course $course)
    {
        return view('dashboard.courses.videos.create', compact('course'));
    }

    public function store(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_type' => 'required|in:upload,youtube,vimeo,facebook',
            'video_file' => 'required_if:video_type,upload|nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo|max:512000', // 500MB
            'external_id' => 'required_if:video_type,youtube,vimeo,facebook|nullable|string',
            'thumbnail_file' => 'nullable|image|max:2048',
            'duration' => 'nullable|integer|min:0',
            'is_preview' => 'nullable|boolean',
        ]);

        $videoData = [
            'course_id' => $course->id,
            'title' => $request->title,
            'description' => $request->description,
            'video_type' => $request->video_type,
            'duration' => $request->duration,
            'is_preview' => $request->has('is_preview'),
            'order' => $course->videos()->max('order') + 1,
        ];

        // Handle External ID extraction from URL
        if (in_array($request->video_type, ['youtube', 'vimeo', 'facebook']) && $request->external_id) {
            $url = $request->external_id;
            $id = $url;
            
            if ($request->video_type === 'youtube') {
                // Extract YouTube ID
                if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
                    $id = $matches[1];
                }
            } elseif ($request->video_type === 'vimeo') {
                // Extract Vimeo ID
                if (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)/', $url, $matches)) {
                    $id = $matches[1];
                }
            } elseif ($request->video_type === 'facebook') {
                // For Facebook, store the full URL
                $id = $url;
            }
            
            $videoData['external_id'] = $id;
        }

        if ($request->video_type === 'upload' && $request->hasFile('video_file')) {
            $videoData['video_path'] = $request->file('video_file')->store('courses/videos', config('filesystems.private'));
        }

        if ($request->hasFile('thumbnail_file')) {
            $videoData['thumbnail'] = $request->file('thumbnail_file')->store('courses/thumbnails', 'public');
        }

        CourseVideo::create($videoData);

        return redirect()->route('dashboard.courses.edit', $course)
            ->with('success', 'Video added successfully.');
    }

    public function edit(Course $course, CourseVideo $video)
    {
        abort_unless($video->course_id === $course->id, 404);
        return view('dashboard.courses.videos.edit', compact('video', 'course'));
    }

    public function update(Request $request, Course $course, CourseVideo $video)
    {
        abort_unless($video->course_id === $course->id, 404);
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_type' => 'required|in:upload,youtube,vimeo,facebook',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo|max:512000',
            'external_id' => 'required_if:video_type,youtube,vimeo,facebook|nullable|string',
            'thumbnail_file' => 'nullable|image|max:2048',
            'duration' => 'nullable|integer|min:0',
            'is_preview' => 'nullable|boolean',
        ]);

        $updateData = [
            'title' => $request->title,
            'description' => $request->description,
            'video_type' => $request->video_type,
            'duration' => $request->duration,
            'is_preview' => $request->has('is_preview'),
        ];

        // Handle External ID change
        if (in_array($request->video_type, ['youtube', 'vimeo', 'facebook'])) {
            $url = $request->external_id;
            $id = $url;
            
            if ($request->video_type === 'youtube') {
                if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
                    $id = $matches[1];
                }
            } elseif ($request->video_type === 'vimeo') {
                if (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)/', $url, $matches)) {
                    $id = $matches[1];
                }
            } elseif ($request->video_type === 'facebook') {
                $id = $url;
            }
            
            $updateData['external_id'] = $id;
            $updateData['video_path'] = null; // clear path if validation passes
        }

        // Handle Video File Upload
        if ($request->video_type === 'upload' && $request->hasFile('video_file')) {
            // Delete old file
            if ($video->video_path) {
                Storage::disk(config('filesystems.private'))->delete($video->video_path);
            }
            $updateData['video_path'] = $request->file('video_file')->store('courses/videos', config('filesystems.private'));
            $updateData['external_id'] = null;
        }

        // Handle Thumbnail Upload
        if ($request->hasFile('thumbnail_file')) {
            // Delete old thumbnail
            if ($video->thumbnail) {
                Storage::disk('public')->delete($video->thumbnail);
            }
            $updateData['thumbnail'] = $request->file('thumbnail_file')->store('courses/thumbnails', 'public');
        }

        $video->update($updateData);

        return redirect()->route('dashboard.courses.videos.index', $course)
            ->with('success', 'Video updated successfully.');
    }

    public function destroy(Course $course, CourseVideo $video)
    {
        abort_unless($video->course_id === $course->id, 404);
        // Delete files
        if ($video->video_path) {
            Storage::disk(config('filesystems.private'))->delete($video->video_path);
        }
        if ($video->thumbnail) {
            Storage::disk('public')->delete($video->thumbnail);
        }

        $video->delete();

        return redirect()->route('dashboard.courses.edit', $course)
            ->with('success', 'Video deleted successfully.');
    }

    public function reorder(Request $request, Course $course)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:course_videos,id',
        ]);

        foreach ($request->order as $index => $videoId) {
            CourseVideo::where('id', $videoId)
                ->where('course_id', $course->id)
                ->update(['order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }
}
