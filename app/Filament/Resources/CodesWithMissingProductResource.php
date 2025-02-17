<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CodesWithMissingProductResource\Pages;
use App\Models\CodesWithMissingProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CodesWithMissingProductResource extends Resource
{
    protected static ?string $model = CodesWithMissingProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';


    protected static ?string $navigationLabel = 'Codes Without Products';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('account_id')
                    ->readOnly(),
                Forms\Components\TextInput::make('account.name')
                    ->label('Account Name')
                    ->formatStateUsing(fn (Forms\Get $get): string => \App\Models\Account::find($get('account_id'))?->name ?? '')
                    ->readOnly(),
                Forms\Components\TextInput::make('account.email')
                    ->label('Account Email')
                    ->formatStateUsing(fn (Forms\Get $get): string => \App\Models\Account::find($get('account_id'))?->email ?? '')
                    ->readOnly(),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('serial_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('product_name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                        $set('product_slug', Str::slug($state));
                    }),
                Forms\Components\TextInput::make('product_slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('account_type')
                    ->default('unknown')
                    ->maxLength(255),
                Forms\Components\TextInput::make('product_edition')
                    ->default('unknown')
                    ->maxLength(255),
                Forms\Components\TextInput::make('product_buy_value')
                    ->numeric()
                    ->default(0)
                    ->step('0.01'),
                Forms\Components\TextInput::make('product_face_value')
                    ->numeric()
                    ->default(0)
                    ->step('0.01'),
                Forms\Components\DateTimePicker::make('buy_date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('buy_value')
                    ->required()
                    ->numeric()
                    ->step('0.01'),
                Forms\Components\TextInput::make('transaction_ref')
                    ->maxLength(255),
                Forms\Components\TextInput::make('transaction_id')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([10,20,25,50,100, 250, 500, 1000, 2000, 5000, 'all'])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),


                Tables\Columns\TextColumn::make('account_id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_slug')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('account_type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_edition')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_buy_value')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_face_value')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('buy_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('buy_value')
                    ->money()
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money()
                    ]),
                Tables\Columns\TextColumn::make('transaction_ref')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->label('Account')
                    ->searchable()
                    ->relationship('account', 'name'),
                Tables\Filters\SelectFilter::make('product_name')
                    ->label('Product Name')
                    ->searchable()
                    ->options(fn(): array => CodesWithMissingProduct::whereNotNull('product_name')->distinct()->pluck('product_name', 'product_name')->toArray()),
     /*

                Tables\Filters\SelectFilter::make('product_edition')
                    ->label('Product Edition')
                    ->searchable()
                    ->options(fn(): array => CodesWithMissingProduct::whereNotNull('product_edition')->distinct()->pluck('product_edition', 'product_edition')->toArray()),


                */
                Tables\Filters\Filter::make('buy_date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('buy_date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('buy_date', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('purgeAll')
                    ->label('Purge All')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(fn () => CodesWithMissingProduct::truncate()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('purgeAll')
                        ->label('Purge All')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->action(fn () => CodesWithMissingProduct::truncate())
                        ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListCodesWithMissingProducts::route('/'),
            'create' => Pages\CreateCodesWithMissingProduct::route('/create'),
            'edit' => Pages\EditCodesWithMissingProduct::route('/{record}/edit'),
        ];
    }
}
