<?php

namespace App\Filament\Exports;

use App\Models\AccountTopup;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AccountTopupExporter extends Exporter
{
    protected static ?string $model = AccountTopup::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('account.id')
                ->label('Account ID'),
            ExportColumn::make('account.name')
                ->label('Account Name'),

            ExportColumn::make('transaction_id')
                ->label('Transaction Id'),

            ExportColumn::make('transaction_ref')
                ->label('Transaction Ref'),

            ExportColumn::make('topup_amount')
                ->label('Topup Amount'),
            ExportColumn::make('topup_time')
                ->label('Topup Time'),

          /*  ExportColumn::make('date')
                ->label('Date'),*/
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
        ];
    }

    public function getFileName(Export $export): string
    {
        return 'topup_' . now();
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your account topup export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
