<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\AccountTopup;
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
                AccountTopup::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'transaction_id' => $topup['TransactionID'] ?? null,
                    ],
                    [
                        'amount' => $topup['Amount'] ?? 0,
                        'status' => $topup['Status'] ?? 'pending',
                        'transaction_date' => $topup['TransactionDate'] ?? now(),
                        'details' => json_encode($topup),
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
