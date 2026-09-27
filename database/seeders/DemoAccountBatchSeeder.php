<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Student;
use Illuminate\Database\Seeder;

/**
 * Attaches the demo student accounts to a real batch.
 *
 * AdminUserSeeder creates the demo Student profiles, but it runs before
 * BatchSeeder, so it cannot know a batch id at that point. This seeder runs
 * afterwards and fills in the batch so the student portal has real content
 * (schedule, materials, exams) instead of empty states.
 *
 * Safe to run repeatedly: it only fills a missing batch_id and never
 * reassigns a student who already belongs to a batch.
 */
class DemoAccountBatchSeeder extends Seeder
{
    public function run(): void
    {
        $batchId = Batch::query()->orderBy('id')->value('id');

        if (!$batchId) {
            $this->command?->warn('No batches found. Skipping demo student batch assignment.');
            return;
        }

        $demoEmails = [
            'student@gmail.com',
            'student@dhakaitinstitute.test',
        ];

        $updated = Student::whereNull('batch_id')
            ->whereHas('user', fn ($query) => $query->whereIn('email', $demoEmails))
            ->update(['batch_id' => $batchId]);

        $this->command?->info("✓ Assigned {$updated} demo student(s) to batch #{$batchId}");
    }
}
