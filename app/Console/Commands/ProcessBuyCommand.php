<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Code;
use App\Models\ProductToBuy;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ProcessBuyCommand extends Command
{
    protected $signature = 'app:process-buy {product : The ID of the product to buy} {quantity=1 : The quantity to buy}';
    protected $description = 'Process a buy order for testing with fake data';

    public function handle()
    {
        try {
            $productId = $this->argument('product');
            $quantity = (int)$this->argument('quantity');

            if ($quantity <= 0) {
                $this->error('Quantity must be greater than 0');
                return Command::FAILURE;
            }

            $product = ProductToBuy::findOrFail($productId);

            if ($product->quantity < $quantity) {
                $this->error("Not enough product quantity available. Available: {$product->quantity}");
                return Command::FAILURE;
            }

            // Create or get test account
            $account = Account::firstOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test Account',
                    'password' => bcrypt('password'),
                    'ballance_gold' => 1000,
                    'ballance_silver' => 1000,
                    'limit_orders_per_day' => 10
                ]
            );

            // Calculate total cost
            $totalCost = $product->buy_value * $quantity;

            // Check if account has enough balance
            if ($account->ballance_gold < $totalCost) {
                $this->error("Insufficient balance. Required: {$totalCost}, Available: {$account->ballance_gold}");
                return Command::FAILURE;
            }

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
                'quantity' => $product->quantity - $quantity
            ]);

            // Generate codes for the quantity purchased
            for ($i = 0; $i < $quantity; $i++) {
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

            $this->info('Buy process completed successfully:');
            $this->info("Transaction ID: {$transaction->transaction_id}");
            $this->info("Quantity purchased: {$quantity}");
            $this->info("Total cost: {$totalCost}");
            $this->info("New balance: {$account->ballance_gold}");
            $this->info("Account: {$account->email}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error processing purchase: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
