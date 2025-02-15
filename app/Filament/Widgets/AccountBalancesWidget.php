<?php

namespace App\Filament\Widgets;

use App\Models\Account;
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
            $balances[$type] = [
                'active' => [
                    'gold' => Account::where('account_type', $type)
                        ->where('is_active', true)
                        ->sum('ballance_gold'),
                    'silver' => Account::where('account_type', $type)
                        ->where('is_active', true)
                        ->sum('ballance_silver'),
                ],
                'inactive' => [
                    'gold' => Account::where('account_type', $type)
                        ->where('is_active', false)
                        ->sum('ballance_gold'),
                    'silver' => Account::where('account_type', $type)
                        ->where('is_active', false)
                        ->sum('ballance_silver'),
                ],
            ];
        }

        return view('filament.widgets.account-balances', [
            'balances' => $balances,
        ]);
    }
}
