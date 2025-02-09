<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBuyJob;
use App\Models\ProductToBuy;
use Illuminate\Console\Command;

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
                $this->warn("Only {$product->quantity} items available. Will purchase maximum available quantity.");
            }

            ProcessBuyJob::dispatch($productId, $quantity);
            
            $this->info('Buy process has been queued successfully.');
            if ($product->quantity < $quantity) {
                $this->info("Will attempt to purchase up to {$product->quantity} items.");
            }
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error queueing purchase: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
