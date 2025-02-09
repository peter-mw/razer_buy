<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;

class SyncAccountBalancesCommand extends Command
{
    protected $signature = 'accounts:sync-balances {account-id? : Optional account ID to sync specific account}';
    protected $description = 'Sync account balances with external service';

    public function handle()
    {
        $accountId = $this->argument('account-id');

        if ($accountId) {
            $account = Account::find($accountId);

            if (!$account) {
                $this->error("Account with ID {$accountId} not found.");
                return 1;
            }

            $this->syncAccount($account);
            $this->info("Account {$account->email} sync completed!");
            return 0;
        }

        $accounts = Account::all();
        if ($accounts->isEmpty()) {
            $this->info('No accounts found to sync.');
            return 0;
        }
        $bar = $this->output->createProgressBar($accounts->count());
        $bar->start();

        foreach ($accounts as $account) {
            $this->syncAccount($account);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('All accounts sync completed!');
        return 0;
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

            $this->info(" Synced balances for account: {$account->email}");
        } catch (\Exception $e) {
            $account->update([
                'last_ballance_update_at' => now(),
                'last_ballance_update_status' => 'error',
            ]);
            $this->error(" Failed to sync account {$account->email}: {$e->getMessage()}");
        }
    }
}
