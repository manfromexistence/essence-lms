<?php

namespace Tests\Browser;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseVideo;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\VideoView;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * When a lesson video ends, the player must save completion and navigate to
 * the next lesson automatically.
 */
class VideoAutoAdvanceTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_ended_video_saves_progress_and_advances_to_next_lesson(): void
    {
        Storage::disk('local')->makeDirectory('courses/videos');

        $studentUser = $this->makeStudent();
        $student = Student::where('user_id', $studentUser->id)->firstOrFail();

        $course = Course::factory()->active()->create(['delivery_mode' => 'online']);
        $first = CourseVideo::create([
            'course_id' => $course->id,
            'title' => 'Lesson One',
            'video_type' => 'upload',
            'video_path' => 'courses/videos/lesson-one.mp4',
            'duration' => 10,
            'order' => 1,
        ]);
        $second = CourseVideo::create([
            'course_id' => $course->id,
            'title' => 'Lesson Two',
            'video_type' => 'upload',
            'video_path' => 'courses/videos/lesson-two.mp4',
            'duration' => 10,
            'order' => 2,
        ]);

        // The stream endpoint only needs the file to exist for the player page.
        Storage::disk('local')->put($first->video_path, str_repeat("\x00", 64));
        Storage::disk('local')->put($second->video_path, str_repeat("\x00", 64));

        CourseEnrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($course, $first, $second) {
            $browser->visit("/student/courses/{$course->id}/watch/{$first->id}")
                ->assertVisible('#course-video')
                ->assertSee('Lesson One');

            // Simulate the browser firing the video "ended" event with a real
            // duration so the page's own auto-advance pipeline runs.
            $browser->script(<<<'JS'
                const video = document.getElementById('course-video');
                Object.defineProperty(video, 'duration', {value: 30, configurable: true});
                video.dispatchEvent(new Event('ended'));
            JS);

            $browser->waitForLocation("/student/courses/{$course->id}/watch/{$second->id}", 15)
                ->assertSee('Lesson Two');
        });

        $this->assertDatabaseHas('video_views', [
            'student_id' => $student->id,
            'course_video_id' => $first->id,
            'completed' => true,
        ]);
    }

    private function makeStudent(): User
    {
        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $user = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $user->roles()->attach($role);
        Student::factory()->create([
            'user_id' => $user->id,
            'batch_id' => null,
            'admission_status' => 'approved',
            'status' => 'active',
        ]);

        return $user;
    }
}
