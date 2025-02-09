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
        $globalGold = Account::where('account_type', 'global')->sum('ballance_gold');
        $globalSilver = Account::where('account_type', 'global')->sum('ballance_silver');
        $usaGold = Account::where('account_type', 'usa')->sum('ballance_gold');
        $usaSilver = Account::where('account_type', 'usa')->sum('ballance_silver');

        return [
            Stat::make('Global Gold', '$' . number_format($globalGold, 2))
                ->description('Global account gold balance')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
            
            Stat::make('Global Silver', '$' . number_format($globalSilver, 2))
                ->description('Global account silver balance')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('gray'),

            Stat::make('USA Gold', '$' . number_format($usaGold, 2))
                ->description('USA account gold balance')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('USA Silver', '$' . number_format($usaSilver, 2))
                ->description('USA account silver balance')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('gray'),
        ];
    }
}
