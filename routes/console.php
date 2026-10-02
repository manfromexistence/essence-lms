<?php

use App\Models\Notification;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Queue monitoring
|--------------------------------------------------------------------------
|
| Fail closed loudly: when the default database queue grows beyond the
| threshold, queue:monitor exits non-zero and the failure handler raises a
| dashboard notification for admins so a stalled worker is noticed.
|
*/

Schedule::command('queue:monitor database:default --max=200')
    ->everyFifteenMinutes()
    ->onFailure(function () {
        Log::critical('Queue backlog exceeds threshold — check that a queue worker is running.');

        try {
            User::whereHas('roles', fn ($query) => $query->whereIn('slug', ['admin', 'super-admin']))
                ->pluck('id')
                ->each(fn ($userId) => Notification::create([
                    'user_id' => $userId,
                    'user_type' => 'admin',
                    'type' => 'queue_backlog_alert',
                    'title' => 'Queue backlog alert',
                    'message' => 'The background job queue has more than 200 pending jobs. Check that a queue worker is running.',
                    'data' => ['threshold' => 200],
                ]));
        } catch (\Throwable $e) {
            Log::error('Failed to create queue backlog notification', ['error' => $e->getMessage()]);
        }
    });

Schedule::command('queue:prune-failed --hours=720')->daily();

/*
|--------------------------------------------------------------------------
| Exam deadlines
|--------------------------------------------------------------------------
|
| autoSubmitExpired() previously had no callers, so an attempt left open when a
| student closed their browser stayed 'in_progress' forever. The deadline is
| also enforced inline on save and submit, so this is a housekeeping backstop
| rather than the thing making the deadline stick.
*/

Schedule::command('exams:auto-submit-expired')->everyFiveMinutes();

/*
|--------------------------------------------------------------------------
| Report export housekeeping
|--------------------------------------------------------------------------
|
| Removes the database rows for generated exports after 7 days.
|
| The exported file itself is hosted on Catbox, which cannot delete without an
| account key, so this no longer reduces data-retention exposure for the file —
| it only stops the portal advertising the link. Anyone holding a previously
| issued export URL keeps access to it. If exports must genuinely expire, they
| need to be hosted somewhere the application controls.
|
*/

Schedule::call(function () {
    ReportExport::where('created_at', '<', now()->subDays(7))
        ->get()
        ->each(function (ReportExport $export) {
            if ($export->path) {
                // Best-effort: a no-op with a log line for Catbox-hosted exports.
                Storage::disk($export->disk)->delete($export->path);
            }
            $export->delete();
        });
})->dailyAt('02:30')->name('prune-report-exports');
