<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record that a certificate's QR code was scanned.
 *
 * The `certificate_verifications` table existed since the certificate feature
 * was introduced but was never written to, so there was no way to tell whether a
 * certificate had been checked. Writing a row here makes verification
 * observable: an employer who scans a certificate leaves a trace the institute
 * can audit.
 */
class CertificateVerification extends Model
{
    /**
     * The table records `verified_at` explicitly and has no created_at/updated_at
     * pair, so Eloquent must not try to maintain them.
     */
    public $timestamps = false;

    protected $fillable = [
        'certificate_id',
        'verified_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }
}
