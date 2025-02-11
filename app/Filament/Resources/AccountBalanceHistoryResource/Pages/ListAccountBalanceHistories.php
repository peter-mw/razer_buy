<?php

namespace App\Filament\Resources\AccountBalanceHistoryResource\Pages;

use App\Filament\Resources\AccountBalanceHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListAccountBalanceHistories extends ListRecords
{
    protected static string $resource = AccountBalanceHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewTopUps')
                ->label('Recent Top-ups')
                ->icon('heroicon-o-arrow-up-circle')
                ->url(static::$resource::getUrl('top-ups')),
            Actions\CreateAction::make(),
        ];
    }
}
