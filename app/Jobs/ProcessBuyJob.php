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
    )
    {
    }

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


        $service = new \App\Services\RazerService($eligibleAccount);
        $account = $eligibleAccount;
        $ready = [];
        $remainingQuantity = $actualQuantity;

        // Process in chunks of 5
        while ($remainingQuantity > 0) {
            $chunkSize = min(5, $remainingQuantity);

            $buyProductsResults = $service->buyProduct($product, $chunkSize);

            if (empty($buyProductsResults)) {
                continue;
            }

            foreach ($buyProductsResults as $buyProducts) {
                if (!isset($buyProducts['Code'], $buyProducts['SN'], $buyProducts['Amount'], $buyProducts['TransactionDate'])) {
                    continue;
                }
                $ready[] = [
                    'code' => $buyProducts['Code'],
                    'serial_number' => $buyProducts['SN'],
                    'amount' => $buyProducts['Amount'],
                    'buy_date' => date('Y-m-d H:i:s', strtotime($buyProducts['TransactionDate'])),
                    'transaction_id' => $buyProducts['SN'] // Using SN as transaction ID since it's unique
                ];
            }

            $remainingQuantity -= $chunkSize;
        }

        $totalAmount = 0;

        // Create transaction and code for each item
        foreach ($ready as $item) {
            // Create transaction
            Transaction::create([
                'account_id' => $account->id,
                'amount' => $item['amount'],
                'product_id' => $product->id,
                'transaction_date' => $item['buy_date'],
                'transaction_id' => $item['transaction_id']
            ]);

            // Create code
            Code::create([
                'account_id' => $account->id,
                'code' => $item['code'],
                'serial_number' => $item['serial_number'],
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'product_edition' => $product->product_edition,
                'buy_date' => $item['buy_date'],
                'buy_value' => $item['amount']
            ]);

            $totalAmount += $item['amount'];
        }

        // Update account balance with total amount
        $account->update([
            'ballance_gold' => $account->ballance_gold - $totalAmount
        ]);

        // Update product quantity
        $product->update([
            'quantity' => $product->quantity - count($ready)
        ]);
    }

}
