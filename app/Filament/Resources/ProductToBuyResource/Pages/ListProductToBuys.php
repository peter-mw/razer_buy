<?php

namespace App\Filament\Resources\ProductToBuyResource\Pages;

use App\Filament\Resources\ProductToBuyResource;
use App\Filament\Widgets\AccountBalancesWidget;
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

    protected function getHeaderWidgets(): array
    {
        return [
            AccountBalancesWidget::class,
        ];
    }
}
