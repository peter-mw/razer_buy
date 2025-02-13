<?php

namespace App\Filament\Imports;

use App\Models\Account;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class AccountImporter extends Importer
{
    protected static ?string $model = Account::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Name')
               ,
            ImportColumn::make('email')
                ->label('Email')
             
               ,
            ImportColumn::make('account_type')
                ->label('Account Type')
               ,
            ImportColumn::make('service_code')
                ->label('Service Code')
                
                ->numeric(),
            ImportColumn::make('client_id_login')
                ->label('Client ID Login')
                ,
            ImportColumn::make('password')
                ->label('Password')
                ,
            ImportColumn::make('otp_seed')
                ->label('OTP Seed')
                ,
            ImportColumn::make('vendor')
                ->label('Vendor'),
            ImportColumn::make('email_password')
                ->label('Email Password'),
            ImportColumn::make('limit_amount_per_day')
                ->label('Daily Limit')
                ->numeric()
              ,
            ImportColumn::make('is_active')
                ->label('Active')
                ->boolean()
               ,
        ];
    }

    public function resolveRecord(): ?Account
    {
        // Try to find existing record by email
        return Account::firstOrNew([
            'email' => $this->data['email'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your account import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
