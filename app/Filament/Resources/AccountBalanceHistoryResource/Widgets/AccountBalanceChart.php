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
    
    protected function getData(): array
    {
        $data = AccountBalanceHistory::select('account_id', 'balance_update_time', 'balance_gold')
            ->orderBy('balance_update_time')
            ->get()
            ->groupBy('account_id');

        $datasets = [];
        $labels = [];
        
        foreach ($data as $accountId => $records) {
            $accountName = Account::find($accountId)->name;
            $balances = $records->pluck('balance_gold')->toArray();
            $timestamps = $records->pluck('balance_update_time')->map(function($date) {
                return $date->format('Y-m-d H:i');
            })->toArray();
            
            $datasets[] = [
                'label' => $accountName,
                'data' => $balances,
                'borderWidth' => 2,
                'tension' => 0.3,
            ];
            
            $labels = array_unique(array_merge($labels, $timestamps));
        }
        
        sort($labels);
        
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
                        'unit' => 'hour',
                        'displayFormats' => [
                            'hour' => 'MMM D, HH:mm',
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
