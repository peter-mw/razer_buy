<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->label('Product ID')
                ->numeric(),
            ImportColumn::make('product_name')
                ->label('Product Name'),
            ImportColumn::make('product_slug')
                ->label('Product Slug'),
            ImportColumn::make('account_type')
                ->label('Account Type'),
            ImportColumn::make('product_edition')
                ->label('Product Edition'),
            ImportColumn::make('product_buy_value')
                ->label('Buy Value')
                ->numeric(),
            ImportColumn::make('product_face_value')
                ->label('Face Value')
                ->numeric(),
            ImportColumn::make('remote_crm_product_name')
                ->label('Remote CRM Product Name'),
        ];
    }

    public function resolveRecord(): ?Product
    {
        // Try to find existing record by ID
        return Product::firstOrNew([
            'id' => $this->data['id'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
