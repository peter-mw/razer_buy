<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DailyTopupsWidget;
use Filament\Pages\Page;

class DailyTopupsSummary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $title = 'Daily Topups Summary';
    protected static string $view = 'filament.pages.daily-topups-summary';

    protected static ?string $navigationLabel = 'Daily Topups Summary';

 //   protected static ?string $slug = 'daily-topups-summary';

    protected static ?int $navigationSort = 9;


    public function getHeaderWidgets(): array
    {
        return [
            DailyTopupsWidget::class,
        ];
    }


}
