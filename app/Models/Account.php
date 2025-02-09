<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'otp_seed',
        'ballance_gold',
        'ballance_silver',
        'account_type',
        'limit_orders_per_day',
        'vendor',
        'email_password',
        'last_ballance_update_at',
        'last_ballance_update_status'
    ];

    protected $hidden = [
        'password',
        'otp_seed',
        'email_password'
    ];

    protected $casts = [
        'ballance_gold' => 'decimal:2',
        'ballance_silver' => 'decimal:2',
        'limit_orders_per_day' => 'integer',
        'last_ballance_update_at' => 'datetime'
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function codes(): HasMany
    {
        return $this->hasMany(Code::class);
    }
}
