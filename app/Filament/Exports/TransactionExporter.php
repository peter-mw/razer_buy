<?php

namespace App\Filament\Exports;

use App\Models\Transaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TransactionExporter extends Exporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('transaction_id')
                ->label('Transaction ID'),
            ExportColumn::make('account.email')
                ->label('Account Email'),
            ExportColumn::make('amount')
                ->label('Amount'),
            ExportColumn::make('transaction_date')
                ->label('Transaction Date'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your transaction export has completed and is ready to download.';
    }
}
