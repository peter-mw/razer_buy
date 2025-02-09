<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'product_name',
        'product_slug',
        'account_type',
        'product_edition',
        'product_buy_value',
        'remote_crm_product_name',
    ];

    protected $casts = [
        'product_buy_value' => 'decimal:2',
    ];

    public function productsToBuy(): HasMany
    {
        return $this->hasMany(ProductToBuy::class, 'product_id');
    }
}
