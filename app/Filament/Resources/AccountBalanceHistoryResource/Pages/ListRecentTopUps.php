<?php

namespace App\Filament\Resources\AccountBalanceHistoryResource\Pages;

use App\Filament\Resources\AccountBalanceHistoryResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListRecentTopUps extends ListRecords
{
    protected static string $resource = AccountBalanceHistoryResource::class;

    protected static ?string $title = 'Recent Top-ups';

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->where('balance_event', 'topup')
            ->latest('balance_update_time');
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated([10,20,25,50,100, 250, 500, 1000, 2000, 5000, 'all'])
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('account.id')
                    ->label('Id')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('account.name')
                    ->label('Account')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('balance_gold')
                    ->numeric()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('balance_silver')
                    ->numeric()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('balance_update_time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('balance_update_time', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('account_id')
                    ->relationship('account', 'name')
                    ->label('Account')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make(),
            ]);
    }
}
