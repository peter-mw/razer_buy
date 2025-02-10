<?php

namespace App\Console\Commands;

use App\Jobs\RedeemSilverToGoldJob;
use Illuminate\Console\Command;

class RedeemSilverToGoldCommand extends Command
{
    protected $signature = 'redeem:silver-to-gold {account-id} {product-id?}';
    protected $description = 'Convert 1000 silver to 1 gold for the specified account';

    public function handle()
    {
        try {
            $accountId = $this->argument('account-id');
            $productId = $this->argument('product-id');

            RedeemSilverToGoldJob::dispatch($accountId, $productId);

            $this->info('Silver to gold redemption has been queued successfully.');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error queueing redemption: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
