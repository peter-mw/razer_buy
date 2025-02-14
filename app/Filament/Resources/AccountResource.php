<?php

namespace App\Filament\Resources;

use App\Filament\Exports;
use App\Filament\Imports;
use App\Filament\Resources\AccountResource\Pages;
use App\Filament\Resources\AccountResource\RelationManagers;
use App\Models\Account;
use App\Filament\Widgets\AccountCodesInlineChartWidget;
use App\Models\PurchaseOrders;
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

    protected static ?string $navigationIcon = 'heroicon-o-user';
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
                    ->native(true)
                    ->options([
                        'global' => 'Global',
                        'usa' => 'USA',
                    ])
                ,


                Forms\Components\TextInput::make('limit_amount_per_day')
                    ->numeric()
                    ->default(1000000)
                    ->step('0.01')
                    ->required(),


                Forms\Components\TextInput::make('password')

                ,
                Forms\Components\TextInput::make('otp_seed')

                ,


                Forms\Components\TextInput::make('vendor')
                    ->maxLength(255),
                Forms\Components\TextInput::make('email_password')
                ,

                Forms\Components\TextInput::make('service_code')
                    ->required()
                    ->numeric()
                    ->maxLength(255),

                Forms\Components\TextInput::make('client_id_login')
                    ->required()
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
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->defaultSort('ballance_gold', 'desc')
            ->actionsPosition(Tables\Enums\ActionsPosition::AfterCells)
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->importer(Imports\AccountImporter::class),
                Tables\Actions\ExportAction::make()
                    ->exporter(Exports\AccountExporter::class),
                Tables\Actions\Action::make('sync_all')
                    ->label('Sync All Balances')
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
                    }),
                Tables\Actions\Action::make('sync_all_codes')
                    ->label('Sync All Codes')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function () {
                        try {
                            $accounts = Account::where('is_active', true)->get();
                            foreach ($accounts as $account) {
                                dispatch(new \App\Jobs\FetchAccountCodesJob($account->id));
                            }
                            Notification::make()
                                ->success()
                                ->title('Code sync jobs dispatched for all active accounts')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Failed to dispatch code sync jobs')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('sync_all_data')
                    ->label('Sync All Data')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function () {
                        try {
                            dispatch(new \App\Jobs\SyncAllAccountData());
                            Notification::make()
                                ->success()
                                ->title('All sync jobs dispatched successfully')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Failed to dispatch sync jobs')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('sync_all_topups')
                    ->label('Sync All Topups')
                    ->icon('heroicon-o-credit-card')
                    ->action(function () {
                        try {
                            $accounts = Account::where('is_active', true)->get();
                            foreach ($accounts as $account) {
                                dispatch(new \App\Jobs\SyncAccountTopupsJob($account->id));
                            }
                            Notification::make()
                                ->success()
                                ->title('Topup sync jobs dispatched for all active accounts')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Failed to dispatch topup sync jobs')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
            ])
            ->columns([
                \LaraZeus\InlineChart\Tables\Columns\InlineChart::make('activity')
                    ->chart(AccountCodesInlineChartWidget::class)
                    ->maxWidth(200)
                    ->maxHeight(90)
                    ->description('Last 7 days activity')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->toggleable(),


                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_id_login')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('limit_amount_per_day')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->money()
                    ->default(100000)
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchases_last_24_hours')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Purchases Last 24 Hours')
                    ->getStateUsing(fn(Account $record) => $record->purchasesLast24Hours())
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_ballance_update_at')
                    ->label('Last Ballance Update')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_ballance_update_status')
                    ->label('Last Ballance Update Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'success' => 'success',
                        'error' => 'danger',
                        default => 'warning',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_topup_sync_at')
                    ->label('Last Topup Sync')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_topup_sync_status')
                    ->label('Last Topup Sync Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'success' => 'success',
                        'error' => 'danger',
                        default => 'warning',
                    })
                    ->searchable()
                    ->sortable()
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

                Tables\Filters\SelectFilter::make('account_type')
                    ->options([
                        'global' => 'Global',
                        'usa' => 'USA',
                    ])
                    ->label('Account Type'),

                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),
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
                Action::make('create_order')
                    ->label('Create Order')
                    ->icon('heroicon-o-shopping-cart')
                    ->hidden()
                    ->url(fn(Account $record): string => PurchaseOrderResource::getUrl('create', [
                        'account_id' => $record->id,
                        'account_type' => $record->account_type
                    ])
                    )
                    ->openUrlInNewTab(),
                Action::make('redeem_silver_to_gold')
                    ->label('Redeem Silver to Gold')
                    ->icon('heroicon-o-cash')
                    ->form([
                        Forms\Components\Select::make('product_id')
                            ->label('Product ID')
                            ->options(PurchaseOrders::all()->mapWithKeys(function ($product) {
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
                    Tables\Actions\ExportBulkAction::make()
                        ->exporter(Exports\AccountExporter::class),


                    Tables\Actions\BulkAction::make('bulk_redeem_silver_to_gold')
                        ->label('Bulk Redeem Silver to Gold')
                        ->form([
                            Forms\Components\Select::make('product_id')
                                ->label('Product ID')
                                ->options(PurchaseOrders::all()->mapWithKeys(function ($product) {
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
                    Tables\Actions\BulkAction::make('sync_all_data')
                        ->label('Sync All Data')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->action(function ($records): void {
                            try {
                                foreach ($records as $record) {
                                    dispatch(new \App\Jobs\SyncAllAccountData($record->id));
                                }
                                Notification::make()
                                    ->success()
                                    ->title('Sync jobs dispatched for selected accounts')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Failed to dispatch sync jobs')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                    Tables\Actions\BulkAction::make('sync_codes')
                        ->label('Sync Codes')
                        ->icon('heroicon-o-document-duplicate')
                        ->action(function ($records): void {
                            try {
                                foreach ($records as $record) {
                                    dispatch(new \App\Jobs\FetchAccountCodesJob($record->id));
                                }
                                Notification::make()
                                    ->success()
                                    ->title('Code sync jobs dispatched for selected accounts')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Failed to dispatch code sync jobs')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                    Tables\Actions\BulkAction::make('sync_topups')
                        ->label('Sync Topups')
                        ->icon('heroicon-o-credit-card')
                        ->action(function ($records): void {
                            try {
                                foreach ($records as $record) {
                                    dispatch(new \App\Jobs\SyncAccountTopupsJob($record->id));
                                }
                                Notification::make()
                                    ->success()
                                    ->title('Topup sync jobs dispatched for selected accounts')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Failed to dispatch topup sync jobs')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CodesRelationManager::class,
            RelationManagers\TransactionsRelationManager::class,
            RelationManagers\AccountTopupsRelationManager::class,
            RelationManagers\SystemLogsRelationManager::class,
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
