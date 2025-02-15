<?php

namespace App\Filament\Resources\AccountTopupResource\Pages;

use App\Filament\Resources\AccountTopupResource;
use Filament\Resources\Pages\Page;
use App\Models\AccountTopup;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DailyTopups extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = AccountTopupResource::class;

    protected static string $view = 'filament.pages.table';

    protected static ?string $title = 'Daily Topups Summary';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AccountTopup::query()
                    ->select([
                        DB::raw('DATE(topup_time) as date'),
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
            ]);
    }
}
