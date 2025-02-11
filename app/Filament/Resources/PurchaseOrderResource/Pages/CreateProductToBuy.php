<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProductToBuy extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        
        // Redirect to the edit page where the process button is available
        $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
    }
}
