<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\Account;
use App\Models\Product;
use App\Models\PurchaseOrders;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Forms\Components\OrderDetails;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms;

class CreateMultipleOrders extends Page
{
    use InteractsWithForms;

    protected static string $resource = PurchaseOrderResource::class;

    protected static string $view = 'filament.resources.purchase-order-resource.pages.create-multiple-orders';

    public ?array $data = [];

    public function refreshOrderDetails(): void
    {
        $this->data['order_details'] = $this->getOrderDetails();
        $this->dispatch('order-details-updated');
    }

    public function mount(): void
    {
        $this->form->fill([
            'data.account_type' => null,
            'data.product_id' => null,
            'data.selected_accounts' => [],
            'data.quantities' => [],
            'data.execute_immediately' => false,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Account Type')
                ->schema([
                    Select::make('data.account_type')
                        ->label('Select Account Type')
                        ->options([
                            'global' => 'Global',
                            'usa' => 'USA',
                        ])
                        ->required()
                        ->reactive()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            $this->data['account_type'] = $state;
                            $this->data['product_id'] = null;
                            $this->data['selected_accounts'] = [];
                            $this->refreshOrderDetails();
                        }),
                ]),

            Section::make('Product')
                ->schema([
                    Select::make('data.product_id')
                        ->label('Select Product')
                        ->options(fn() => Product::where('account_type', $this->data['account_type'] ?? null)
                            ->get()
                            ->mapWithKeys(function ($product) {
                                return [
                                    $product->id => "{$product->product_name} - {$product->product_edition} (Buy: {$product->product_buy_value} Gold, Face: {$product->product_face_value})"
                                ];
                            }))
                        ->required()
                        ->reactive()
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            if ($state) {
                                $product = Product::find($state);
                                $this->data['product'] = $product;
                                $this->data['product_id'] = $state;
                                $this->data['product_name'] = $product->product_name;
                                $this->data['product_edition'] = $product->product_edition;
                                $this->data['buy_value'] = $product->product_buy_value;
                                $this->data['product_face_value'] = $product->product_face_value;
                            }
                            $this->refreshOrderDetails();
                        }),
                ]),

            Section::make('Select Accounts')
                ->schema([
                    Actions::make([
                        Actions\Action::make('setMaxQuantities')
                        ->label('Set All to Max')
                        ->icon('heroicon-m-arrow-up-circle')
                        ->visible(fn() => collect($this->data['selected_accounts'] ?? [])->contains(true))
                        ->action(function () {
                            $accounts = Account::where('is_active', true)
                                ->where('account_type', $this->data['account_type'] ?? null)
                                ->where('is_active', true)
                                ->get();

                            foreach ($accounts as $account) {
                                if (isset($this->data['selected_accounts'][$account->id]) && 
                                    $this->data['selected_accounts'][$account->id] && 
                                    isset($this->data['product'])) {
                                    $maxQuantity = intval($account->ballance_gold / $this->data['product']->product_buy_value);
                                    $this->data['quantities'][$account->id] = $maxQuantity;
                                }
                            }
                            $this->refreshOrderDetails();
                            $this->form->fill([
                                'data.quantities' => $this->data['quantities']
                            ]);
                        }),
                    ])->columnSpanFull(),
                    Grid::make()
                        ->schema(fn() => $this->getAccountsWithQuantityInputs())
                        ->columns(1),
                ]),
            Section::make('Order Summary')
                ->schema([
                    OrderDetails::make('data.order_details')
                        ->columnSpanFull()
                        ->live()
                        ->reactive()
                        ->afterStateHydrated(function () {
                            $this->refreshOrderDetails();
                        })
                        ->dehydrated(false),

                    Checkbox::make('data.execute_immediately')
                        ->label('Create and Execute Orders Immediately')
                        ->default(false)
                        ->live(),
                ]),
        ]);
    }

    #[Computed]
    protected function getAccountsWithQuantityInputs(): array
    {
        $accounts = Account::where('is_active', true)
            ->where('account_type', $this->data['account_type'] ?? null)
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            return [];
        }

        $schema = [];
        foreach ($accounts as $account) {
            $maxQuantity = 0;

            if (intval($account->ballance_gold == 0)) {
                continue;
            }

            if (isset($this->data['product'])) {
                $maxQuantity = intval($account->ballance_gold / $this->data['product']->product_buy_value);
            }


            if ($maxQuantity == 0) {
                continue;
            }

            $schema[] = Grid::make([
                'default' => 2,
            ])
                ->schema([
                    Checkbox::make("data.selected_accounts.{$account->id}")
                        ->label("{$account->name} (Balance: {$account->ballance_gold} Gold, Max: {$maxQuantity})")
                        ->live()
                        ->afterStateUpdated(function ($state) use ($account) {
                            // Reset quantity when unchecking
                            if (!$state) {
                                $this->data['quantities'][$account->id] = 0;
                            }
                            $this->refreshOrderDetails();
                        }),
                    TextInput::make("data.quantities.{$account->id}")
                        ->label('Quantity')
                        ->numeric()
                        ->default($maxQuantity)
                        ->minValue(0)
                        ->maxValue($maxQuantity)
                        ->visible(fn() => $this->data['selected_accounts'][$account->id] ?? false)
                        ->required()
                        ->live(debounce: 1500)
                        ->beforeStateDehydrated(function ($state) {
                            if (!isset($state)) {
                                return 0;
                            }
                            return $state;
                        })
                        ->afterStateUpdated(function ($state) {
                            $this->refreshOrderDetails();
                        })
                        ->reactive(),
                ])
                ->columnSpan('full');
        }

        return $schema;
    }

    protected function getOrderDetails(): ?string
    {
        if (!isset($this->data['product'])) {
            return null;
        }

        // Check if any accounts are selected
        $hasSelectedAccounts = collect($this->data['selected_accounts'] ?? [])
            ->contains(fn($selected) => $selected === true);

        if (!$hasSelectedAccounts) {
            return null;
        }

        $product = $this->data['product'];
        $accounts = [];
        $totalQuantity = 0;
        $totalCost = 0;
        $warnings = [];

        // Get selected accounts from individual checkboxes
        $selectedAccounts = collect($this->data['selected_accounts'] ?? [])
            ->filter(fn($selected) => $selected)
            ->keys();

        foreach ($selectedAccounts as $accountId) {
            $account = Account::find($accountId);
            if (!$account) continue;

            $quantity = $this->data['quantities'][$accountId] ?? 0;
            if ($quantity > 0) {
                $cost = $quantity * $product->product_buy_value;
                if ($cost > $account->ballance_gold) {
                    $warnings[] = "Insufficient balance for {$account->name}. Needs additional " .
                        ($cost - $account->ballance_gold) . " Gold";
                }

                $accounts[] = [
                    'id' => $account->id,
                    'name' => $account->name,
                    'quantity' => $quantity,
                    'balance' => $account->ballance_gold,
                ];

                $totalQuantity += $quantity;
                $totalCost += $cost;
            }
        }

        $orderDetails = [
            'product' => [
                'name' => $product->product_name,
                'buy_value' => $product->product_buy_value,
                'face_value' => $product->product_face_value,
            ],
            'accounts' => $accounts,
            'total' => [
                'quantity' => $totalQuantity,
                'cost' => $totalCost,
            ],
            'warnings' => $warnings,
        ];

        return json_encode($orderDetails);
    }

    public function createAndExecuteOrders()
    {
        $this->data['execute_immediately'] = true;
        $this->createOrders();
    }

    public function createOrders()
    {
        $this->form->validate();

        try {
            $data = $this->form->getState();
            $product = Product::findOrFail($data['data']['product_id']);
            $hasValidQuantity = false;

            // Convert checkbox format to array of selected account IDs
            $selectedAccounts = collect($data['data']['selected_accounts'] ?? [])
                ->filter(fn($selected) => $selected)
                ->keys()
                ->toArray();

            foreach ($selectedAccounts as $accountId) {
                $quantity = $data['data']['quantities'][$accountId] ?? 0;

                if ($quantity > 0) {
                    $hasValidQuantity = true;

                    // Validate against account balance
                    $account = Account::findOrFail($accountId);
                    $maxQuantity = floor($account->ballance_gold / $product->product_buy_value);

                    if ($quantity > $maxQuantity) {
                        Notification::make()
                            ->title("Quantity for {$account->name} exceeds maximum allowed ({$maxQuantity})")
                            ->danger()
                            ->send();
                        return;
                    }
                }
            }

            if (!$hasValidQuantity) {
                Notification::make()
                    ->title('Please enter a quantity greater than 0 for at least one account')
                    ->danger()
                    ->send();
                return;
            }

            $this->processOrders($data);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error validating form')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function processOrders(array $data): void
    {
        try {
            $data = $this->form->getState();

            /** @var Product $product */
            $product = Product::findOrFail($data['data']['product_id']);
            $selectedAccounts = collect($data['data']['selected_accounts'])
                ->filter(fn($selected) => $selected)
                ->keys()
                ->toArray();
            $quantities = $data['data']['quantities'];
            $executeImmediately = $data['data']['execute_immediately'] ?? false;

            foreach ($selectedAccounts as $accountId) {
                $quantity = $quantities[$accountId] ?? 0;

                if ($quantity > 0) {
                    /** @var PurchaseOrders $order */
                    $order = PurchaseOrders::create([
                        'product_id' => $product->id,
                        'product_name' => $product->product_name,
                        'product_edition' => $product->product_edition,
                        'account_type' => $data['data']['account_type'],
                        'quantity' => $quantity,
                        'buy_value' => $product->product_buy_value,
                        'product_face_value' => $product->product_face_value,
                        'account_id' => $accountId,
                        'order_status' => $executeImmediately ? 'processing' : 'pending',
                    ]);

                    if ($executeImmediately) {
                        \App\Jobs\ProcessBuyJob::dispatch($order->id, $quantity);
                    }
                }
            }

            $status = $executeImmediately ? 'created and being processed' : 'created in pending state';

            Notification::make()
                ->title('Orders have been ' . $status)
                ->success()
                ->send();

            $this->redirect(PurchaseOrderResource::getUrl('index'));

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error creating orders')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
