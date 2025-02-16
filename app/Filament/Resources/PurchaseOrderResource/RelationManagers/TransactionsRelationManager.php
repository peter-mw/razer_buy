<?php

namespace App\Filament\Resources\PurchaseOrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use App\Models\Transaction;
use App\Filament\Exports\TransactionExporter;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return $table
            ->paginated([10,20,25,50,100, 250, 500, 1000, 2000, 5000, 'all'])
            ->columns([
                TextColumn::make('transaction_id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account.email')
                    ->label('Account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->money()
                    ->sortable(),
                TextColumn::make('transaction_date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->headerActions([
                ExportAction::make()
                    ->label('Export to Excel')
                    ->exporter(TransactionExporter::class)
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Export Selected to Excel')
                        ->exporter(TransactionExporter::class)
                ]),
            ]);
    }
}
