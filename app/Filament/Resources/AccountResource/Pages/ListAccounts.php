<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;


    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(Account::count()),
            'active' => Tab::make('Active')
                ->badge(Account::where('is_active', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true)),
            'inactive' => Tab::make('Inactive')
                ->badge(Account::where('is_active', false)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', false)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
