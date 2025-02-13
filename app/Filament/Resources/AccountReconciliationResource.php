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
            ->paginated([10, 25, 50, 100, 1000, 'all'])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->exporter(AccountReconciliationExporter::class),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
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
                    ->color(fn(string $state): string => $state < -0.1 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('codes_count')
                    ->label('Total Codes')
                    ->counts('codes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Total Transactions')
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
                Tables\Actions\ExportBulkAction::make()
                    ->exporter(AccountReconciliationExporter::class),
                Tables\Actions\BulkAction::make('reconcile')
                    ->label('Reconcile')
                    ->icon('heroicon-o-scale')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function ($records) {
                        foreach ($records as $account) {
                            $difference = $account->balance_difference;

                            if ($difference != 0) {
                                AccountTopup::create([
                                    'account_id' => $account->id,
                                    'topup_amount' => abs($difference),
                                    'topup_time' => now(),
                                ]);
                            }
                        }
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
