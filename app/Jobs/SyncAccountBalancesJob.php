<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\AccountTopup;
use App\Models\SystemLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SyncAccountBalancesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected ?int $accountId = null
    )
    {
    }
    public function middleware()
    {
        return [(new WithoutOverlapping('SyncAccountBalancesJob'.$this->accountId))->dontRelease()];
    }
    public function handle(): void
    {
        if ($this->accountId) {
            $this->syncAccount(Account::findOrFail($this->accountId));
            return;
        } else {
            Account::chunk(100, function ($accounts) {
                foreach ($accounts as $account) {
                    $this->syncAccount($account);
                }
            });
        }


    }

    protected function syncAccount(Account $account): void
    {
        try {
            // Create initial system log
            SystemLog::create([
                'source' => 'SyncAccountBalancesJob',
                'account_id' => $account->id,
                'status' => 'processing',
                'command' => 'sync_balance',
                'params' => [
                    'account_id' => $account->id,
                    'current_gold' => $account->ballance_gold,
                    'current_silver' => $account->ballance_silver,
                ],
            ]);

            $service = new \App\Services\RazerService($account);
            $ballance = $service->getAccountBallance();
            // Here you would implement the actual API call to fetch balances
            // This is a placeholder that you should replace with actual API integration
            $response = [
                'gold' => $ballance['gold'] ?? 0, // Replace with actual API data
                'silver' => $ballance['silver'] ?? 0, // Replace with actual API data
            ];

            // Determine if this is a top-up by comparing with new balances
            $isTopUp = floatval($response['gold']) > floatval($account->ballance_gold);
            // Check if balance has changed
            $balanceChanged = floatval($response['gold']) !== floatval($account->ballance_gold);

            // Only create history record if balance changed or it's a top-up
            if ($balanceChanged || $isTopUp) {
                $account->balanceHistories()->create([
                    'balance_gold' => $response['gold'],
                    'balance_silver' => $response['silver'],
                    'balance_update_time' => $account->last_ballance_update_at ?? now(),
                    'balance_event' => $isTopUp ? 'topup' : null,
                ]);

                // If it's a top-up, create a record in the account_topups table
                if ($isTopUp) {
                    $topupAmount = floatval($response['gold']) - floatval($account->ballance_gold);
                    AccountTopup::create([
                        'account_id' => $account->id,
                        'topup_amount' => $topupAmount,
                        'topup_time' => now(),
                    ]);

                    SystemLog::create([
                        'source' => 'SyncAccountBalancesJob',
                        'account_id' => $account->id,
                        'status' => 'success',
                        'command' => 'detect_topup',
                        'params' => [
                            'account_id' => $account->id,
                            'topup_amount' => $topupAmount,
                            'previous_gold' => $account->ballance_gold,
                            'new_gold' => $response['gold'],
                        ],
                    ]);
                }
            }

            $account->update([
                'ballance_gold' => $response['gold'],
                'ballance_silver' => $response['silver'],
                'last_ballance_update_at' => now(),
                'last_ballance_update_status' => 'success',
            ]);

            SystemLog::create([
                'source' => 'SyncAccountBalancesJob',
                'account_id' => $account->id,
                'status' => 'success',
                'command' => 'sync_balance',
                'params' => [
                    'account_id' => $account->id,
                    'gold' => $response['gold'],
                    'silver' => $response['silver'],
                    'balance_changed' => $balanceChanged,
                ],
            ]);
        } catch (\Exception $e) {
            SystemLog::create([
                'source' => 'SyncAccountBalancesJob',
                'account_id' => $account->id,
                'status' => 'error',
                'command' => 'sync_balance',
                'params' => [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ],
            ]);

            $account->update([
                'last_ballance_update_at' => now(),
                'last_ballance_update_status' => 'error',
            ]);

            throw $e;
        }
    }
}
