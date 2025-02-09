<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductToBuy extends Model
{
    use HasFactory;

    protected $table = 'products_to_buy';

    protected $fillable = [
        'product_id',
        'product_name',
        'product_edition',
        'buy_value',
        'is_active',
        'quantity'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quantity' => 'integer'
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'product_id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(Code::class, 'product_id');
    }
}
