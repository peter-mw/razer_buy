<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\AccountTopup;
use App\Models\SystemLog;
use App\Models\Transaction;
use App\Services\RazerService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
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

    public function middleware()
    {
        return [(new WithoutOverlapping('SyncAccountTopupsJob' . $this->accountId))->dontRelease()];
    }

    public function handle(): void
    {





        try {
            $account = Account::findOrFail($this->accountId);

            // Create initial system log
            SystemLog::create([
                'source' => 'SyncAccountTopupsJob',
                'account_id' => $account->id,
                'status' => 'processing',
                'command' => 'sync_topups',
                'params' => [
                    'account_id' => $account->id,
                ],
            ]);

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

                SystemLog::create([
                    'source' => 'SyncAccountTopupsJob',
                    'account_id' => $account->id,
                    'status' => 'success',
                    'command' => 'process_topup',
                    'params' => [
                        'account_id' => $account->id,
                        'topup_amount' => $topup['amount'] ?? 0,
                        'transaction_id' => $topup['transaction'] ?? null,
                        'transaction_ref' => $topup['product'] ?? '',
                    ],
                ]);
            }

            $account->last_topup_sync_at = now();
            $account->last_topup_sync_status = 'success';
            $account->save();

            SystemLog::create([
                'source' => 'SyncAccountTopupsJob',
                'account_id' => $account->id,
                'status' => 'success',
                'command' => 'sync_topups',
                'params' => [
                    'account_id' => $account->id,
                    'topups_processed' => count($topups),
                ],
            ]);

        } catch (\Exception $e) {
            SystemLog::create([
                'source' => 'SyncAccountTopupsJob',
                'account_id' => isset($account) ? $account->id : $this->accountId,
                'status' => 'error',
                'command' => 'sync_topups',
                'params' => [
                    'account_id' => isset($account) ? $account->id : $this->accountId,
                    'error' => $e->getMessage(),
                ],
            ]);

            Log::error('SyncAccountTopupsJob failed', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage()
            ]);

            if (isset($account)) {
                $account->last_topup_sync_status = 'error';
                $account->save();
            }


        }
    }
}
