<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_LATE = 'late';

    public const STATUS_EXCUSED = 'excused';

    protected $fillable = ['student_id', 'batch_id', 'date', 'status'];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Store `date` as a bare calendar day.
     *
     * The column is a DATE, so MySQL and Postgres truncate the time component
     * automatically. SQLite does not: the default 'date' cast serialises through
     * fromDateTime() and wrote 'Y-m-d 00:00:00'. Two consequences on SQLite:
     *
     *   - every read using where('date', 'Y-m-d') matched nothing, because
     *     '2026-10-01' and '2026-10-01 00:00:00' are different strings — so the
     *     attendance reports and dashboards silently showed nothing;
     *   - updateOrCreate(['date' => 'Y-m-d']) never matched an existing row,
     *     fell through to an INSERT, and hit the
     *     (student_id, batch_id, date) unique index — meaning a teacher could
     *     not correct a single day they had already marked.
     *
     * A set mutator is used rather than a 'date:Y-m-d' cast because the cast
     * format only affects serialisation for arrays/JSON; on write Eloquent still
     * goes through fromDateTime() and keeps the time. The mutator wins because
     * Laravel checks for it before date casting, so the column receives exactly
     * what every query in the app already compares against.
     */
    public function setDateAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['date'] = null;

            return;
        }

        $this->attributes['date'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : Carbon::parse($value)->toDateString();
    }

    /**
     * Statuses that count as having attended.
     *
     * `late` means the student turned up after the start bell, which is still an
     * attended class. Several screens counted only `present`, so a student marked
     * late every day saw 0% on their own dashboard while the admin reports
     * showed 100%. Anything computing an attendance *rate* should use this list;
     * per-status breakdowns legitimately report `late` on its own.
     *
     * @return array<int, string>
     */
    public static function attendingStatuses(): array
    {
        return [self::STATUS_PRESENT, self::STATUS_LATE];
    }

    /**
     * Restrict to classes the student actually attended.
     */
    public function scopeAttended($query)
    {
        return $query->whereIn('status', self::attendingStatuses());
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
