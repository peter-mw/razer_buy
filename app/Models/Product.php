<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'id',
        'product_name',
        'product_slug',
        'account_type',
        'product_edition',
        'product_buy_value',
        'product_face_value',
        'remote_crm_product_name',
    ];

    public function codes()
    {
        return $this->hasMany(Code::class, 'product_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'product_id');
    }
}
