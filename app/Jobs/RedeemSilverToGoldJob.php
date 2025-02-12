<?php

namespace App\Jobs;

use App\Models\Account;
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

        abort(500, 'Not implemented yet');

        $account = Account::findOrFail($this->accountId);

        if ($account->ballance_silver < 1000) {
            throw new \Exception('Not enough silver to redeem.');
        }

        $goldToAdd = floor($account->ballance_silver / 1000);
        $account->ballance_silver -= $goldToAdd * 1000;
        $account->ballance_gold += $goldToAdd;
        $account->save();
    }
}
