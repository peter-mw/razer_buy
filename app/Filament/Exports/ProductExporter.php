<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('Product ID'),
            ExportColumn::make('product_name')
                ->label('Product Name'),
            ExportColumn::make('product_slug')
                ->label('Product Slug'),
            ExportColumn::make('account_type')
                ->label('Account Type'),
            ExportColumn::make('product_edition')
                ->label('Product Edition'),
            ExportColumn::make('product_buy_value')
                ->label('Buy Value'),
            ExportColumn::make('product_face_value')
                ->label('Face Value'),
            ExportColumn::make('remote_crm_product_name')
                ->label('Remote CRM Product Name'),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your products export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
