<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseVideo;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\VideoView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the video-completion trust boundary.
 *
 * `completeVideo` used to take `watched_seconds` straight from the request and
 * compare it to the video duration, so a single POST of a large number per
 * video marked every lesson complete. Because CertificateService treats "all
 * videos complete" as finishing the course, that issued a real, publicly
 * verifiable certificate with no playback at all.
 *
 * Watch time is now credited from the server's clock, bounded by how long the
 * player has actually been open.
 */
class VideoCompletionTrustTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;

    private Student $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $this->studentUser = User::factory()->create(['is_active' => true]);
        $this->studentUser->roles()->attach($role);

        $this->student = Student::factory()->create(['user_id' => $this->studentUser->id]);

        $this->course = Course::factory()->active()->create();

        CourseEnrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrolled_at' => now(),
        ]);
    }

    private function makeVideo(int $order = 1, int $duration = 600): CourseVideo
    {
        return CourseVideo::create([
            'course_id' => $this->course->id,
            'title' => 'Lesson ' . $order,
            'video_type' => 'youtube',
            'external_id' => 'dQw4w9WgXcQ',
            'duration' => $duration,
            'order' => $order,
        ]);
    }

    private function complete(CourseVideo $video, int $watchedSeconds): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->studentUser)->postJson(
            "/student/courses/{$this->course->id}/watch/{$video->id}/complete",
            ['watched_seconds' => $watchedSeconds]
        );
    }

    private function backdateOpen(CourseVideo $video, int $seconds): void
    {
        VideoView::where('student_id', $this->student->id)
            ->where('course_video_id', $video->id)
            ->update(['last_watched_at' => now()->subSeconds($seconds)]);
    }

    /**
     * The exploit: open nothing, POST a huge number, get a certificate.
     */
    public function test_a_forged_watch_time_does_not_complete_a_lesson(): void
    {
        $video = $this->makeVideo();

        $this->complete($video, 999_999)
            ->assertStatus(422)
            ->assertJsonPath('completed', false);

        $this->assertDatabaseMissing('video_views', [
            'student_id' => $this->student->id,
            'course_video_id' => $video->id,
            'completed' => true,
        ]);

        $this->assertSame(0, Certificate::count(), 'No certificate may be issued from a forged watch time.');
    }

    public function test_repeated_forged_attempts_cannot_bank_credit(): void
    {
        $video = $this->makeVideo();

        // Spamming must not accumulate credit faster than real time passes.
        for ($i = 0; $i < 25; $i++) {
            $this->complete($video, 999_999);
        }

        $this->assertDatabaseMissing('video_views', [
            'student_id' => $this->student->id,
            'course_video_id' => $video->id,
            'completed' => true,
        ]);
    }

    public function test_a_genuine_viewer_still_completes_the_lesson(): void
    {
        $video = $this->makeVideo(duration: 600);

        $this->actingAs($this->studentUser)
            ->get("/student/courses/{$this->course->id}/watch/{$video->id}")
            ->assertOk();

        $this->backdateOpen($video, 600);

        $this->complete($video, 600)
            ->assertSuccessful()
            ->assertJsonPath('completed', true);

        $this->assertDatabaseHas('video_views', [
            'student_id' => $this->student->id,
            'course_video_id' => $video->id,
            'completed' => true,
        ]);
    }

    public function test_a_genuine_viewer_of_the_last_lesson_still_gets_a_certificate(): void
    {
        $video = $this->makeVideo(duration: 120);

        $this->actingAs($this->studentUser)
            ->get("/student/courses/{$this->course->id}/watch/{$video->id}")
            ->assertOk();
        $this->backdateOpen($video, 120);

        $response = $this->complete($video, 120)->assertSuccessful();

        $this->assertNotNull($response->json('certificate_url'));
        $this->assertSame(1, Certificate::count());
    }

    public function test_credited_time_never_exceeds_the_video_length(): void
    {
        $video = $this->makeVideo(duration: 30);

        $this->actingAs($this->studentUser)
            ->get("/student/courses/{$this->course->id}/watch/{$video->id}")
            ->assertOk();
        $this->backdateOpen($video, 3_600);

        $this->complete($video, 3_600)->assertSuccessful();

        $view = VideoView::where('student_id', $this->student->id)
            ->where('course_video_id', $video->id)
            ->firstOrFail();

        $this->assertLessThanOrEqual(30, (int) $view->watched_seconds);
    }

    public function test_opening_a_lesson_records_progress_without_completing_it(): void
    {
        $video = $this->makeVideo();

        $this->actingAs($this->studentUser)
            ->get("/student/courses/{$this->course->id}/watch/{$video->id}")
            ->assertOk();

        $view = VideoView::where('student_id', $this->student->id)
            ->where('course_video_id', $video->id)
            ->firstOrFail();

        $this->assertFalse((bool) $view->completed, 'Opening a lesson must not mark it complete.');
        $this->assertNotNull($view->last_watched_at);
    }

    public function test_revisiting_a_completed_lesson_keeps_its_progress(): void
    {
        $video = $this->makeVideo(duration: 100);

        $this->actingAs($this->studentUser)
            ->get("/student/courses/{$this->course->id}/watch/{$video->id}")->assertOk();
        $this->backdateOpen($video, 100);
        $this->complete($video, 100)->assertSuccessful();

        $before = VideoView::where('student_id', $this->student->id)
            ->where('course_video_id', $video->id)->firstOrFail();

        $this->travel(5)->minutes();

        $this->actingAs($this->studentUser)
            ->get("/student/courses/{$this->course->id}/watch/{$video->id}")->assertOk();

        $after = VideoView::where('student_id', $this->student->id)
            ->where('course_video_id', $video->id)->firstOrFail();

        $this->assertTrue((bool) $after->completed);
        $this->assertSame((int) $before->watched_seconds, (int) $after->watched_seconds);
    }

    public function test_earlier_lessons_still_have_to_be_completed_first(): void
    {
        $first = $this->makeVideo(order: 1, duration: 60);
        $second = $this->makeVideo(order: 2, duration: 60);

        $this->actingAs($this->studentUser)
            ->get("/student/courses/{$this->course->id}/watch/{$second->id}")->assertOk();
        $this->backdateOpen($second, 60);

        $this->complete($second, 60)
            ->assertStatus(422)
            ->assertJsonPath('completed', false);
    }
}