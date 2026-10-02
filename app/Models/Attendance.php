<?php

namespace App\Models;

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
