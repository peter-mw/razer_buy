<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $fillable = [
        'source',
        'params',
        'response',
        'status',
    ];

    protected $casts = [
        'params' => 'array',
        'response' => 'array',
    ];
}
