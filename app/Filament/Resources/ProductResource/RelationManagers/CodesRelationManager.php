<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use App\Models\Code;
use App\Filament\Exports\CodeExporter;
use Illuminate\Database\Eloquent\Builder;

class CodesRelationManager extends RelationManager
{
    protected static string $relationship = 'codes';

    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serial_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account.name')
                    ->label('Account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order.product_name')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('buy_value')
                    ->money()
                    ->sortable(),
                TextColumn::make('buy_date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('buy_date', 'desc')
            ->headerActions([
                ExportAction::make()
                    ->label('Export to Excel')
                    ->exporter(CodeExporter::class)
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('product_id', $this->getOwnerRecord()->id))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Export Selected to Excel')
                        ->exporter(CodeExporter::class)
                        ->modifyQueryUsing(fn (Builder $query) => $query->where('product_id', $this->getOwnerRecord()->id))
                ]),
            ]);
    }
}
