<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AccountBalancesWidget;
use Filament\Pages\Page;


class Balances extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Balances';

    protected static ?string $title = 'Account Balances';
    protected static string $view = 'filament.pages.balances';

    protected static ?int $navigationSort = 10;

    public function getHeaderWidgetsColumns(): int | string | array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AccountBalancesWidget::class,
        ];
    }
}
