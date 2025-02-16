<?php

namespace App\Filament\Resources\CodesWithMissingProductResource\Pages;

use App\Filament\Resources\CodesWithMissingProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCodesWithMissingProduct extends EditRecord
{
    protected static string $resource = CodesWithMissingProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
