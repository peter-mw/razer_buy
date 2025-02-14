<?php

namespace App\Filament\Resources\AccountTopupResource\Pages;

use App\Filament\Resources\AccountTopupResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Session;

class CreateAccountTopup extends CreateRecord
{
    protected static string $resource = AccountTopupResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Session::put('last_topup_account_id', $data['account_id']);
        Session::put('last_topup_time', $data['topup_time']);
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
