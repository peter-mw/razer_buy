<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Code extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'code',
        'serial_number',
        'product_id',
        'product_name',
        'product_edition',
        'buy_date',
        'buy_value',
        'order_id',
        'transaction_ref',
        'transaction_id'
    ];

    protected $casts = [
        'buy_date' => 'datetime',
        'buy_value' => 'decimal:2'
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrders::class, 'order_id');
    }
}
