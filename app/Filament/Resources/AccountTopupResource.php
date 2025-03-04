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
use Filament\Notifications\Notification;

class AccountTopupResource extends Resource
{
    protected static ?string $model = AccountTopup::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?int $navigationSort = 2;


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
                        fn($query) => $query->select(['id', 'name', 'account_type'])
                    )
                    ->getOptionLabelFromRecordUsing(fn($record) => "[{$record->id}] {$record->name} ({$record->account_type})")
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
                Forms\Components\DatePicker::make('date')
                    ->native(true)
                    ->default(now())
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
            ->paginated([10, 20, 25, 50, 100, 250, 500, 1000, 2000, 5000, 'all'])
            ->defaultSort('topup_time', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('account.id')
                    ->label('Account ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Account')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('account.vendor')
                    ->label('Vendor')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('topup_amount')
                    ->money('USD')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('USD')
                    ]),
                Tables\Columns\TextColumn::make('topup_time')
                    ->date(format: 'Y-m-d H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('topup_time')
                    ->label('Topup Date')
                    ->date('Y-m-d H:i:s')
                    ->sortable()
                    ->color(function ($record) {
                        // Get the first record ID for this date
                        $firstRecordId = AccountTopup::whereDate('topup_time', $record->topup_time->format('Y-m-d'))
                            ->orderBy('topup_time', 'desc')
                            ->value('id');

                        // Return success color if this is the first record for the date
                        return $record->id === $firstRecordId ? 'success' : null;
                    }),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('transaction_ref')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                /*Tables\Actions\ImportAction::make()
                    ->importer(Imports\AccountTopupImporter::class),*/


                Tables\Actions\Action::make('custom_export')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date')
                            ->default(now()->subDay())
                            ->required(),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('vendor')
                            ->label('Vendor')
                            ->options(function (): array {
                                return \App\Models\Account::query()
                                    ->select(['vendor'])
                                    ->whereNotNull('vendor')
                                    ->distinct()
                                    ->get()
                                    ->pluck('vendor', 'vendor')
                                    ->toArray();
                            })
                            ->searchable()
                        ,


                        Forms\Components\Select::make('account_id')
                            ->label('Account')
                            ->options(function (): array {
                                return \App\Models\Account::query()
                                    ->select(['id', 'name', 'account_type'])
                                    ->withSum('accountTopups', 'topup_amount')
                                    ->get()
                                    ->mapWithKeys(function ($account) {
                                        return [
                                            $account->id => "[{$account->id}] {$account->name} ({$account->account_type}) - Total Topup: $" .
                                                number_format($account->account_topups_sum_topup_amount ?? 0, 2)
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->searchable(),
                    ])
                    ->action(function (array $data): void {
                        $params = http_build_query([
                            'from_date' => $data['from_date'],
                            'to_date' => $data['to_date'],
                            'account_id' => $data['account_id'],
                            'vendor' => $data['vendor'] ?? null,
                        ]);
                        $url = route('export.account-topups') . '?' . $params;
                        redirect()->away($url);
                    }),

                Tables\Actions\Action::make('sync_all_topups')
                    ->label('Sync All Topups')
                    ->icon('heroicon-o-credit-card')
                    ->action(function () {
                        try {

                            $accounts = \App\Models\Account::where('is_active', true)->get();
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
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor')
                    ->label('Vendor')
                    ->options(function (): array {
                        return \App\Models\Account::query()
                            ->select(['vendor'])
                            ->whereNotNull('vendor')
                            ->distinct()
                            ->get()
                            ->pluck('vendor', 'vendor')
                            ->toArray();
                    })
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, string $value): Builder => $query->whereHas(
                                'account',
                                fn(Builder $query): Builder => $query->where('vendor', $value)
                            )
                        );
                    }),

                Tables\Filters\SelectFilter::make('account')
                    ->searchable()
                    ->preload()
                    ->relationship(
                        'account',
                        'id',
                        fn($query) => $query->select(['id', 'name', 'account_type'])
                            ->withSum('accountTopups', 'topup_amount')
                    )
                    ->getOptionLabelFromRecordUsing(fn($record) => "[{$record->id}] {$record->name} ({$record->account_type}) - Total Topup: $" . number_format($record->account_topups_sum_topup_amount ?? 0, 2)),
                Tables\Filters\SelectFilter::make('date_range')
                    ->options([
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        'last_7_days' => 'Last 7 Days',
                        'last_30_days' => 'Last 30 Days',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'today' => $query->whereDate('topup_time', now()),
                            'yesterday' => $query->whereDate('topup_time', now()->subDay()),
                            'last_7_days' => $query->whereDate('topup_time', '>=', now()->subDays(7)),
                            'last_30_days' => $query->whereDate('topup_time', '>=', now()->subDays(30)),
                            'this_month' => $query->whereMonth('topup_time', now()->month)
                                ->whereYear('topup_time', now()->year),
                            'last_month' => $query->whereMonth('topup_time', now()->subMonth()->month)
                                ->whereYear('topup_time', now()->subMonth()->year),
                            default => $query
                        };
                    }),
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

                    Tables\Actions\BulkAction::make('sync_topups')
                        ->label('Sync Topups')
                        ->icon('heroicon-o-credit-card')
                        ->action(function ($records): void {
                            try {
                                $accountIds = $records->pluck('account_id')->unique();
                                foreach ($accountIds as $accountId) {
                                    dispatch(new \App\Jobs\SyncAccountTopupsJob($accountId));
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
}
