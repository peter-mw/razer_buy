<?php

namespace App\Filament\Exports;

use App\Models\Account;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\DB;

class AccountReconciliationExporter extends Exporter
{
    protected static ?string $model = Account::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Name'),
            ExportColumn::make('ballance_gold')
                ->label('Gold Balance'),
            ExportColumn::make('topup_balance')
                ->label('Topup Balance'),
            ExportColumn::make('transaction_balance')
                ->label('Transaction Balance'),
            ExportColumn::make('balance_difference')
                ->label('Balance Difference'),
            ExportColumn::make('codes_count')
                ->label('Total Codes')
                ->state(fn (Account $record): int => $record->codes()->count()),
            ExportColumn::make('transactions_count')
                ->label('Total Transactions')
                ->state(fn (Account $record): int => $record->transactions()->count()),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your account reconciliation export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
