<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ProductPurchaseActivityWidget extends ChartWidget
{
    protected static ?string $heading = 'Product Purchase Activity';
    protected static ?string $maxHeight = '400px';
    protected static ?string $pollingInterval = null;
    protected int|string|array $columnSpan = 12;

    protected function getData(): array
    {
        // Get transactions grouped by product for the last 7 days
        $transactions = Transaction::query()
            ->select('products.product_name', DB::raw('COUNT(*) as count'), DB::raw('DATE(transactions.created_at) as date'))
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->whereBetween('transactions.created_at', [now()->subDays(7), now()])
            ->groupBy('products.product_name', 'date')
            ->orderBy('date')
            ->get();

        // Get unique product names and dates
        $productNames = $transactions->pluck('product_name')->unique();
        $dates = collect(range(0, 6))->map(fn($days) => now()->subDays($days)->format('Y-m-d'))->reverse();

        // Prepare datasets
        $datasets = $productNames->map(function ($productName) use ($transactions, $dates) {
            $data = $dates->map(function ($date) use ($transactions, $productName) {
                return $transactions
                    ->where('product_name', $productName)
                    ->where('date', $date)
                    ->first()?->count ?? 0;
            });

            // Generate a unique color for each product
            $color = '#' . substr(md5($productName), 0, 6);

            return [
                'label' => $productName,
                'data' => $data->values()->toArray(),
                'borderColor' => $color,
                'tension' => 0.4,
            ];
        });

        return [
            'labels' => $dates->values()->toArray(),
            'datasets' => $datasets->values()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
                        'text' => 'Transactions',
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'maxRotation' => 45,
                    ],
                ],
            ],
        ];
    }
}
