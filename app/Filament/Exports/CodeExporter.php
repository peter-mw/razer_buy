<?php

namespace App\Filament\Exports;

use App\Models\Code;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CodeExporter extends Exporter
{
    protected static ?string $model = Code::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('account.name'),
            ExportColumn::make('code'),
            ExportColumn::make('serial_number'),
            ExportColumn::make('product.id'),
            ExportColumn::make('product_name'),
            ExportColumn::make('product_edition'),
            ExportColumn::make('buy_date'),
            ExportColumn::make('buy_value'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your code export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
