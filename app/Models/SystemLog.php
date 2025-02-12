<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $fillable = [
        'source',
        'command',
        'params',
        'response',
        'status',
    ];

    protected $casts = [
        'command' => 'string',
        'params' => 'array',
        'response' => 'array',
    ];
}
