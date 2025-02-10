<?php

namespace App\Filament\Resources\AccountBalanceHistoryResource\Pages;

use App\Filament\Resources\AccountBalanceHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountBalanceHistories extends ListRecords
{
    protected static string $resource = AccountBalanceHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
