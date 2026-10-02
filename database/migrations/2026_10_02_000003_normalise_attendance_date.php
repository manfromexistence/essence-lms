<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalises attendances.date to a bare calendar day.
 *
 * The column has always been a DATE, but Attendance::$casts used the plain
 * 'date' cast, which serialises to 'Y-m-d 00:00:00'. On SQLite — the driver
 * this project develops and tests on — that literal is what got stored, while
 * every query filtered on a bare 'Y-m-d'.
 *
 * The two representations are different strings, so:
 *
 *   - updateOrCreate(['date' => '2026-10-01'], ...) never matched a stored
 *     '2026-10-01 00:00:00' row, fell through to an INSERT, and hit the
 *     (student_id, batch_id, date) unique index -> a teacher could not correct a
 *     single day they had already marked;
 *   - with no unique index to stop it, concurrent marking inserted duplicate
 *     rows that then double-counted in both numerator and denominator.
 *
 * The cast is now 'date:Y-m-d'. On MySQL and Postgres the column already
 * truncated to a date, so this only affects SQLite and is a no-op there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        $driver = DB::getDriverName();

        // Only SQLite stores the time component; a DATE column elsewhere already
        // discarded it.
        if ($driver !== 'sqlite') {
            return;
        }

        DB::table('attendances')->select('id', 'date')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $value = (string) $row->date;

                if ($value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
                    continue;
                }

                DB::table('attendances')
                    ->where('id', $row->id)
                    ->update(['date' => substr($value, 0, 10)]);
            }
        });

        // Now that the values are unique per student/batch/day, collapse any
        // duplicates that predate the unique index, keeping the earliest row.
        $duplicates = DB::select(
            'SELECT student_id, batch_id, date, MIN(id) AS keep_id, COUNT(*) AS n
             FROM attendances
             GROUP BY student_id, batch_id, date
             HAVING n > 1'
        );

        foreach ($duplicates as $duplicate) {
            $ids = DB::table('attendances')
                ->where('student_id', $duplicate->student_id)
                ->where('batch_id', $duplicate->batch_id)
                ->where('date', $duplicate->date)
                ->where('id', '>', $duplicate->keep_id)
                ->pluck('id');

            if ($ids->isNotEmpty()) {
                DB::table('attendances')->whereIn('id', $ids)->delete();
            }
        }
    }

    public function down(): void
    {
        // Nothing to undo: the trimmed value is the same calendar day, and
        // re-adding a time component would re-break the unique index.
    }
};
