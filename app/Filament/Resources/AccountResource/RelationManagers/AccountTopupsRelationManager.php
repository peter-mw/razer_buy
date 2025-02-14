<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class AccountTopupsRelationManager extends RelationManager
{
    protected static string $relationship = 'accountTopups';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('topup_amount')
                    ->required()
                    ->numeric()
                    ->step('0.01'),
                Forms\Components\DateTimePicker::make('topup_time')
                    ->required(),
                Forms\Components\TextInput::make('transaction_ref')
                    ->required(),
                Forms\Components\TextInput::make('transaction_id')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('topup_amount')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('topup_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_ref')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
