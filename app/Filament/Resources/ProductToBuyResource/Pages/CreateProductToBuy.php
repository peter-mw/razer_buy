<?php

namespace App\Filament\Resources\ProductToBuyResource\Pages;

use App\Filament\Resources\ProductToBuyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductToBuy extends CreateRecord
{
    protected static string $resource = ProductToBuyResource::class;
}
