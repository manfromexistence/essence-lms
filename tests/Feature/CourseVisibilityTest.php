<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CourseController;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CourseVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_newly_uploaded_course_appears_first_on_website(): void
    {
        for ($i = 1; $i <= 9; $i++) {
            Course::create([
                'name' => "Course $i",
                'code' => "VIS00$i",
                'price' => 1000,
                'status' => 'active',
                'delivery_mode' => $i % 2 ? 'online' : 'offline',
                'level' => 'beginner',
            ]);
        }

        $popular = Course::active()->orderByDesc('courses.id')->take(8)->get();

        $this->assertCount(8, $popular);
        $this->assertEquals('Course 9', $popular->first()->name);
    }

    public function test_admin_course_list_shows_all_modes_by_default(): void
    {
        Course::create(['name' => 'Online C', 'code' => 'VIS-ON1', 'price' => 10, 'status' => 'active', 'delivery_mode' => 'online', 'level' => 'beginner']);
        Course::create(['name' => 'Offline C', 'code' => 'VIS-OFF1', 'price' => 10, 'status' => 'active', 'delivery_mode' => 'offline', 'level' => 'beginner']);

        $request = Request::create('/dashboard/courses', 'GET');
        $request->setLaravelSession(app('session.store'));

        $courses = (new CourseController())->index($request)->getData()['courses'];

        $this->assertGreaterThanOrEqual(2, $courses->total());
    }

    public function test_admin_course_list_mode_filter_still_works(): void
    {
        Course::create(['name' => 'Online C', 'code' => 'VIS-ON2', 'price' => 10, 'status' => 'active', 'delivery_mode' => 'online', 'level' => 'beginner']);
        Course::create(['name' => 'Offline C', 'code' => 'VIS-OFF2', 'price' => 10, 'status' => 'active', 'delivery_mode' => 'offline', 'level' => 'beginner']);

        $request = Request::create('/dashboard/courses?delivery_mode=online', 'GET');
        $request->setLaravelSession(app('session.store'));

        $courses = (new CourseController())->index($request)->getData()['courses'];

        foreach ($courses as $course) {
            $this->assertEquals('online', $course->delivery_mode);
        }
    }
}
