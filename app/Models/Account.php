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
        'account_type',
        'vendor',
        'email_password',
        'ballance_gold',
        'ballance_silver',
        'limit_amount_per_day',
        'last_ballance_update_at',
        'last_ballance_update_status',
        'service_code',
        'client_id_login',
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

    public function purchasesLast24Hours()
    {
        return $this->transactions()
            ->where('created_at', '>=', now()->subHours(24))
            ->sum('amount'); // Assuming 'amount' is the field for purchase value
    }

    public function codes(): HasMany
    {
        return $this->hasMany(Code::class);
    }

    public function balanceHistories(): HasMany
    {
        return $this->hasMany(AccountBalanceHistory::class);
    }
}
