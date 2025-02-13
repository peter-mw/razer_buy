<?php

namespace App\Filament\Exports;

use App\Models\Account;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AccountExporter extends Exporter
{
    protected static ?string $model = Account::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Name'),
            ExportColumn::make('email')
                ->label('Email'),
            ExportColumn::make('password')
                ->label('Password'),
            ExportColumn::make('otp_seed')
                ->label('OTP Seed'),
            ExportColumn::make('account_type')
                ->label('Account Type'),
            ExportColumn::make('vendor')
                ->label('Vendor'),
            ExportColumn::make('email_password')
                ->label('Email Password'),
            ExportColumn::make('ballance_gold')
                ->label('Gold Balance'),
            ExportColumn::make('ballance_silver')
                ->label('Silver Balance'),
            ExportColumn::make('limit_amount_per_day')
                ->label('Daily Limit'),
            ExportColumn::make('last_ballance_update_at')
                ->label('Last Balance Update'),
            ExportColumn::make('last_ballance_update_status')
                ->label('Last Update Status'),
            ExportColumn::make('service_code')
                ->label('Service Code'),
            ExportColumn::make('client_id_login')
                ->label('Client ID Login'),
            ExportColumn::make('is_active')
                ->label('Active'),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your accounts export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
