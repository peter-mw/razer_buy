<?php

namespace App\Filament\Resources\AccountTopupResource\Pages;

use App\Filament\Resources\AccountTopupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountTopups extends ListRecords
{
    protected static string $resource = AccountTopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
