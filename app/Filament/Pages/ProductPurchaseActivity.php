<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\ProductPurchaseActivityWidget;

class ProductPurchaseActivity extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Purchase Activity';
    protected static ?string $title = 'Product Purchase Activity';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.product-purchase-activity';

    public function getHeaderWidgets(): array
    {
        return [
            ProductPurchaseActivityWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    public function getWidgetData(): array
    {
        return [];
    }
}
