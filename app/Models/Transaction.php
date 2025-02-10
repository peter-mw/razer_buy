<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'amount',
        'product_id',
        'transaction_date',
        'transaction_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime'
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrders::class, 'product_id');
    }
}
