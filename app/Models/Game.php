<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'id',
        'product_id',
        'product_code',
        'vanity_name',
        'description',
        'position',
        'category',
        'merchant_service_id',
        'merchant_id',
        'region_id',
        'has_stock',
        'product_type_id',
        'unit_gold',
        'unit_base_gold',
        'unit_tax_gold',
        'amount_in_rz_silver',
        'is_price_incl_tax',
        'tax_name',
        'tax_rate',
        'product_name'
    ];

    // Disable auto-incrementing as we're using a string ID
    public $incrementing = false;
    
    // Specify the ID is a string
    protected $keyType = 'string';

    // Cast attributes to their appropriate types
    protected $casts = [
        'has_stock' => 'boolean',
        'is_price_incl_tax' => 'boolean',
        'unit_gold' => 'decimal:4',
        'unit_base_gold' => 'decimal:4',
        'unit_tax_gold' => 'decimal:4',
        'product_id' => 'integer',
        'position' => 'integer',
        'merchant_service_id' => 'integer',
        'merchant_id' => 'integer',
        'region_id' => 'integer',
        'product_type_id' => 'integer',
        'amount_in_rz_silver' => 'integer',
        'tax_rate' => 'integer'
    ];

    /**
     * Get the catalog that shares the same region.
     */
    public function catalog()
    {
        return $this->belongsTo(Catalog::class, 'region_id', 'region_id');
    }
}
