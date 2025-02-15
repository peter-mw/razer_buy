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
        // Active accounts
        $activeGlobalGold = Account::where('account_type', 'global')
            ->where('is_active', true)
            ->sum('ballance_gold');
        $activeGlobalSilver = Account::where('account_type', 'global')
            ->where('is_active', true)
            ->sum('ballance_silver');
        $activeUsaGold = Account::where('account_type', 'usa')
            ->where('is_active', true)
            ->sum('ballance_gold');
        $activeUsaSilver = Account::where('account_type', 'usa')
            ->where('is_active', true)
            ->sum('ballance_silver');

        // Inactive accounts
        $inactiveGlobalGold = Account::where('account_type', 'global')
            ->where('is_active', false)
            ->sum('ballance_gold');
        $inactiveGlobalSilver = Account::where('account_type', 'global')
            ->where('is_active', false)
            ->sum('ballance_silver');
        $inactiveUsaGold = Account::where('account_type', 'usa')
            ->where('is_active', false)
            ->sum('ballance_gold');
        $inactiveUsaSilver = Account::where('account_type', 'usa')
            ->where('is_active', false)
            ->sum('ballance_silver');

        return view('filament.widgets.account-balances', [
            'balances' => [
                'global' => [
                    'active' => [
                        'gold' => $activeGlobalGold,
                        'silver' => $activeGlobalSilver,
                    ],
                    'inactive' => [
                        'gold' => $inactiveGlobalGold,
                        'silver' => $inactiveGlobalSilver,
                    ],
                ],
                'usa' => [
                    'active' => [
                        'gold' => $activeUsaGold,
                        'silver' => $activeUsaSilver,
                    ],
                    'inactive' => [
                        'gold' => $inactiveUsaGold,
                        'silver' => $inactiveUsaSilver,
                    ],
                ],
            ],
        ]);
    }
}
