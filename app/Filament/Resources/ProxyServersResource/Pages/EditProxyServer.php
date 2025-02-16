<?php

namespace App\Filament\Resources\ProxyServersResource\Pages;

use App\Filament\Resources\ProxyServersResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProxyServer extends EditRecord
{
    protected static string $resource = ProxyServersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
