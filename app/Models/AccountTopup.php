<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Account;

class AccountTopup extends Model
{
    protected $fillable = [
        'account_id',
        'topup_amount',
        'topup_time',
        'transaction_ref',
        'transaction_id',
    ];

    protected $casts = [
        'topup_time' => 'datetime',
        'topup_amount' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
