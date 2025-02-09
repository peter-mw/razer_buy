<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Filament\Resources\AccountResource\RelationManagers;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->hiddenOn('edit')
                    ->maxLength(255),
                Forms\Components\TextInput::make('otp_seed')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ballance_gold')
                    ->numeric()
                    ->default(0)
                    ->step('0.01')
                    ->required(),
                Forms\Components\TextInput::make('ballance_silver')
                    ->numeric()
                    ->default(0)
                    ->step('0.01')
                    ->required(),
                Forms\Components\TextInput::make('limit_orders_per_day')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make('sync_all')
                    ->label('Sync All Accounts')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function () {
                        try {
                            Artisan::call('accounts:sync-balances');
                            Notification::make()
                                ->success()
                                ->title('All accounts synced successfully')
                                ->body(Artisan::output())
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Failed to sync accounts')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ballance_gold')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ballance_silver')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('limit_orders_per_day')
                    ->numeric()
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('sync')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Account $record): void {
                        try {
                            $output = Artisan::call('accounts:sync-balances', [
                                'account-id' => $record->id
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Account synced successfully')
                                ->body(Artisan::output())
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Failed to sync account')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->tooltip('Sync account balances')
                    ->color('success'),
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
