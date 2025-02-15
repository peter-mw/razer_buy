<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\Transaction;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class TransactionStatsWidget extends Widget
{
    protected static ?string $pollingInterval = '10s';

    protected int|string|array $columnSpan = 'full';

    protected array $accountTypes = ['global', 'usa'];

    public function render(): View
    {
        $stats = [];

        foreach ($this->accountTypes as $type) {
            $stats[$type] = Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
                ->where('accounts.account_type', $type)
                ->where('transactions.created_at', '>=', now()->subHours(24))
                ->sum('transactions.amount');
        }

        return view('filament.widgets.transaction-stats', [
            'stats' => $stats,
        ]);
    }
}
