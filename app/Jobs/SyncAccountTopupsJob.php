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

/*    public function middleware()
    {
        return [(new WithoutOverlapping('SyncAccountTopupsJob' . $this->accountId))->dontRelease()];
    }*/

    public function handle(): void
    {


        try {
            $account = Account::findOrFail($this->accountId);

            // Create initial system log
            $log = SystemLog::create([
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
                        'date' => $topup['transaction_date'] ?? now(),
                        'topup_time' => $topup['transaction_date'] ?? now(),
                    ]
                );


            }

            // Update balance columns
            $account->topup_balance = $account->accountTopups()->sum('topup_amount');
            $account->transaction_balance = $account->transactions()->sum('amount');
            $account->balance_difference = ($account->topup_balance - $account->transaction_balance) - $account->ballance_gold;

            $account->last_topup_sync_at = now();
            $account->last_topup_sync_status = 'success';
            $account->save();

            $log->status = 'success';
            $log->save();

        } catch (\Exception $e) {
            $log->status = 'error';
            $log->response = [
                'message' => $e->getMessage(),
            ];
            $log->save();

            if (isset($account)) {
                $account->last_topup_sync_status = 'error';
                $account->save();
            }

        }
    }
}
