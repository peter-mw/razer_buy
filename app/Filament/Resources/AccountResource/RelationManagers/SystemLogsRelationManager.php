<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SystemLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'systemLogs';

    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {


        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->searchable()
                    ->sortable(), Tables\Columns\TextColumn::make('command')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('params')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('response')
                    ->searchable()
                    ->sortable()
                    ->wrap(), Tables\Columns\TextColumn::make('status')

                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}
