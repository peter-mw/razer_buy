<?php

namespace App\Console\Commands;

use App\Jobs\SyncAccountBalancesJob;
use App\Models\Account;
use Illuminate\Console\Command;

class SyncAccountBalancesCommand extends Command
{
    protected $signature = 'accounts:sync-balances {account-id? : Optional account ID to sync specific account}';
    protected $description = 'Sync account balances with external service';

    public function handle()
    {
        try {
            $accountId = $this->argument('account-id');

            if ($accountId) {
                if (!Account::find($accountId)) {
                    $this->error("Account with ID {$accountId} not found.");
                    return Command::FAILURE;
                }
            }

            SyncAccountBalancesJob::dispatchSync($accountId);

            $this->info($accountId
                ? "Account sync has been queued successfully."
                : "All accounts sync has been queued successfully."
            );
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error queueing sync: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
