<?php

namespace App\Filament\Exports;

use App\Models\Code;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Illuminate\Database\Eloquent\Builder;

class CodeExporter extends Exporter
{
    protected static ?string $model = Code::class;

    public static function getCompletedNotificationBody(\Filament\Actions\Exports\Models\Export $export): string
    {
        return 'Your codes export has completed and is ready to download.';
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('account.name')
                ->label('Account'),
            ExportColumn::make('order.id')
                ->label('Order ID'),
            ExportColumn::make('code')
                ->label('Code'),
            ExportColumn::make('serial_number')
                ->label('Serial Number'),
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


}
