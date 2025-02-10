<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrders extends Model
{
    use HasFactory;

    protected $table = 'purchase_orders';
    protected $fillable = [
        'product_id',
        'product_name',
        'product_edition',
        'is_active',
        'account_type',
        'quantity',
        'buy_value',
        'product_face_value',
        'order_status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quantity' => 'integer',
        'buy_value' => 'decimal:2',
        'product_face_value' => 'decimal:2',
        'order_status' => 'string',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'product_id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(Code::class, 'product_id');
    }
}
