<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs class_schedules.day_of_week, which had two incompatible encodings.
 *
 * The column is an enum of weekday NAMES, but ScheduleController validated it as
 * `integer|min:0|max:6` and both schedule forms posted 0-6. So the same column
 * held 'wednesday' (from the seeder) and '3' (from the admin UI).
 *
 * The consequences were silent rather than loud. On SQLite the enum has no CHECK
 * constraint, so the digits were stored as text and nothing errored; on MySQL
 * and Postgres, where the column really is an enum, the same POST would have
 * failed outright. Either way four screens disagreed with each other:
 *
 *   schedules/index   compared against 0-6 integers  -> seeder rows never rendered
 *   TeacherController compared against now()->format('l') -> admin rows never rendered
 *   ClassSchedule::isToday / scopeToday                -> admin rows always false
 *   student/schedule  $days[$schedule->day_of_week]    -> admin rows showed "N/A"
 *
 * Validation and both forms now use the names, so this converts the digits that
 * are already stored. Reversible, but the down() is lossy in the same way the
 * original data was.
 */
return new class extends Migration
{
    /** Numeric index (Carbon's default) to weekday name. */
    private const DAYS = [
        0 => 'sunday',
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('class_schedules')) {
            return;
        }

        foreach (DB::table('class_schedules')->select('id', 'day_of_week')->get() as $row) {
            $value = (string) $row->day_of_week;

            // Already a name, or empty: nothing to do.
            if ($value === '' || ! ctype_digit($value)) {
                continue;
            }

            $name = self::DAYS[(int) $value] ?? null;

            if ($name === null) {
                continue;
            }

            DB::table('class_schedules')
                ->where('id', $row->id)
                ->update(['day_of_week' => $name]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('class_schedules')) {
            return;
        }

        $flipped = array_flip(self::DAYS);

        foreach (DB::table('class_schedules')->select('id', 'day_of_week')->get() as $row) {
            $index = $flipped[(string) $row->day_of_week] ?? null;

            if ($index === null) {
                continue;
            }

            DB::table('class_schedules')
                ->where('id', $row->id)
                ->update(['day_of_week' => (string) $index]);
        }
    }
};
