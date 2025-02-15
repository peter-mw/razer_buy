<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountBalancesWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
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

        // Totals
        $totalGlobalGold = $activeGlobalGold + $inactiveGlobalGold;
        $totalGlobalSilver = $activeGlobalSilver + $inactiveGlobalSilver;
        $totalUsaGold = $activeUsaGold + $inactiveUsaGold;
        $totalUsaSilver = $activeUsaSilver + $inactiveUsaSilver;

        return [
            // Active Global
            Stat::make('Active Global Gold', '$' . number_format($activeGlobalGold, 2))
                ->description('Active global account gold balance')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Active Global Silver', '$' . number_format($activeGlobalSilver, 2))
                ->description('Active global account silver balance')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('gray'),

            // Active USA
            Stat::make('Active USA Gold', '$' . number_format($activeUsaGold, 2))
                ->description('Active USA account gold balance')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Active USA Silver', '$' . number_format($activeUsaSilver, 2))
                ->description('Active USA account silver balance')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('gray'),

            // Inactive Global
            Stat::make('Inactive Global Gold', '$' . number_format($inactiveGlobalGold, 2))
                ->description('Inactive global account gold balance')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('gray'),


                        // Total Global
                        Stat::make('Total Global Gold', '$' . number_format($totalGlobalGold, 2))
                            ->description('Total global account gold balance')
                            ->descriptionIcon('heroicon-m-currency-dollar')
                            ->color('warning'),

                        Stat::make('Total Global Silver', '$' . number_format($totalGlobalSilver, 2))
                            ->description('Total global account silver balance')
                            ->descriptionIcon('heroicon-m-currency-dollar')
                            ->color('gray'),

             Stat::make('Total USA Silver', '$' . number_format($totalUsaSilver, 2))
                            ->description('Total USA account silver balance')
                            ->descriptionIcon('heroicon-m-currency-dollar')
                            ->color('gray'),

                        // Total USA
                        Stat::make('Total USA Gold', '$' . number_format($totalUsaGold, 2))
                            ->description('Total USA account gold balance')
                            ->descriptionIcon('heroicon-m-currency-dollar')
                            ->color('success'),



        ];
    }
}
