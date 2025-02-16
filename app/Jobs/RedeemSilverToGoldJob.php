<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\SystemLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class RedeemSilverToGoldJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int  $accountId,
        protected ?int $productId = null
    )
    {
    }
    public function middleware()
    {
        return [(new WithoutOverlapping($this->accountId))->dontRelease()];
    }
    public function handle(): void
    {

        $account = Account::findOrFail($this->accountId);

        // Create initial system log
        SystemLog::create([
            'source' => 'RedeemSilverToGoldJob',
            'account_id' => $account->id,
            'status' => 'processing',
            'command' => 'redeem_silver',
            'order_id' => $this->productId,
            'params' => [
                'account_id' => $account->id,
                'product_id' => $this->productId,
                'silver_balance' => $account->ballance_silver,
            ],
        ]);

        abort(500, 'Not implemented yet');

        if ($account->ballance_silver < 1000) {
            SystemLog::create([
                'source' => 'RedeemSilverToGoldJob',
                'account_id' => $account->id,
                'status' => 'error',
                'command' => 'redeem_silver',
                'order_id' => $this->productId,
                'params' => [
                    'account_id' => $account->id,
                    'silver_balance' => $account->ballance_silver,
                    'error' => 'Not enough silver to redeem',
                ],
            ]);
            throw new \Exception('Not enough silver to redeem.');
        }

        $goldToAdd = floor($account->ballance_silver / 1000);
        $account->ballance_silver -= $goldToAdd * 1000;
        $account->ballance_gold += $goldToAdd;
        $account->save();

        SystemLog::create([
            'source' => 'RedeemSilverToGoldJob',
            'account_id' => $account->id,
            'status' => 'success',
            'command' => 'redeem_silver',
            'order_id' => $this->productId,
            'params' => [
                'account_id' => $account->id,
                'silver_redeemed' => $goldToAdd * 1000,
                'gold_added' => $goldToAdd,
                'new_silver_balance' => $account->ballance_silver,
                'new_gold_balance' => $account->ballance_gold,
            ],
        ]);
    }
}
