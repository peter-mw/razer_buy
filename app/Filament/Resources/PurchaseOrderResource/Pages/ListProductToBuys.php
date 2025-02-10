<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Widgets\AccountBalancesWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductToBuys extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

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
