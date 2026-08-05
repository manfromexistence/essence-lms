<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
    protected $fillable = [
        'name', 'type', 'background_image', 'logo_image', 'signature_image',
        'layout_config', 'variables', 'width', 'height', 'is_active', 'is_default',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'template_id');
    }
}
