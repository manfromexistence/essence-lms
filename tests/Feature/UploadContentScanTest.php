<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadContentScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_proof_with_embedded_php_is_rejected(): void
    {
        config(['payment-methods.methods.bkash.number' => '01700000000']);

        $studentUser = $this->makeStudent();
        $course = Course::factory()->active()->create(['price' => 5000]);

        $polyglot = UploadedFile::fake()->createWithContent(
            'proof.png',
            "\x89PNG\r\n\x1A\n<?php system(\$_GET['c']); ?>"
        );

        $this->actingAs($studentUser)->post('/student/payment/submit', [
            'course_id' => $course->id,
            'payment_method' => 'bkash',
            'transaction_id' => 'TRX9ZZZ999',
            'sender_number' => '01712345678',
            'screenshot' => $polyglot,
        ])->assertSessionHasErrors(['screenshot']);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_valid_payment_proof_is_stored_on_private_disk(): void
    {
        Storage::fake('local');
        config(['filesystems.private' => 'local']);
        config(['payment-methods.methods.bkash.number' => '01700000000']);

        $studentUser = $this->makeStudent();
        $course = Course::factory()->active()->create(['price' => 5000]);

        $proof = UploadedFile::fake()->createWithContent(
            'proof.png',
            "\x89PNG\r\n\x1A\n" . str_repeat('A', 256)
        );

        $this->actingAs($studentUser)->post('/student/payment/submit', [
            'course_id' => $course->id,
            'payment_method' => 'bkash',
            'transaction_id' => 'TRX8AAA888',
            'sender_number' => '01712345678',
            'screenshot' => $proof,
        ])->assertRedirect();

        $payment = Payment::where('transaction_id', 'TRX8AAA888')->first();
        $this->assertNotNull($payment);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        Storage::disk('local')->assertExists($payment->screenshot_path);
    }

    private function makeStudent(): User
    {
        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);
        Student::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
