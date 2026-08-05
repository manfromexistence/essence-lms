<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'student_id', 'course_id', 'template_id', 'certificate_number',
        'verification_code', 'issued_at', 'issued_by', 'grade', 'status',
        'revoked_at', 'revocation_reason', 'pdf_path',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function course() { return $this->belongsTo(Course::class); }
    public function template() { return $this->belongsTo(CertificateTemplate::class, 'template_id'); }
    public function issuer() { return $this->belongsTo(User::class, 'issued_by'); }
}
