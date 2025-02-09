<?php

namespace App\Filament\Resources\ProductToBuyResource\Pages;

use App\Filament\Resources\ProductToBuyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductToBuys extends ListRecords
{
    protected static string $resource = ProductToBuyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
