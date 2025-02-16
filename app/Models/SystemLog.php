<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $fillable = [
        'account_id',
        'source',
        'status',
        'command',
        'params',
        'response',
        'order_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    protected $casts = [
        'command' => 'string',
        'params' => 'array',
        'response' => 'array',
    ];
}
