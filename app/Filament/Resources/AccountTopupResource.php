<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountTopupResource\Pages;
use App\Models\AccountTopup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccountTopupResource extends Resource
{
    protected static ?string $model = AccountTopup::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?int $navigationSort = 20;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('account_id')
                    ->label('Account')
                    ->native(true)
                    ->searchable()
                    ->preload()
                    ->relationship(
                        'account',
                        'id',
                        fn ($query) => $query->select(['id',  'name'])
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->required(),
                Forms\Components\TextInput::make('topup_amount')
                    ->required()
                    ->numeric()
                     ->step(0.01),
                Forms\Components\DateTimePicker::make('topup_time')
                    ->native(true)
                    ->default(now())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account.id')
                    ->label('Account ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Account')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('topup_amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('topup_time')
                    ->dateTime()
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
                Tables\Filters\SelectFilter::make('account')
                    ->relationship('account', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name),
                Tables\Filters\Filter::make('topup_time')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('topup_time', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('topup_time', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('topup_time', 'desc');
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
            'index' => Pages\ListAccountTopups::route('/'),
            'view' => Pages\ViewAccountTopup::route('/{record}'),
        ];
    }

   /* public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count();
    }*/
}
