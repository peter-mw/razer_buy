<?php

namespace App\Filament\Exports;

use App\Models\Code;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class RemoteCrmExporter extends Exporter
{
    protected static ?string $model = Code::class;

    public static function getCompletedNotificationBody(\Filament\Actions\Exports\Models\Export $export): string
    {
        return 'Your Remote CRM export has completed and is ready to download.';
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('product.remote_crm_product_name')
                ->label('Product Name')
                ->state(fn($record) => $record->product?->remote_crm_product_name ?? ''),
            ExportColumn::make('buy_value')
                ->label('Cost')
                ->state(fn($record) => $record->buy_value * 0.83), // Apply 17% discount
            ExportColumn::make('currency')
                ->label('Currency')
                ->state(fn() => 'USD'),
            ExportColumn::make('account.name')
                ->label('Source')
                ->state(fn($record) => $record->account?->name ?? ''),
            ExportColumn::make('account.vendor')
                ->label('Vendor')
                ->state(fn($record) => $record->account?->vendor ?? ''),

            ExportColumn::make('serial_number')
                ->label('Serial'),
            ExportColumn::make('number')
                ->label('Number')
                ->state(fn() => ''),
            ExportColumn::make('cvv')
                ->label('CVV')
                ->state(fn() => ''),
            ExportColumn::make('code')
                ->label('Pin'),
        ];
    }
    /*    public function getBuilder(): Builder
        {

            dd(  $this->form->getRawState());
            return parent::getBuilder();

        }*/

    /*   public static function modifyQuery(Builder $query): Builder
       {
           $filters = static::getFilters();


           return $query->whereBetween('created_at', [$filters['from_date'], $filters['to_date']])
               ->where('edcount', $filters['edcount']);
       }*/


    public static function getFormComponents(): array
    {
        return [
            DatePicker::make('from_date')
                ->label('From Date')
                ->required(),
            DatePicker::make('to_date')
                ->label('To Date')
                ->required(),
            TextInput::make('edcount')
                ->label('Edcount')
                ->numeric()
                ->required(),
        ];
    }


}
