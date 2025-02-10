<?php

namespace App\Filament\Resources\AccountBalanceHistoryResource\Pages;

use App\Filament\Resources\AccountBalanceHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccountBalanceHistory extends EditRecord
{
    protected static string $resource = AccountBalanceHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
