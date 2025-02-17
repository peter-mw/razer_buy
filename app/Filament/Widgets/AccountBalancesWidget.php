<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\AccountTopup;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class AccountBalancesWidget extends Widget
{
    protected static ?string $pollingInterval = '10s';

    protected int|string|array $columnSpan = 'full';

    public function render(): View
    {
        $balances = [];
        $accountTypes = \App\Models\AccountType::where('is_active', true)->pluck('code');

        foreach ($accountTypes as $type) {
            // Get accounts grouped by region and status
            $activeAccounts = Account::where('account_type', $type)
                ->where('is_active', true)
                ->get()
                ->groupBy('region_id');
                
            $inactiveAccounts = Account::where('account_type', $type)
                ->where('is_active', false)
                ->get()
                ->groupBy('region_id');

            $balances[$type] = [
                'active' => [
                    'gold' => Account::where('account_type', $type)
                        ->where('is_active', true)
                        ->sum('ballance_gold'),
                    'silver' => Account::where('account_type', $type)
                        ->where('is_active', true)
                        ->sum('ballance_silver'),
                    'topup' => [],
                ],
                'inactive' => [
                    'gold' => Account::where('account_type', $type)
                        ->where('is_active', false)
                        ->sum('ballance_gold'),
                    'silver' => Account::where('account_type', $type)
                        ->where('is_active', false)
                        ->sum('ballance_silver'),
                    'topup' => [],
                ],
            ];

            // Calculate topups by region for active accounts
            foreach ($activeAccounts as $regionId => $accounts) {
                $accountIds = $accounts->pluck('id');
                $balances[$type]['active']['topup'][$regionId] = AccountTopup::whereIn('account_id', $accountIds)
                    ->sum('topup_amount');
            }

            // Calculate topups by region for inactive accounts
            foreach ($inactiveAccounts as $regionId => $accounts) {
                $accountIds = $accounts->pluck('id');
                $balances[$type]['inactive']['topup'][$regionId] = AccountTopup::whereIn('account_id', $accountIds)
                    ->sum('topup_amount');
            }
        }

        return view('filament.widgets.account-balances', [
            'balances' => $balances,
        ]);
    }
}
