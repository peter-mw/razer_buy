<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TransactionStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {

        $globalStats = Transaction::query()
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->where('accounts.account_type', 'global')
            ->where('transactions.created_at', '>=', now()->subHours(24))
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->first();

        $usaStats = Transaction::query()
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->where('accounts.account_type', 'usa')
            ->where('transactions.created_at', '>=', now()->subHours(24))
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->first();

        return [
            Stat::make('Global Transactions (24h)', $globalStats->count)
                ->description('Total Amount: $' . number_format($globalStats->total_amount ?? 0, 2))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning'),

            Stat::make('USA Transactions (24h)', $usaStats->count)
                ->description('Total Amount: $' . number_format($usaStats->total_amount ?? 0, 2))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Transactions (24h)', $globalStats->count + $usaStats->count)
                ->description('Total Amount: $' . number_format(($globalStats->total_amount ?? 0) + ($usaStats->total_amount ?? 0), 2))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),
        ];
    }
}
