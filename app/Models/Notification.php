<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id', 'user_type', 'type', 'title', 'message', 'data', 'read_at', 'action_url',
    ];

    protected $casts = ['data' => 'array', 'read_at' => 'datetime'];
}
