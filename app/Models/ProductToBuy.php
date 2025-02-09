<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductToBuy extends Model
{
    use HasFactory;

    protected $table = 'products_to_buy';
    protected $fillable = [
        'product_id',
        'product_name',
        'product_edition',
        'is_active',
        'account_type',
        'quantity',
        'buy_value',
        'product_face_value',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quantity' => 'integer',
        'buy_value' => 'decimal:2',
        'product_face_value' => 'decimal:2',
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
