<?php

namespace App\Filament\Widgets;

use LaraZeus\InlineChart\InlineChartWidget;

class AccountCodesInlineChartWidget extends InlineChartWidget
{
    //#protected static ?string $maxHeight = '50';
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Account Activity';
    //public int $maxWidth = 100;

    public function getData(): array
    {
        if(!isset( $this->record)) {
            return [];
        }



        // Get the account's codes and transactions for the last 7 days
        $codes = \App\Models\Code::where('account_id', $this->record->id)
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $transactions = \App\Models\Transaction::where('account_id', $this->record->id)
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Prepare data for the last 7 days
        $dates = collect(range(0, 6))->map(fn ($days) => now()->subDays($days)->format('Y-m-d'));

        $chartData = [
            'labels' => $dates->reverse()->values()->toArray(),
            'datasets' => [
                [
                    'label' => 'Codes',
                    'data' => $dates->reverse()->map(fn ($date) => $codes[$date] ?? 0)->values()->toArray(),
                    'borderColor' => '#10B981', // Green
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Transactions',
                    'data' => $dates->reverse()->map(fn ($date) => $transactions[$date] ?? 0)->values()->toArray(),
                    'borderColor' => '#3B82F6', // Blue
                    'tension' => 0.4,
                ],
            ],
        ];

        return $chartData;
    }
}
