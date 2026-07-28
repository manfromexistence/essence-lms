<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseEnrollment extends Model
{
    protected $fillable = ['student_id', 'course_id', 'batch_id', 'payment_id', 'enrolled_at'];

    protected $casts = ['enrolled_at' => 'datetime'];

    public function student() { return $this->belongsTo(Student::class); }
    public function course() { return $this->belongsTo(Course::class); }
    public function batch() { return $this->belongsTo(Batch::class); }
    public function payment() { return $this->belongsTo(Payment::class); }
}
