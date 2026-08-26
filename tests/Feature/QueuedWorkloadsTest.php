<?php

namespace Tests\Feature;

use App\Jobs\GenerateReportExportJob;
use App\Jobs\SendBulkEmailsJob;
use App\Jobs\SendBulkSmsJob;
use App\Models\ReportExport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QueuedWorkloadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_email_is_dispatched_to_queue(): void
    {
        Queue::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->postJson('/dashboard/email/send-bulk', [
            'recipient_type' => 'custom',
            'custom_emails' => 'a@example.com, b@example.com',
            'subject' => 'Batch update',
            'message' => 'Classes move online this week.',
        ])->assertSuccessful()->assertJsonPath('data.queued', true);

        Queue::assertPushed(SendBulkEmailsJob::class);
    }

    public function test_single_email_is_dispatched_to_queue(): void
    {
        Queue::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/dashboard/email/send', [
            'email' => 'parent@example.com',
            'subject' => 'Fee reminder',
            'message' => 'Please clear the due amount.',
        ])->assertRedirect()->assertSessionHas('success');

        Queue::assertPushed(\App\Jobs\SendEmailJob::class);
    }

    public function test_bulk_sms_is_dispatched_to_queue(): void
    {
        Queue::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->postJson('/dashboard/communication/send-bulk', [
            'message' => 'Exam tomorrow at 10 AM.',
            'recipients' => ['01712345678', '01898765432'],
        ])->assertSuccessful()->assertJsonPath('data.queued', true);

        Queue::assertPushed(SendBulkSmsJob::class);
    }

    public function test_report_export_request_is_queued_and_recorded(): void
    {
        Queue::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/dashboard/reports/export-excel', [
            'report_type' => 'student',
        ])->assertRedirect()->assertSessionHas('success');

        $export = ReportExport::first();
        $this->assertNotNull($export);
        $this->assertSame('student', $export->report_type);
        $this->assertSame('xlsx', $export->format);
        $this->assertSame(ReportExport::STATUS_PENDING, $export->status);
        $this->assertSame($admin->id, $export->requested_by);

        Queue::assertPushed(GenerateReportExportJob::class);
    }

    public function test_export_job_generates_file_and_notifies_requester(): void
    {
        Storage::fake('local');
        config(['filesystems.private' => 'local']);
        $admin = $this->makeAdmin();

        $export = ReportExport::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'requested_by' => $admin->id,
            'report_type' => 'student',
            'format' => 'xlsx',
            'filters' => [],
            'status' => ReportExport::STATUS_PENDING,
            'disk' => 'local',
            'filename' => 'Student_Report.xlsx',
        ]);

        (new GenerateReportExportJob($export))->handle(app(\App\Services\ExportService::class));

        $export->refresh();
        $this->assertSame(ReportExport::STATUS_COMPLETED, $export->status);
        Storage::disk('local')->assertExists($export->path);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'report_export_ready',
        ]);
    }

    public function test_only_owner_or_super_admin_can_download_export(): void
    {
        Storage::fake('local');
        config(['filesystems.private' => 'local']);

        $owner = $this->makeAdmin();
        $other = $this->makeAdmin();
        $super = $this->makeSuperAdmin();

        $export = ReportExport::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'requested_by' => $owner->id,
            'report_type' => 'student',
            'format' => 'xlsx',
            'filters' => [],
            'status' => ReportExport::STATUS_COMPLETED,
            'disk' => 'local',
            'path' => 'report-exports/test.xlsx',
            'filename' => 'Student_Report.xlsx',
        ]);
        Storage::disk('local')->put('report-exports/test.xlsx', 'binary-content');

        $this->actingAs($other)
            ->get("/dashboard/reports/exports/{$export->uuid}/download")
            ->assertForbidden();

        $this->actingAs($super)
            ->get("/dashboard/reports/exports/{$export->uuid}/download")
            ->assertSuccessful();

        $this->actingAs($owner)
            ->get("/dashboard/reports/exports/{$export->uuid}/download")
            ->assertSuccessful();
    }

    private function makeAdmin(): User
    {
        return $this->makeUserWithRole('admin');
    }

    private function makeSuperAdmin(): User
    {
        return $this->makeUserWithRole('super-admin');
    }

    private function makeUserWithRole(string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucwords(str_replace('-', ' ', $slug))]);
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->roles()->attach($role);

        return $user;
    }
}
