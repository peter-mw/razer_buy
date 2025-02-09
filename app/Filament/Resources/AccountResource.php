<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Filament\Resources\AccountResource\RelationManagers;
use App\Models\Account;
use App\Models\ProductToBuy;
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
                 ,
                Forms\Components\TextInput::make('ballance_silver')
                    ->numeric()
                    ->default(0)
                    ->step('0.01')
                   ,
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
                Tables\Columns\TextColumn::make('purchases_last_24_hours')
                    ->label('Purchases Last 24 Hours')
                    ->getStateUsing(fn(Account $record) => $record->purchasesLast24Hours())
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

                Tables\Filters\SelectFilter::make('account_type')
                    ->options([
                        'global' => 'Global',
                        'usa' => 'USA',
                    ])
                    ->label('Account Type'),
                Tables\Filters\SelectFilter::make('last_ballance_update_status')
                    ->options([
                        'success' => 'Success',
                        'error' => 'Error',
                        'pending' => 'Pending',
                    ])
                    ->label('Last Ballance Update Status'),


            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('sync')
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
                    ->color('success')
                ,
                Action::make('redeem_silver_to_gold')
                    ->label('Redeem Silver to Gold')
                    ->icon('heroicon-o-cash')
                    ->form([
                        Forms\Components\Select::make('product_id')
                            ->label('Product ID')
                            ->options(ProductToBuy::all()->mapWithKeys(function ($product) {
                                return [$product->id => "{$product->product_name} - \${$product->buy_value}"];
                            }))
                    ])
                    ->action(function (Account $record, array $data): void {
                        try {
                            Artisan::call('redeem:silver-to-gold', [
                                'account-id' => $record->id,
                                'product-id' => $data['product_id']
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Silver redeemed to Gold successfully')
                                ->body(Artisan::output())
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Failed to redeem silver to gold')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->hidden()
                    ->icon('heroicon-o-arrow-path'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),


                    Tables\Actions\BulkAction::make('bulk_redeem_silver_to_gold')
                        ->label('Bulk Redeem Silver to Gold')
                        ->form([
                            Forms\Components\Select::make('product_id')
                                ->label('Product ID')
                                ->options(ProductToBuy::all()->mapWithKeys(function ($product) {
                                    return [$product->id => "{$product->product_name} - \${$product->buy_value}"];
                                }))
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {

                            $errors = [];

                            foreach ($records as $record) {
                                try {
                                    Artisan::call('redeem:silver-to-gold', [
                                        'account-id' => $record->id,
                                        'product-id' => $data['product_id']
                                    ]);
                                } catch (\Exception $e) {
                                    $errors[] = $e->getMessage();
                                }
                            }

                            if (count($errors) > 0) {
                                Notification::make()
                                    ->danger()
                                    ->title('Failed to redeem silver to gold for some accounts')
                                    ->body(implode('<br>', $errors))
                                    ->send();
                                return;
                            }

                            Notification::make()
                                ->success()
                                ->title('Silver redeemed to Gold for selected accounts successfully')
                                ->send();
                        }),
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
