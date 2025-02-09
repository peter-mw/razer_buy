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
    protected $signature = 'app:process-buy {product : The ID of the product to buy}';
    protected $description = 'Process a buy order for testing with fake data';

    public function handle()
    {
        $productId = $this->argument('product');
        $product = ProductToBuy::findOrFail($productId);

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

        // Create transaction
        $transaction = Transaction::create([
            'account_id' => $account->id,
            'amount' => 100.00, // Example amount
            'product_id' => $product->id,
            'transaction_date' => now(),
            'transaction_id' => 'TRX-' . Str::random(10)
        ]);

        // Generate fake codes
        $numberOfCodes = rand(1, 3); // Generate 1-3 codes for testing
        for ($i = 0; $i < $numberOfCodes; $i++) {
            Code::create([
                'account_id' => $account->id,
                'code' => 'CODE-' . Str::random(16),
                'serial_number' => 'SN-' . Str::random(8),
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'product_edition' => $product->product_edition,
                'buy_date' => now(),
                'buy_value' => 100.00 // Example value
            ]);
        }

        $this->info('Buy process completed successfully:');
        $this->info("Transaction ID: {$transaction->transaction_id}");
        $this->info("Number of codes generated: {$numberOfCodes}");
        $this->info("Account: {$account->email}");

        return Command::SUCCESS;
    }
}
