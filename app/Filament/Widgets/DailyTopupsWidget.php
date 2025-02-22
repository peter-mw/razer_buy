<?php

namespace App\Filament\Widgets;

use App\Models\AccountTopup;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class DailyTopupsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Daily Topups Summary';

    protected function getTableFiltersFormWidth(): string
    {

        return '4xl';
    }

    protected function getIdentifier(): string
    {
        return 'daily-topups-widget';
    }
    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->id ?? '';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AccountTopup::query()
                    ->select([
                        DB::raw('DATE(date) as date'),
                        DB::raw('COUNT(*) as total_topups'),
                        DB::raw('SUM(topup_amount) as total_amount'),
                        DB::raw('COUNT(DISTINCT account_id) as unique_accounts')
                    ])
                    ->groupBy('date')
            )
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')

                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_topups')
                    ->label('Total Topups')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unique_accounts')
                    ->label('Unique Accounts')
                    ->sortable(),
                Tables\Columns\ViewColumn::make('date')
                    ->label('Details')
                    ->view('filament.widgets.topup-sumapy-for-the-day')



                  /*  ->viewData(fn ($record) => [
                        'date' => \Carbon\Carbon::parse($record->date),
                        'total_topups' => $record->total_topups,
                        'total_amount' => $record->total_amount,
                        'unique_accounts' => $record->unique_accounts,
                    ])*/
                    ,
            ]);
    }
}
