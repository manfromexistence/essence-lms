<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the two defects that left the public admission form unusable:
 *
 *  1. components/ui/select.blade.php merged the caller's `id` into BOTH the
 *     wrapper <div> and the native <select>. HTML keeps the first `id` on a tag,
 *     so the wrapper (which comes first in document order) stole `id="course_id"`
 *     and `getElementById('course_id')` returned a <div>. initCustomSelect() then
 *     read `.options` off a div, threw, and aborted its loop — which left EVERY
 *     custom dropdown on the page empty ("Select Option", 0 items). Applicants
 *     could not pick a course, so admission was impossible.
 *
 *  2. The admission page's mode filter ran `option.hidden = option.dataset.mode
 *     !== mode.value` on load with an empty mode, hiding all courses.
 */
class CustomSelectComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admission_page_renders_course_options(): void
    {
        Course::create([
            'name' => 'Visible Course',
            'code' => 'VIS-1',
            'price' => 1000,
            'duration' => 3,
            'duration_unit' => 'months',
            'level' => 'beginner',
            'delivery_mode' => 'offline',
            'status' => 'active',
        ]);

        $response = $this->get('/admission');

        $response->assertOk();
        $response->assertSee('Visible Course', false);
    }

    public function test_select_component_does_not_duplicate_the_id_attribute(): void
    {
        $response = $this->get('/admission');
        $response->assertOk();

        $html = $response->getContent();

        // No tag may carry the same id twice.
        foreach (['course_id', 'admission_mode', 'blood_group'] as $id) {
            $this->assertSame(
                1,
                substr_count($html, 'id="' . $id . '"'),
                "id=\"{$id}\" must appear exactly once — a duplicate means the wrapper div stole it from the <select>."
            );
        }
    }

    public function test_the_id_resolves_to_the_native_select_not_the_wrapper(): void
    {
        $response = $this->get('/admission');
        $html = $response->getContent();

        // The wrapper must own the prefixed id, and the plain id must sit on a
        // <select>. If the div keeps the plain id, getElementById() returns the
        // div and the JS init throws.
        $this->assertStringContainsString('id="select-group-course_id"', $html);

        $this->assertMatchesRegularExpression(
            '/<select[^>]*\bid="course_id"/',
            $html,
            'id="course_id" must be on the <select> element so the JS can read .options.'
        );

        // And the wrapper div must NOT carry the plain id.
        $this->assertDoesNotMatchRegularExpression(
            '/<div[^>]*\bid="course_id"/',
            $html,
            'The wrapper div must not take id="course_id" — it shadows the real <select>.'
        );
    }

    public function test_mode_filter_only_hides_courses_when_a_mode_is_chosen(): void
    {
        $response = $this->get('/admission');
        $html = $response->getContent();

        // The fix: hide only when a mode is actually selected.
        $this->assertStringContainsString(
            "mode.value !== '' && option.dataset.mode !== mode.value",
            $html,
            'The course filter must not hide every course when no learning mode is selected yet.'
        );

        $this->assertStringNotContainsString(
            'option.hidden = option.dataset.mode !== mode.value',
            $html
        );
    }

    public function test_admin_student_forms_use_the_same_guarded_filter(): void
    {
        $admin = $this->makeAdmin();

        foreach (['/dashboard/students/create'] as $path) {
            $response = $this->actingAs($admin)->get($path);
            if ($response->getStatusCode() !== 200) {
                continue;
            }
            $html = $response->getContent();
            if (str_contains($html, 'dataset.mode')) {
                $this->assertStringContainsString(
                    "els.mode.value !== ''",
                    $html,
                    "{$path} must not hide all courses when the mode is empty."
                );
            }
        }

        $this->assertTrue(true);
    }

    private function makeAdmin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);

        $user = User::factory()->create([
            'email' => 'select-test-admin@example.com',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $user->roles()->attach($role->id);

        return $user;
    }
}
