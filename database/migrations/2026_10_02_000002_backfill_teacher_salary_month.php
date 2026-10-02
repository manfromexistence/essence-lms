<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills teacher_salaries.month from payment_date.
 *
 * The column has been NOT NULL since the table was created and carries
 * unique(teacher_id, month), but no application code ever wrote it: store() and
 * update() both passed the validated array straight to create()/update() without
 * a `month` key. So:
 *
 *   - every salary insert raised a NOT NULL violation, i.e. payroll recording
 *     never worked (the seeded rows survived only because the seeder set the
 *     column by hand);
 *   - the unique index was inert, so the read-then-insert duplicate check was
 *     the only protection against paying a teacher twice for one month, and it
 *     races.
 *
 * Deriving the value fixes both. Existing rows are backfilled here so the index
 * can be trusted, and so this migration never fails on a NOT NULL violation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_salaries')) {
            return;
        }

        DB::table('teacher_salaries')
            ->select('id', 'payment_date')
            ->orderBy('id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $month = $row->payment_date
                        ? substr((string) $row->payment_date, 0, 7)
                        : null;

                    if ($month === null || $month === '') {
                        continue;
                    }

                    DB::table('teacher_salaries')
                        ->where('id', $row->id)
                        ->update(['month' => $month]);
                }
            });
    }

    public function down(): void
    {
        // Nothing to undo: month is derived from payment_date and is required by
        // the schema. Blanking it would re-break salary inserts.
    }
};
