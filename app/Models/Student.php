<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'batch_id',
        'class',
        'roll',
        'section',
        'group',
        'shift',
        'registration_no',
        'name_bn',
        'dob', // Date
        'gender', // Male, Female
        'blood_group',
        'religion',
        'father_name',
        'mother_name',
        'father_occupation',
        'mother_occupation',
        'father_phone',
        'mother_phone',
        'guardian_name',
        'guardian_phone',
        'present_village',
        'present_po',
        'present_ps',
        'present_dist',
        'present_holding',
        'permanent_village',
        'permanent_po',
        'permanent_ps',
        'permanent_dist',
        'permanent_holding',
        'ssc_institute',
        'ssc_board',
        'ssc_year',
        'ssc_gpa',
        'ssc_group',
        'hsc_institute',
        'hsc_board',
        'hsc_year',
        'hsc_gpa',
        'hsc_group',
        'undergrad_institute',
        'undergrad_board',
        'undergrad_year',
        'undergrad_gpa',
        'undergrad_group',
        'undergrad_department',
        'course_name', // This might be redundant if we use course relationship, but keeping per schema
        'total_amount',
        'paid_amount',
        'due_amount',
        'balance',
        'payment_method',
        'status',
        'phone',
        'profile_image',
        'featured',
        'profession',
        'marital_status',
        'admission_purpose',
        'admission_mode',
        'admission_status',
        'applied_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'featured' => 'boolean',
            'applied_at' => 'datetime',
        ];
    }

    /**
     * Get the student's name (uses the linked user's name as fallback).
     */
    public function getNameAttribute(): string
    {
        if ($this->name_bn) {
            return $this->name_bn;
        }
        if ($this->relationLoaded('user') && $this->user) {
            return $this->user->name ?? 'Unknown Student';
        }
        return $this->user?->name ?? 'Unknown Student';
    }

    /**
     * Get the balance attribute (alias for due_amount for compatibility).
     */
    public function getBalanceAttribute($value): float
    {
        // If balance is not set, return due_amount
        return $value ?? $this->attributes['due_amount'] ?? 0;
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            if (empty($student->registration_no)) {
                $year = date('Y');
                // Format: YYYY-BATCH-SEQUENCE (e.g., 2026-B10-0001)
                $batchCode = 'STU';
                if ($student->batch_id) {
                    $batch = \App\Models\Batch::find($student->batch_id);
                    if ($batch) {
                        $batchCode = $batch->code ?? 'BAT';
                    }
                }

                // Find last student ID to increment
                $lastStudent = self::latest('id')->first();
                $sequence = $lastStudent ? $lastStudent->id + 1 : 1;

                $student->registration_no = $year . '-' . $batchCode . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the user that owns the student.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the batch that owns the student.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the attendances for the student.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the exam results for the student.
     */
    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Get the payments for the student.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function courseEnrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    /**
     * Get the invoices for the student.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the income records for the student.
     */
    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    /**
     * Get the total paid amount from payments.
     *
     * @return float
     */
    public function getTotalPaidFromPaymentsAttribute(): float
    {
        return $this->payments()->completed()->sum('amount');
    }

    /**
     * Get the count of pending invoices.
     *
     * @return int
     */
    public function getPendingInvoicesCountAttribute(): int
    {
        return $this->invoices()->pending()->count();
    }

    /**
     * Get the attendance percentage for the student.
     *
     * @return float
     */
    public function getAttendancePercentageAttribute(): float
    {
        $total = $this->attendances()->count();
        if ($total === 0) {
            return 0;
        }

        $present = $this->attendances()->whereIn('status', ['present', 'late'])->count();
        return round(($present / $total) * 100, 2);
    }

    /**
     * Scope a query to only include active students.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include featured students.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Scope a query to only include students enrolled in a specific year.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $year
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnrolledInYear($query, int $year)
    {
        $startOfYear = \Carbon\Carbon::create($year, 1, 1)->startOfYear();
        $endOfYear = \Carbon\Carbon::create($year, 12, 31)->endOfYear();
        return $query->whereBetween('created_at', [$startOfYear, $endOfYear]);
    }

    /**
     * Scope a query to only include students with due amounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithDues($query)
    {
        return $query->where('due_amount', '>', 0);
    }

    /**
     * Scope a query to only include students in a specific batch.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $batchId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInBatch($query, int $batchId)
    {
        return $query->where('batch_id', $batchId);
    }
}
