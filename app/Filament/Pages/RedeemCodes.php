<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Services\RazerService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class RedeemCodes extends Page
{
    protected static string $view = 'filament.pages.redeem-codes';

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationLabel = 'Redeem Codes';
    protected static ?string $title = 'Redeem Codes';
    protected static ?string $slug = 'redeem-codes';
    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {

        return $form
            ->schema([
                Select::make('account_id')
                    ->label('Account')
                    ->options(Account::query()->where('is_active', 1)->get()->mapWithKeys(function ($account) {
                        return [$account->id => $account->email . ' (Remaining 24h: $' . number_format($account->remainingBallance24Hours(), 2) . ') id: ' . $account->id];
                    }))
                    ->searchable()
                    ->required(),
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->placeholder('Enter code to redeem')
                    ->maxLength(255),
            ])
            ->statePath('data');
    }

    public function redeem(): void
    {
        $data = $this->form->getState();

        $account = Account::find($data['account_id']);
        if (!$account) {
            Notification::make()
                ->title('Account not found')
                ->danger()
                ->send();
            return;
        }

        try {
            $razerService = new RazerService($account);
            $result = $razerService->reloadAccount($data['code']);

            if ($result['status'] === 'success') {
                Notification::make()
                    ->title('Code redeemed successfully')
                    ->success()
                    ->send();

                // Reset the form
                $this->form->fill();
            } else {
                Notification::make()
                    ->title('Failed to redeem code')
                    ->body($result['message'] ?? 'Unknown error occurred')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getFormActions(): array
    {
        return [
            \Filament\Forms\Components\Actions\Action::make('redeem')
                ->label('Redeem Code')
                ->submit('redeem'),
        ];
    }
}
