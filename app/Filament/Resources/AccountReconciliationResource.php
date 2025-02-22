<?php

namespace App\Filament\Resources;

use App\Filament\Exports\AccountReconciliationExporter;
use App\Filament\Resources\AccountReconciliationResource\Pages;
use App\Models\Account;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\AccountTopup;
use Filament\Notifications\Notification;

class AccountReconciliationResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Account Reconciliation';

    protected static ?string $modelLabel = 'Account Reconciliation';
    protected static ?int $navigationSort = 20;

    public static function table(Table $table): Table
    {
        return $table

            ->paginated([10,20,25,50,100, 250, 500, 1000, 2000, 5000, 'all'])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => route('export.account-reconciliation'))
                    ->openUrlInNewTab(),
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
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor')
                    ->label('Provider')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BooleanColumn::make('status')
                    ->label('Status')

                    ->getStateUsing(fn ($record): bool => $record->balance_difference == 0 ? true : false)
                    ->color(fn ($record): string => $record->balance_difference == 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('ballance_gold')
                    ->label('Gold Balance')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('topup_balance')
                    ->label('Topup Balance')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_balance')
                    ->label('Transaction Balance')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_difference')
                    ->label('Balance Difference')
                    ->money('USD')
                    ->sortable()
                    ->color(fn($state): string => $state < -0.1 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('codes_count')
                    ->label('Total Codes')
                    ->counts('codes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Total Transactions')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->counts('transactions')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_selected')
                    ->label('Export Selected')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($records) {
                        $ids = $records->pluck('id')->toArray();
                        return redirect()->route('export.account-reconciliation', ['ids' => implode(',', $ids)]);
                    }),
                Tables\Actions\BulkAction::make('reconcile')
                    ->label('Reconcile')
                    ->icon('heroicon-o-scale')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function ($records) {
                /*
                        foreach ($records as $account) {
                            $difference = $account->balance_difference;

                            if ($difference != 0) {
                                AccountTopup::create([
                                    'account_id' => $account->id,
                                    'topup_amount' => abs($difference),
                                    'topup_time' => now(),
                                ]);
                            }
                        }*/

                    })
            ])
            ->defaultSort('id', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountReconciliations::route('/'),
        ];
    }
}
