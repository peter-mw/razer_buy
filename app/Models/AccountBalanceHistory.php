<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBalanceHistory extends Model
{
    protected $fillable = [
        'account_id',
        'balance_gold',
        'balance_silver',
        'balance_update_time',
        'balance_event',
    ];

    protected $casts = [
        'balance_update_time' => 'datetime',
        'balance_gold' => 'decimal:2',
        'balance_silver' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
