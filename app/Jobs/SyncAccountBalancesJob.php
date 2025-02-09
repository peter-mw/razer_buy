<?php

namespace App\Jobs;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAccountBalancesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected ?int $accountId = null
    ) {}

    public function handle(): void
    {
        if ($this->accountId) {
            $this->syncAccount(Account::findOrFail($this->accountId));
            return;
        }

        Account::chunk(100, function ($accounts) {
            foreach ($accounts as $account) {
                $this->syncAccount($account);
            }
        });
    }

    protected function syncAccount(Account $account): void
    {
        try {
            // Here you would implement the actual API call to fetch balances
            // This is a placeholder that you should replace with actual API integration
            $response = [
                'gold' => rand(100, 1000), // Replace with actual API data
                'silver' => rand(1000, 10000), // Replace with actual API data
            ];

            $account->update([
                'ballance_gold' => $response['gold'],
                'ballance_silver' => $response['silver'],
                'last_ballance_update_at' => now(),
                'last_ballance_update_status' => 'success',
            ]);
        } catch (\Exception $e) {
            $account->update([
                'last_ballance_update_at' => now(),
                'last_ballance_update_status' => 'error',
            ]);
            
            throw $e;
        }
    }
}
