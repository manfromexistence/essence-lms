<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use App\Models\Student;
use App\Services\StudentIdGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the student registration-number sequence generator.
 *
 * Bug (found live on Render): the second and every subsequent admitted student
 * returned HTTP 500 with
 *
 *   TypeError: App\Services\StudentIdGenerator::getNextSequence():
 *              Return value must be of type int, float returned
 *
 * Cause: `getNextSequence()` did `(int) $matches[1] + 1`. For a registration
 * number whose trailing digit run is longer than PHP_INT_MAX, the `(int)` cast
 * saturates to PHP_INT_MAX and the `+ 1` overflows into a *float*, which the
 * strict `: int` return type rejects on PHP 8.3 — killing the whole request.
 */
class StudentIdGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_next_sequence_returns_int_for_normal_registration_numbers(): void
    {
        Student::factory()->create([
            'registration_no' => '2026-STU-0020',
            'created_at' => now(),
        ]);

        $generator = app(StudentIdGenerator::class);
        $sequence = $generator->getNextSequence();

        $this->assertIsInt($sequence);
        $this->assertSame(21, $sequence);
    }

    public function test_get_next_sequence_never_returns_a_float_when_the_digit_run_overflows(): void
    {
        // A trailing digit run far longer than PHP_INT_MAX. The old code cast
        // this to PHP_INT_MAX and then overflowed to float on `+ 1`.
        Student::factory()->create([
            'registration_no' => '2026-99999999999999999999',
            'created_at' => now(),
        ]);

        $generator = app(StudentIdGenerator::class);
        $sequence = $generator->getNextSequence();

        $this->assertIsInt($sequence, 'getNextSequence() must never return a float.');
        $this->assertGreaterThan(0, $sequence);
        $this->assertLessThanOrEqual(PHP_INT_MAX, $sequence);
    }

    public function test_second_admission_succeeds_after_a_student_already_exists(): void
    {
        // The live failure only happened from the *second* admission onwards,
        // so this test deliberately admits twice.
        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);

        $course = Course::factory()->create([
            'status' => 'active',
            'delivery_mode' => 'offline',
        ]);

        // First applicant — must create the very first student row.
        $this->post('/admission', [
            'name_bn' => 'First Applicant',
            'email' => 'first.applicant@example.com',
            'phone' => '01700000001',
            'admission_mode' => 'offline',
            'course_id' => $course->id,
        ])->assertRedirect('/login');

        // Second applicant — this is where the live site returned 500.
        $this->post('/admission', [
            'name_bn' => 'Second Applicant',
            'email' => 'second.applicant@example.com',
            'phone' => '01700000002',
            'admission_mode' => 'offline',
            'course_id' => $course->id,
        ])->assertRedirect('/login');

        $this->assertDatabaseHas('users', ['email' => 'first.applicant@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'second.applicant@example.com']);
        $this->assertSame(2, Student::whereIn('name_bn', ['First Applicant', 'Second Applicant'])->count());
    }

    public function test_admission_does_not_500_when_a_legacy_registration_number_has_a_huge_tail(): void
    {
        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);

        $course = Course::factory()->create([
            'status' => 'active',
            'delivery_mode' => 'online',
        ]);

        // Simulate a legacy/corrupt registration number already in the table.
        Student::factory()->create([
            'registration_no' => 'LEGACY-9999999999999999999999',
            'created_at' => now(),
        ]);

        $this->post('/admission', [
            'name_bn' => 'After Legacy Row',
            'email' => 'after.legacy@example.com',
            'phone' => '01700000003',
            'admission_mode' => 'online',
            'course_id' => $course->id,
        ])->assertRedirect('/login');

        $this->assertDatabaseHas('users', ['email' => 'after.legacy@example.com']);
    }

    /**
     * Bug #2 (found live immediately after the overflow fix landed): every
     * admission past the first returned HTTP 500 with
     *
     *   RuntimeException: Unable to generate unique student ID after 10 attempts
     *
     * Cause: getNextSequence() read the *last row by id* and incremented its
     * trailing digits. Because a second transaction already held a row whose
     * tail equalled the value being generated, all ten retries inside
     * generateUnique() recomputed the exact same colliding ID and the loop
     * exhausted itself.
     */
    public function test_admission_succeeds_when_the_latest_row_collides_with_the_next_sequence(): void
    {
        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);

        $course = Course::factory()->create([
            'status' => 'active',
            'delivery_mode' => 'online',
        ]);

        // This is precisely the live state: the most recent row carries the
        // registration number the generator would produce next.
        Student::factory()->create([
            'registration_no' => (string) date('Y') . '00000021',
            'created_at' => now(),
        ]);
        Student::factory()->create([
            'registration_no' => (string) date('Y') . '00000022',
            'created_at' => now(),
        ]);

        $this->post('/admission', [
            'name_bn' => 'Collision Survivor',
            'email' => 'collision.survivor@example.com',
            'phone' => '01700000004',
            'admission_mode' => 'online',
            'course_id' => $course->id,
        ])->assertRedirect('/login');

        $this->assertDatabaseHas('users', ['email' => 'collision.survivor@example.com']);
    }

    public function test_next_sequence_uses_the_highest_number_not_the_last_row(): void
    {
        // Insert the *highest* number first and the lowest last, so "last row"
        // and "highest number" disagree.
        Student::factory()->create([
            'registration_no' => date('Y') . '-STU-0040',
            'created_at' => now()->subMinutes(5),
        ]);
        Student::factory()->create([
            'registration_no' => date('Y') . '-STU-0003',
            'created_at' => now(),
        ]);

        $this->assertSame(41, app(StudentIdGenerator::class)->getNextSequence());
    }

    public function test_generate_unique_steps_past_an_occupied_sequence(): void
    {
        // Occupy the first three values the generator would try. The default
        // pattern is {YEAR}{BATCH}{SEQ:4} -> e.g. "20260000021".
        foreach ([21, 22, 23] as $tail) {
            Student::factory()->create([
                'registration_no' => date('Y') . '00' . str_pad((string) $tail, 4, '0', STR_PAD_LEFT),
                'created_at' => now(),
            ]);
        }

        $generator = app(StudentIdGenerator::class);
        $id = $generator->generateUnique();

        // Highest value is 23, so the next free slot is 24 (the trailing
        // {SEQ:4} block).
        $this->assertSame('0024', substr($id, -4));
        $this->assertFalse($generator->exists($id));
    }
}
