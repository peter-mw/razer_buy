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
use Phpsa\FilamentPasswordReveal\Password;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 1;

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
                Forms\Components\Select::make('account_type')
                    ->required()
                    ->options([
                        'global' => 'Global',
                        'usa' => 'USA',
                    ])
                ,


                Forms\Components\TextInput::make('limit_amount_per_day')
                    ->numeric()
                    ->default(0)
                    ->step('0.01')
                    ->required(),


                Password::make('password')
                    ->revealable()
                    ->required()
                    ->maxLength(255),
                Password::make('otp_seed')
                    ->revealable()
                    ->maxLength(255),


                Forms\Components\TextInput::make('vendor')
                    ->maxLength(255),
                Password::make('email_password')
                    ->revealable()
                    ->maxLength(255),


                Forms\Components\TextInput::make('ballance_gold')
                    ->numeric()
                    ->default(0)
                    ->step('0.01')
                    ->disabled(),
                Forms\Components\TextInput::make('ballance_silver')
                    ->numeric()
                    ->default(0)
                    ->step('0.01')
                    ->disabled(),
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
                Tables\Columns\TextColumn::make('account_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'global' => 'Global',
                        'usa' => 'USA',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ballance_gold')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ballance_silver')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('limit_amount_per_day')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_ballance_update_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_ballance_update_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'success' => 'success',
                        'error' => 'danger',
                        default => 'warning',
                    })
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
