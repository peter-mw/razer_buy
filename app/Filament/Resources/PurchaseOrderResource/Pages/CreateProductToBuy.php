<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductToBuy extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;
}
