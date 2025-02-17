<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemLogResource\Pages;
use App\Models\SystemLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use ValentinMorice\FilamentJsonColumn\FilamentJsonColumn;

class SystemLogResource extends Resource
{
    protected static ?string $model = SystemLog::class;
    protected static ?int $navigationSort = 999;
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('account_id')
                    ->relationship('account', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\TextInput::make('order_id')
                    ->numeric()
                    ->nullable(),
                Forms\Components\TextInput::make('source')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('command')
                    ->columnSpanFull(),


                FilamentJsonColumn::make('params')
                ,
                FilamentJsonColumn::make('response')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 20, 25, 50, 100, 250, 500, 1000, 2000, 5000, 'all'])
            ->poll(30)
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('account.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_id')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                     ,
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->color(fn(string $state): string => match ($state) {
                        'success' => 'success',
                        'error' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('params')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('response')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('command')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account')
                    ->relationship('account', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'error' => 'Error',
                    ]),
                Tables\Filters\Filter::make('command')
                    ->form([
                        Forms\Components\TextInput::make('command')
                            ->label('Command')
                            ->placeholder('Search command...'),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['command']) {
                            $query->where('command', 'like', "%{$data['command']}%");
                        }
                    }),
                Tables\Filters\Filter::make('source')
                    ->form([
                        Forms\Components\TextInput::make('source')
                            ->label('Source')
                            ->placeholder('Search source...'),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['source']) {
                            $query->where('source', 'like', "%{$data['source']}%");
                        }
                    }),
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
