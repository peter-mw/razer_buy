<?php

namespace App\Filament\Resources\PendingTransactionResource\Pages;

use App\Filament\Resources\PendingTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPendingTransactions extends ListRecords
{
    protected static string $resource = PendingTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
