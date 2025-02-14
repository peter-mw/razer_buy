<?php

namespace App\Filament\Imports;

use App\Models\AccountTopup;
use App\Models\Account;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class AccountTopupImporter extends Importer
{
    protected static ?string $model = AccountTopup::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->label('Account Id')
                ->requiredMapping(),
            ImportColumn::make('topup_amount')
                ->label('Amount')
                ->numeric()
                ->requiredMapping(),
            ImportColumn::make('topup_time')
                ->label('Time')
                ->requiredMapping(),
        ];
    }

    
    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your account topup import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
