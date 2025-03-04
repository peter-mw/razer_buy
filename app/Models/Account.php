<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CodesWithMissingProduct;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'account_password',
        'otp_seed',
        'account_type',
        'vendor',
        'email_password',
        'ballance_gold',
        'ballance_silver',
        'limit_amount_per_day',
        'last_ballance_update_at',
        'last_ballance_update_status',
        'last_topup_sync_at',
        'last_topup_sync_status',
        'service_code',
        'client_id_login',
        'is_active',
        'topup_balance',
        'transaction_balance',
        'balance_difference',
        'failed_to_purchase_attempts',
        'failed_to_purchase_timestamp',
        'notes',
    ];

    protected $accountTypes = ['global', 'usa'];


    protected $casts = [
        'ballance_gold' => 'decimal:2',
        'ballance_silver' => 'decimal:2',
        'limit_amount_per_day' => 'decimal:2',
        'last_ballance_update_at' => 'datetime',
        'last_topup_sync_at' => 'datetime',
        'topup_balance' => 'decimal:2',
        'transaction_balance' => 'decimal:2',
        'balance_difference' => 'decimal:2'
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

    public function remainingBallance24Hours()
    {
        $transaction = $this->transactions()
            ->where('created_at', '>=', now()->subHours(24))
            ->sum('amount'); // Assuming 'amount' is the field for purchase value

        $gold = $this->ballance_gold;
        $limit = $this->limit_amount_per_day;
        $remaining = $limit - $gold;
        return $remaining;
    }

    public function codes(): HasMany
    {
        return $this->hasMany(Code::class);
    }

    public function balanceHistories(): HasMany
    {
        return $this->hasMany(AccountBalanceHistory::class);
    }

    public function accountTopups(): HasMany
    {
        return $this->hasMany(AccountTopup::class);
    }

    public function systemLogs(): HasMany
    {
        return $this->hasMany(SystemLog::class);
    }

    public function codesWithMissingProduct(): HasMany
    {
        return $this->hasMany(CodesWithMissingProduct::class);
    }

    public function getTopupBalanceAttribute(): float
    {
        return $this->accountTopups()->sum('topup_amount');
    }

    public function getTransactionBalanceAttribute(): float
    {
        return $this->transactions()->sum('amount');
    }

    public function getBalanceDifferenceAttribute(): float
    {

        $val = ($this->topup_balance - $this->transaction_balance) - $this->ballance_gold;
        return intval($val);
    }
}
