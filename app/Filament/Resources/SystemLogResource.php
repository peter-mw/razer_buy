<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemLogResource\Pages;
use App\Models\SystemLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SystemLogResource extends Resource
{
    protected static ?string $model = SystemLog::class;
    protected static ?int $navigationSort = 999;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('source')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
                \InvadersXX\FilamentJsoneditor\Forms\JsonEditor::make('params')
                    ->required(),
                \InvadersXX\FilamentJsoneditor\Forms\JsonEditor::make('response')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->searchable(),
                Tables\Columns\TextColumn::make('params')
                    ->searchable(),
                Tables\Columns\TextColumn::make('response')
                    ->searchable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemLogs::route('/'),
            'create' => Pages\CreateSystemLog::route('/create'),
            'edit' => Pages\EditSystemLog::route('/{record}/edit'),
        ];
    }
}
