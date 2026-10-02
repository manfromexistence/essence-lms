<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesHostedMedia;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseVideo;
use App\Storage\CatboxStorage;
use App\Storage\CatboxUploadFailed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class CourseVideoController extends Controller
{
    use HandlesHostedMedia;

    /**
     * Formats accepted for course video.
     *
     * @var array<int, string>
     */
    private const VIDEO_TYPES = ['mp4', 'm4v', 'mov', 'webm'];

    /** MIME types the browser-side <video> element reliably decodes. */
    private const VIDEO_MIMES = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm'];

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
            'video_file' => [
                'required_if:video_type,upload', 'nullable',
                File::types(self::VIDEO_MIMES)->max($this->maxUploadKb()),
                new \App\Rules\SafeUpload(self::VIDEO_TYPES),
            ],
            'external_id' => 'required_if:video_type,youtube,vimeo,facebook|nullable|string',
            'thumbnail_file' => ['nullable', 'image', 'max:2048', new \App\Rules\SafeUpload(['jpg', 'jpeg', 'png', 'gif', 'webp'])],
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

        if (in_array($request->video_type, ['youtube', 'vimeo', 'facebook'], true) && $request->external_id) {
            $videoData['external_id'] = $this->externalIdFor($request->video_type, $request->external_id);
        }

        if ($request->video_type === 'upload' && $request->hasFile('video_file')) {
            $videoData['video_path'] = $this->hostVideo($request->file('video_file'));
        }

        if ($request->hasFile('thumbnail_file')) {
            $videoData['thumbnail'] = $this->hostImage($request->file('thumbnail_file'), 'courses/thumbnails');
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
            'video_file' => [
                'nullable',
                File::types(self::VIDEO_MIMES)->max($this->maxUploadKb()),
                new \App\Rules\SafeUpload(self::VIDEO_TYPES),
            ],
            'external_id' => 'required_if:video_type,youtube,vimeo,facebook|nullable|string',
            'thumbnail_file' => ['nullable', 'image', 'max:2048', new \App\Rules\SafeUpload(['jpg', 'jpeg', 'png', 'gif', 'webp'])],
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

        // Switching to an embedded provider drops any file we were serving.
        if (in_array($request->video_type, ['youtube', 'vimeo', 'facebook'], true)) {
            $this->unlinkMedia($video->video_path);
            $updateData['external_id'] = $this->externalIdFor($request->video_type, $request->external_id);
            $updateData['video_path'] = null;
        }

        if ($request->video_type === 'upload' && $request->hasFile('video_file')) {
            $this->unlinkMedia($video->video_path);
            $updateData['video_path'] = $this->hostVideo($request->file('video_file'));
            $updateData['external_id'] = null;
        }

        if ($request->hasFile('thumbnail_file')) {
            $this->unlinkMedia($video->thumbnail);
            $updateData['thumbnail'] = $this->hostImage($request->file('thumbnail_file'), 'courses/thumbnails');
        }

        $video->update($updateData);

        return redirect()->route('dashboard.courses.videos.index', $course)
            ->with('success', 'Video updated successfully.');
    }

    public function destroy(Course $course, CourseVideo $video)
    {
        abort_unless($video->course_id === $course->id, 404);

        $this->unlinkMedia($video->video_path, $video->thumbnail);

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

    /**
     * Send the browser to the hosted video (admin preview).
     *
     * A redirect rather than a proxied stream: the media host serves HTTP Range
     * itself, so seeking and resuming work as they normally would, whereas
     * pushing hundreds of megabytes through PHP would exhaust the request
     * timeout and hold a worker for the duration.
     */
    public function stream(Course $course, CourseVideo $video): RedirectResponse
    {
        abort_unless($video->course_id === $course->id, 404);

        $storage = app(CatboxStorage::class);

        abort_unless($video->video_path && $storage->isRemote($video->video_path), 404);

        return redirect()->away($storage->url($video->video_path));
    }

    /**
     * The provider's own identifier for an embed URL.
     */
    private function externalIdFor(string $type, string $input): string
    {
        return match ($type) {
            'youtube' => $this->youtubeId($input) ?? $input,
            'vimeo' => $this->vimeoId($input) ?? $input,
            // Facebook embeds are referenced by URL.
            default => $input,
        };
    }

    private function youtubeId(string $url): ?string
    {
        return preg_match(
            '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/',
            $url,
            $m
        ) === 1 ? $m[1] : null;
    }

    private function vimeoId(string $url): ?string
    {
        return preg_match(
            '/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)/',
            $url,
            $m
        ) === 1 ? $m[1] : null;
    }

    /**
     * Host an uploaded lecture.
     *
     * Course video is the reason media lives on Catbox at all, so the failure
     * message names the size limit rather than surfacing a generic HTTP error.
     */
    private function hostVideo(\Illuminate\Http\UploadedFile $file): string
    {
        try {
            return app(CatboxStorage::class)->store($file, 'courses/videos', $file->getClientOriginalName());
        } catch (CatboxUploadFailed $e) {
            throw ValidationException::withMessages([
                'video_file' => $e->getMessage(),
            ]);
        }
    }

    private function hostImage(\Illuminate\Http\UploadedFile $file, string $directory): string
    {
        try {
            return app(CatboxStorage::class)->store($file, $directory, $file->getClientOriginalName());
        } catch (CatboxUploadFailed $e) {
            throw ValidationException::withMessages(['thumbnail_file' => $e->getMessage()]);
        }
    }

    /**
     * The host's own ceiling, in kilobytes.
     *
     * Catbox rejects anything over 200 MB. Validating against our own larger
     * limit instead would only move the failure to the upload, after the user
     * had already waited out the whole transfer.
     */
    private function maxUploadKb(): int
    {
        return (int) config('media.max_upload_kb', 200 * 1024);
    }
}