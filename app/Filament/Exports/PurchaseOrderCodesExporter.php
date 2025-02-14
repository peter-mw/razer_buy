<?php

namespace App\Filament\Exports;

use App\Models\PurchaseOrderCodesView;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderCodesExporter extends Exporter
{
    protected static ?string $model = PurchaseOrderCodesView::class;



    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('Code ID'),
            ExportColumn::make('account.name')
                ->label('Account Name'),
            ExportColumn::make('code')
                ->label('Code'),
            ExportColumn::make('serial_number')
                ->label('Serial Number'),
            ExportColumn::make('product.id')
                ->label('Product ID'),
            ExportColumn::make('product.remote_crm_product_name')
                ->label('Remote CRM Product'),
            ExportColumn::make('product_name')
                ->label('Product Name'),
            ExportColumn::make('product_edition')
                ->label('Product Edition'),
            ExportColumn::make('buy_date')
                ->label('Buy Date'),
            ExportColumn::make('buy_value')
                ->label('Buy Value'),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
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
