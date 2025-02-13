<?php

namespace App\Filament\Resources\AccountReconciliationResource\Pages;

use App\Filament\Resources\AccountReconciliationResource;
use Filament\Resources\Pages\ListRecords;

class ListAccountReconciliations extends ListRecords
{
    protected static string $resource = AccountReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
