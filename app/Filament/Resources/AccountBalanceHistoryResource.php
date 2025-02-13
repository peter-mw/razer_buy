<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountBalanceHistoryResource\Pages;
use App\Filament\Resources\AccountBalanceHistoryResource\RelationManagers;
use App\Filament\Resources\AccountBalanceHistoryResource\Widgets;
use App\Models\AccountBalanceHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountBalanceHistoryResource extends Resource
{
    protected static ?string $model = AccountBalanceHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Balance History';
    protected static ?int $navigationSort = 20;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('account_id')
                    ->relationship('account', 'name')
                    ->required(),
                Forms\Components\TextInput::make('balance_gold')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('balance_silver')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\DateTimePicker::make('balance_update_time')
                    ->required(),
                Forms\Components\TextInput::make('balance_event')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('balance_update_time', 'desc')
            ->paginated([100, 200, 500, 'all'])
            ->columns([
                Tables\Columns\TextColumn::make('account.id')
                    ->label('Id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Account')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_gold')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_silver')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_update_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_event')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account_id')
                    ->relationship('account', 'name')
                    ->label('Account')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('balance_event')
                    ->options([
                        'topup' => 'Top-up',
                        'null' => 'No Event',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value']) {
                            'topup' => $query->where('balance_event', 'topup'),
                            'null' => $query->whereNull('balance_event'),
                            default => $query
                        };
                    }),
                Tables\Filters\Filter::make('balance_gold_range')
                    ->form([
                        Forms\Components\TextInput::make('balance_gold_min')
                            ->numeric()
                            ->label('Minimum Gold'),
                        Forms\Components\TextInput::make('balance_gold_max')
                            ->numeric()
                            ->label('Maximum Gold'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['balance_gold_min'],
                                fn(Builder $query, $min): Builder => $query->where('balance_gold', '>=', $min)
                            )
                            ->when(
                                $data['balance_gold_max'],
                                fn(Builder $query, $max): Builder => $query->where('balance_gold', '<=', $max)
                            );
                    }),
                Tables\Filters\Filter::make('balance_silver_range')
                    ->form([
                        Forms\Components\TextInput::make('balance_silver_min')
                            ->numeric()
                            ->label('Minimum Silver'),
                        Forms\Components\TextInput::make('balance_silver_max')
                            ->numeric()
                            ->label('Maximum Silver'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['balance_silver_min'],
                                fn(Builder $query, $min): Builder => $query->where('balance_silver', '>=', $min)
                            )
                            ->when(
                                $data['balance_silver_max'],
                                fn(Builder $query, $max): Builder => $query->where('balance_silver', '<=', $max)
                            );
                    }),
                Tables\Filters\Filter::make('balance_update_time')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('balance_update_time', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('balance_update_time', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('balance_update_time', 'desc');
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
            'index' => Pages\ListAccountBalanceHistories::route('/'),
            'create' => Pages\CreateAccountBalanceHistory::route('/create'),
            'edit' => Pages\EditAccountBalanceHistory::route('/{record}/edit'),
            'top-ups' => Pages\ListRecentTopUps::route('/top-ups'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\AccountBalanceChart::class,
        ];
    }
}
