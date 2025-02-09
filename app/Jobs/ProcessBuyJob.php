<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Code;
use App\Models\ProductToBuy;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ProcessBuyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $productId,
        protected int $quantity
    ) {}

    public function handle(): void
    {
        $product = ProductToBuy::findOrFail($this->productId);

        // Adjust quantity if requested amount is more than available
        $actualQuantity = min($this->quantity, $product->quantity);
        if ($actualQuantity <= 0) {
            throw new \Exception("Product is out of stock");
        }

        // Calculate total cost
        $totalCost = $product->buy_value * $actualQuantity;

        // Find eligible accounts with sufficient balance and matching account type
        $accounts = Account::where('ballance_gold', '>=', $totalCost)
            ->where('limit_amount_per_day', '>', 0)
            ->where('account_type', $product->account_type)
            ->get();

        $eligibleAccount = null;

        foreach ($accounts as $acc) {
            $todaySpent = Transaction::where('account_id', $acc->id)
                ->whereDate('transaction_date', now())
                ->sum('amount');

            if (($todaySpent + $totalCost) <= $acc->limit_amount_per_day) {
                $eligibleAccount = $acc;
                break;
            }
        }

        if (!$eligibleAccount) {
            throw new \Exception("No eligible account found with sufficient balance and daily limit");
        }

        $account = $eligibleAccount;

        // Create transaction
        $transaction = Transaction::create([
            'account_id' => $account->id,
            'amount' => $totalCost,
            'product_id' => $product->id,
            'transaction_date' => now(),
            'transaction_id' => 'TRX-' . Str::random(10)
        ]);

        // Update account balance
        $account->update([
            'ballance_gold' => $account->ballance_gold - $totalCost
        ]);

        // Update product quantity
        $product->update([
            'quantity' => $product->quantity - $actualQuantity
        ]);

        // Generate codes for the quantity purchased
        for ($i = 0; $i < $actualQuantity; $i++) {
            Code::create([
                'account_id' => $account->id,
                'code' => 'CODE-' . Str::random(16),
                'serial_number' => 'SN-' . Str::random(8),
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'product_edition' => $product->product_edition,
                'buy_date' => now(),
                'buy_value' => $product->buy_value
            ]);
        }
    }
}
