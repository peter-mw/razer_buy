<?php

namespace App\Filament\Resources\AccountBalanceHistoryResource\Widgets;

use App\Models\Account;
use App\Models\AccountBalanceHistory;
use Filament\Widgets\ChartWidget;

class AccountBalanceChart extends ChartWidget
{
    protected static ?string $heading = 'Gold Balance History';
    protected static ?string $maxHeight = '400px';
    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 12;

    protected function getData(): array
    {
        $data = AccountBalanceHistory::selectRaw('
                account_id,
                DATE(balance_update_time) as date,
                MAX(balance_gold) as daily_gold
            ')
            ->groupBy('account_id', 'date')
            ->orderBy('date')
            ->get()
            ->groupBy('account_id');

        $datasets = [];
        $allDates = collect();

        foreach ($data as $accountId => $records) {
            $accountName = Account::find($accountId)->name;

            // Collect all unique dates
            $allDates = $allDates->merge($records->pluck('date'));

            $datasets[] = [
                'label' => $accountName,
                'data' => $records->pluck('daily_gold')->toArray(),
                'borderWidth' => 2,
                'tension' => 0.3,
                'fill' => false,
            ];
        }

        // Get unique sorted dates for labels
        $labels = $allDates->unique()->sort()->values()->toArray();

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Gold',
                    ],
                ],
                'x' => [
                    'type' => 'time',
                    'time' => [
                        'unit' => 'day',
                        'displayFormats' => [
                            'day' => 'MMM D, YYYY',
                        ],
                    ],
                    'ticks' => [
                        'maxRotation' => 45,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
