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
use App\Filament\Exports;
use App\Filament\Imports;
use Illuminate\Support\Facades\Session;

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
                        fn($query) => $query->select(['id', 'name'])
                    )
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                    ->required()
                    ->default(fn() => Session::get('last_topup_account_id')),
                Forms\Components\TextInput::make('topup_amount')
                    ->required()
                    ->numeric()
                    ->step(0.01),
                Forms\Components\DateTimePicker::make('topup_time')
                    ->native(true)
                    ->default(fn() => Session::get('last_topup_time', now()))
                    ->required(),
                Forms\Components\TextInput::make('transaction_ref')
                    ->maxLength(255),
                Forms\Components\TextInput::make('transaction_id')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([100, 250, 500, 1000, 2000, 5000, 'all'])
            ->defaultSort('created_at', 'desc')
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
                    ->date(format: 'Y-m-d H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('transaction_ref')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                /*Tables\Actions\ImportAction::make()
                    ->importer(Imports\AccountTopupImporter::class),*/
                Tables\Actions\ExportAction::make()
                    ->exporter(Exports\AccountTopupExporter::class),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account')
                    ->relationship('account', 'id')
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name),
                Tables\Filters\Filter::make('topup_time')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('topup_time', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('topup_time', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ExportBulkAction::make()
                        ->exporter(Exports\AccountTopupExporter::class)
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
            'create' => Pages\CreateAccountTopup::route('/create'),
            'view' => Pages\ViewAccountTopup::route('/{record}'),
        ];
    }

    /* public static function getNavigationBadge(): ?string
     {
         return static::getModel()::whereDate('created_at', today())->count();
     }*/
}
