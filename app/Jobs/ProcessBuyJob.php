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

        // Get accounts with matching account type and positive daily limit
        $accounts = Account::where('limit_amount_per_day', '>', 0)
            ->where('account_type', $product->account_type)
            ->get();

        $eligibleAccount = null;
        $actualQuantity = 0;

        foreach ($accounts as $acc) {
            // Calculate today's spent amount
            $todaySpent = Transaction::where('account_id', $acc->id)
                ->whereDate('transaction_date', now())
                ->sum('amount');

            // Calculate remaining daily limit
            $remainingDailyLimit = $acc->limit_amount_per_day - $todaySpent;
            if ($remainingDailyLimit <= 0) {
                continue;
            }

            // Calculate maximum quantity possible based on balance and daily limit
            $maxQuantityByBalance = floor($acc->ballance_gold / $product->buy_value);
            $maxQuantityByDailyLimit = floor($remainingDailyLimit / $product->buy_value);

            // Calculate the actual quantity considering all constraints
            $possibleQuantity = min(
                $this->quantity,
                $product->quantity,
                $maxQuantityByBalance,
                $maxQuantityByDailyLimit
            );

            if ($possibleQuantity > 0) {
                $eligibleAccount = $acc;
                $actualQuantity = $possibleQuantity;
                break;
            }
        }

        if (!$eligibleAccount || $actualQuantity <= 0) {
            throw new \Exception("No eligible account found with sufficient balance and daily limit, or product is out of stock");
        }



        // get pricr of pruct from the
        // sync the price




        $account = $eligibleAccount;
        $totalCost = $product->buy_value * $actualQuantity;

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
