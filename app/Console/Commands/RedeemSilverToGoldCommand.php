<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;

class RedeemSilverToGoldCommand extends Command
{
    protected $signature = 'redeem:silver-to-gold {account-id} {product-id?}';
    protected $description = 'Convert 1000 silver to 1 gold for the specified account';

    public function handle()
    {
        $accountId = $this->argument('account-id');
        $productId = $this->argument('product-id');
        $account = Account::find($accountId);

        // Handle product-id if provided
        if ($productId) {
            // Logic to handle product-id, e.g., validate or use it in the process
            $this->info("Product ID provided: {$productId}");
        }

        if (!$account) {
            $this->error('Account not found.');
            return;
        }

        if ($account->ballance_silver < 1000) {
            $this->error('Not enough silver to redeem.');
            return;
        }

        $goldToAdd = floor($account->ballance_silver / 1000);
        $account->ballance_silver -= $goldToAdd * 1000;
        $account->ballance_gold += $goldToAdd;
        $account->save();

        $this->info("Successfully redeemed {$goldToAdd} gold from silver.");
    }
}
