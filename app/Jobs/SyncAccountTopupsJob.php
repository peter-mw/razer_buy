<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\AccountTopup;
use App\Models\Transaction;
use App\Services\RazerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAccountTopupsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $accountId;

    public function __construct($accountId)
    {
        $this->accountId = $accountId;
    }

    public function handle(): void
    {
        try {
            $account = Account::findOrFail($this->accountId);
            $razerService = new RazerService($account);

            $topups = $razerService->fetchTopUps();



            foreach ($topups as $topup) {
                // Create or update the account topup
                $accountTopup = AccountTopup::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'transaction_id' => $topup['transaction'] ?? null,
                    ],
                    [
                        'topup_amount' => $topup['amount'] ?? 0,
                        'transaction_ref' => $topup['product'] ?? '',
                        'topup_time' => $topup['transaction_date'] ?? now(),
                    ]
                );

            }

            $account->last_topup_sync_at = now();
            $account->last_topup_sync_status = 'success';
            $account->save();

        } catch (\Exception $e) {
            Log::error('SyncAccountTopupsJob failed', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage()
            ]);

            if (isset($account)) {
                $account->last_topup_sync_status = 'error';
                $account->save();
            }

            throw $e;
        }
    }
}
