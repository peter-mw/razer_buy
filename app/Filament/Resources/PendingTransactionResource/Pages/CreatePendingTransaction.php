<?php

namespace App\Filament\Resources\PendingTransactionResource\Pages;

use App\Filament\Resources\PendingTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePendingTransaction extends CreateRecord
{
    protected static string $resource = PendingTransactionResource::class;
}
