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
        'limit_amount_per_day',
        'vendor',
        'email_password',
        'last_ballance_update_at',
        'last_ballance_update_status'
    ];



    protected $casts = [
        'ballance_gold' => 'decimal:2',
        'ballance_silver' => 'decimal:2',
        'limit_amount_per_day' => 'decimal:2',
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
