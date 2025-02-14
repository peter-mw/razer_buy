<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\SystemLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Bus;

class SyncAllAccountData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function __construct(
        protected ?int $accountId = null
    )
    {
    }

    public function handle(): void
    {
        if ($this->accountId) {
            $account = Account::findOrFail($this->accountId);

            // Create initial system log
            $log = SystemLog::create([
                'source' => 'SyncAllAccountData',
                'account_id' => $account->id,
                'status' => 'processing',
                'command' => 'sync_all_data',
                'params' => [
                    'account_id' => $account->id,
                ],
            ]);

            // For a single account, chain the jobs sequentially
            Bus::chain([
                new SyncAccountBalancesJob($this->accountId),
                new SyncAccountTopupsJob($this->accountId),
                new FetchAccountCodesJob($this->accountId),
            ])->dispatch();

            $log->update([
                'status' => 'success',
                'command' => 'sync_all_data',
                'params' => [
                    'account_id' => $account->id,
                    'jobs_dispatched' => ['SyncAccountBalancesJob', 'SyncAccountTopupsJob', 'FetchAccountCodesJob'],
                ],
            ]);
            return;
        } else {
            // Create initial system log for all accounts sync
            $log = SystemLog::create([
                'source' => 'SyncAllAccountData',
                'status' => 'processing',
                'command' => 'sync_all_data',
                'params' => [
                    'mode' => 'all_accounts',
                ],
            ]);

            // For all accounts, process each account sequentially
            Account::where('is_active', true)->chunk(100, function ($accounts) {
                foreach ($accounts as $account) {
                    Bus::chain([
                        new SyncAccountBalancesJob($account->id),
                        new SyncAccountTopupsJob($account->id),
                        new FetchAccountCodesJob($account->id),
                    ])->dispatch();
                }
            });

            $log->update([
                'status' => 'success',
                'command' => 'sync_all_data',
                'params' => [
                    'mode' => 'all_accounts',
                    'jobs_dispatched' => ['SyncAccountBalancesJob', 'SyncAccountTopupsJob', 'FetchAccountCodesJob'],
                ],
            ]);
        }
    }
}
