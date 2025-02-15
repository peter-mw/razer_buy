<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'region_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function accounts()
    {
        return $this->hasMany(Account::class, 'account_type', 'code');
    }
}
