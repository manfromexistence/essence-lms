<?php

namespace Tests\Browser;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Students must be able to print / save their certificate as a PDF from the
 * certificate page.
 */
class CertificatePrintTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_student_can_trigger_certificate_printing(): void
    {
        $studentUser = $this->makeStudent();
        $student = Student::where('user_id', $studentUser->id)->firstOrFail();
        $course = Course::factory()->active()->create();

        $certificate = Certificate::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'certificate_number' => 'DIT-TEST-0001',
            'verification_code' => 'verify-dit-test-0001',
            'issued_at' => now(),
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($studentUser, $certificate, $course) {
            $browser->loginAs($studentUser)
                ->visit("/student/certificates/{$certificate->id}")
                ->assertSee($course->name)
                ->assertSee('DIT-TEST-0001')
                // Headless Chrome cannot open the print dialog; stub it and
                // verify the button really invokes window.print().
                ->script('window.print = () => { window.__printInvoked = true; };');

            $browser->click('button[onclick*="window.print"]')
                ->waitUsing(5, 100, fn () => $browser->assertScript('window.__printInvoked', true));
        });
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
