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
            'data.product_ids' => [],
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
                    Actions::make([
                        Actions\Action::make('createOptimalOrder')
                            ->label('Create Optimal Order')
                            ->icon('heroicon-m-sparkles')
                            ->action(function (Forms\Set $set) {
                                // Get account type products
                                $products = Product::whereJsonContains('account_type', $this->data['account_type'] ?? null)
                                    ->orderByDesc('product_buy_value')
                                    ->get();
                                
                                if ($products->isEmpty()) {
                                    Notification::make()
                                        ->title('No products available for this account type')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Set product IDs
                                $productIds = $products->pluck('id')->toArray();
                                $set('data.product_ids', $productIds);
                                
                                // Update products data
                                $this->data['products'] = [];
                                foreach ($products as $product) {
                                    $this->data['products'][$product->id] = [
                                        'id' => $product->id,
                                        'name' => $product->product_name,
                                        'edition' => $product->product_edition,
                                        'buy_value' => $product->product_buy_value,
                                        'face_value' => $product->product_face_value,
                                    ];
                                }

                                // Get and select all active accounts with balance
                                $accounts = Account::where('is_active', true)
                                    ->when($this->data['account_type'], function ($query, $accountType) {
                                        return $query->where('account_type', $accountType);
                                    })
                                    ->where('ballance_gold', '>', 0)
                                    ->orderByDesc('ballance_gold')
                                    ->get();

                                if ($accounts->isEmpty()) {
                                    Notification::make()
                                        ->title('No accounts with balance found')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Select all accounts and initialize quantities
                                $this->data['selected_accounts'] = [];
                                $this->data['quantities'] = [];
                                
                                foreach ($accounts as $account) {
                                    $this->data['selected_accounts'][$account->id] = true;
                                    $remainingBalance = $account->ballance_gold;
                                    
                                    // Initialize quantities to 0
                                    if (!isset($this->data['quantities'][$account->id])) {
                                        $this->data['quantities'][$account->id] = [];
                                    }
                                    foreach ($products as $product) {
                                        $this->data['quantities'][$account->id][$product->id] = 0;
                                    }
                                    
                                    // Distribute balance optimally
                                    foreach ($products as $product) {
                                        if ($remainingBalance >= $product->product_buy_value) {
                                            $maxQuantity = intval($remainingBalance / $product->product_buy_value);
                                            $this->data['quantities'][$account->id][$product->id] = $maxQuantity;
                                            $remainingBalance -= ($maxQuantity * $product->product_buy_value);
                                        }
                                    }
                                }

                                // Update form state
                                $set('data.selected_accounts', $this->data['selected_accounts']);
                                $set('data.quantities', $this->data['quantities']);
                                
                                $this->refreshOrderDetails();

                                Notification::make()
                                    ->title('Orders optimized for ' . count($accounts) . ' accounts')
                                    ->success()
                                    ->send();
                            })
                            ->visible(fn() => !empty($this->data['account_type'])),
                    ])->columnSpanFull(),
                    Select::make('data.account_type')
                        ->label('Select Account Type')
                        ->options(fn() => \App\Models\AccountType::where('is_active', true)
                            ->get()
                            ->pluck('name', 'code'))
                        ->required()
                        ->reactive()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            $this->data['account_type'] = $state;
                            $this->data['product_ids'] = [];
                            $this->data['selected_accounts'] = [];
                            $this->refreshOrderDetails();
                        }),
                ]),

            Section::make('Products')
                ->schema([
                    CheckboxList::make('data.product_ids')
                        ->label('Select Products')
                        ->options(fn() => Product::whereJsonContains('account_type', $this->data['account_type'] ?? null)
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
                            $this->data['products'] = [];
                            if (!empty($state)) {
                                $products = Product::whereIn('id', $state)->get();
                                foreach ($products as $product) {
                                    $this->data['products'][$product->id] = [
                                        'id' => $product->id,
                                        'name' => $product->product_name,
                                        'edition' => $product->product_edition,
                                        'buy_value' => $product->product_buy_value,
                                        'face_value' => $product->product_face_value,
                                    ];
                                }
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
                                ->when($this->data['account_type'], function ($query, $accountType) {
                                    return $query->where('account_type', $accountType);
                                })
                                ->orderByDesc('ballance_gold')
                                ->get();

                            foreach ($accounts as $account) {
                                if (isset($this->data['selected_accounts'][$account->id]) &&
                                    $this->data['selected_accounts'][$account->id] &&
                                    !empty($this->data['products'])) {
                                    
                                    // Sort products by buy value in descending order to prioritize more expensive products
                                    $sortedProducts = collect($this->data['products'])
                                        ->sortByDesc('buy_value')
                                        ->all();

                                    $remainingBalance = $account->ballance_gold;
                                    if (!isset($this->data['quantities'][$account->id])) {
                                        $this->data['quantities'][$account->id] = [];
                                    }

                                    // Initialize all quantities to 0
                                    foreach ($sortedProducts as $productId => $product) {
                                        $this->data['quantities'][$account->id][$productId] = 0;
                                    }

                                    // Distribute balance optimally
                                    foreach ($sortedProducts as $productId => $product) {
                                        if ($remainingBalance >= $product['buy_value']) {
                                            $maxQuantity = intval($remainingBalance / $product['buy_value']);
                                            $this->data['quantities'][$account->id][$productId] = $maxQuantity;
                                            $remainingBalance -= ($maxQuantity * $product['buy_value']);
                                        }
                                    }
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
            ->when($this->data['account_type'], function ($query, $accountType) {
                return $query->where('account_type', $accountType);
            })
            ->orderByDesc('ballance_gold')
            ->get();

        if ($accounts->isEmpty() || empty($this->data['products'])) {
            return [];
        }

        $schema = [];
        foreach ($accounts as $account) {
            if (intval($account->ballance_gold) == 0) {
                continue;
            }

            $hasValidProduct = false;
            foreach ($this->data['products'] as $product) {
                if (intval($account->ballance_gold / $product['buy_value']) > 0) {
                    $hasValidProduct = true;
                    break;
                }
            }

            if (!$hasValidProduct) {
                continue;
            }

            $schema[] = Section::make()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Checkbox::make("data.selected_accounts.{$account->id}")
                                ->label("{$account->name} (Balance: {$account->ballance_gold} Gold)")
                                ->live()
                                ->afterStateUpdated(function ($state) use ($account) {
                                    if (!$state) {
                                        $this->data['quantities'][$account->id] = [];
                                    }
                                    $this->refreshOrderDetails();
                                })
                                ->columnSpan(2),
                            ...collect($this->data['products'])->map(function ($product, $productId) use ($account) {
                                $maxQuantity = intval($account->ballance_gold / $product['buy_value']);
                                return TextInput::make("data.quantities.{$account->id}.{$productId}")
                                    ->label("{$product['name']} ({$product['buy_value']} Gold) (Max: {$maxQuantity})")
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue($maxQuantity)
                                    ->visible(fn() => $this->data['selected_accounts'][$account->id] ?? false)
                                    ->required()
                                    ->live(debounce: 1500)
                                    ->beforeStateDehydrated(function ($state) {
                                        return $state ?? 0;
                                    })
                                    ->afterStateUpdated(function ($state) {
                                        $this->refreshOrderDetails();
                                    })
                                    ->reactive()
                                    ->inlineLabel();
                            })->toArray(),
                        ])
                ])
                ->columnSpan('full')
                ->compact();
        }

        return $schema;
    }

    protected function getOrderDetails(): ?string
    {
        if (empty($this->data['products'])) {
            return null;
        }

        // Check if any accounts are selected
        $hasSelectedAccounts = collect($this->data['selected_accounts'] ?? [])
            ->contains(fn($selected) => $selected === true);

        if (!$hasSelectedAccounts) {
            return null;
        }

        $ordersByProduct = [];
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

            foreach ($this->data['products'] as $productId => $product) {
                $quantity = $this->data['quantities'][$accountId][$productId] ?? 0;
                if ($quantity > 0) {
                    $cost = $quantity * $product['buy_value'];
                    if ($cost > $account->ballance_gold) {
                        $warnings[] = "Insufficient balance for {$account->name} to buy {$product['name']}. Needs additional " .
                            ($cost - $account->ballance_gold) . " Gold";
                    }

                    if (!isset($ordersByProduct[$productId])) {
                        $ordersByProduct[$productId] = [
                            'product' => $product,
                            'accounts' => [],
                            'total_quantity' => 0,
                            'total_cost' => 0,
                        ];
                    }

                    $ordersByProduct[$productId]['accounts'][] = [
                        'id' => $account->id,
                        'name' => $account->name,
                        'quantity' => $quantity,
                        'balance' => $account->ballance_gold,
                    ];

                    $ordersByProduct[$productId]['total_quantity'] += $quantity;
                    $ordersByProduct[$productId]['total_cost'] += $cost;
                    $totalQuantity += $quantity;
                    $totalCost += $cost;
                }
            }
        }

        $orderDetails = [
            'products' => array_values($ordersByProduct),
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
            $products = Product::whereIn('id', $data['data']['product_ids'])->get();
            $hasValidQuantity = false;

            // Convert checkbox format to array of selected account IDs
            $selectedAccounts = collect($data['data']['selected_accounts'] ?? [])
                ->filter(fn($selected) => $selected)
                ->keys()
                ->toArray();

            foreach ($selectedAccounts as $accountId) {
                foreach ($products as $product) {
                    $quantity = $data['data']['quantities'][$accountId][$product->id] ?? 0;

                    if ($quantity > 0) {
                        $hasValidQuantity = true;

                        // Validate against account balance
                        $account = Account::findOrFail($accountId);
                        $maxQuantity = floor($account->ballance_gold / $product->product_buy_value);

                        if ($quantity > $maxQuantity) {
                            Notification::make()
                                ->title("Quantity for {$account->name} on {$product->product_name} exceeds maximum allowed ({$maxQuantity})")
                                ->danger()
                                ->send();
                            return;
                        }

                        // Validate total cost doesn't exceed balance
                        $totalCost = 0;
                        foreach ($products as $p) {
                            $q = $data['data']['quantities'][$accountId][$p->id] ?? 0;
                            $totalCost += ($q * $p->product_buy_value);
                        }
                        
                        if ($totalCost > $account->ballance_gold) {
                            Notification::make()
                                ->title("Total cost for {$account->name} ({$totalCost} Gold) exceeds available balance ({$account->ballance_gold} Gold)")
                                ->danger()
                                ->send();
                            return;
                        }
                    }
                }
            }

            if (!$hasValidQuantity) {
                Notification::make()
                    ->title('Please enter a quantity greater than 0 for at least one account and product')
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
            $products = Product::whereIn('id', $data['data']['product_ids'])->get();
            $selectedAccounts = collect($data['data']['selected_accounts'])
                ->filter(fn($selected) => $selected)
                ->keys()
                ->toArray();
            $quantities = $data['data']['quantities'];
            $executeImmediately = $data['data']['execute_immediately'] ?? false;

            foreach ($selectedAccounts as $accountId) {
                foreach ($products as $product) {
                    $quantity = $quantities[$accountId][$product->id] ?? 0;

                    if ($quantity > 0) {
                        // Get region-specific product name if available
                        $productName = $product->product_name;
                        $accountType = $data['data']['account_type'];
                        
                        if ($accountType) {
                            // First try to get region-specific name
                            if (!empty($product->product_names)) {
                                $names = collect($product->product_names);
                                $regionName = $names->firstWhere('account_type', $accountType);
                                if ($regionName && isset($regionName['name'])) {
                                    $productName = $regionName['name'];
                                }
                            }
                            // Fallback to region-specific slug if name not found
                            elseif (!empty($product->product_slugs)) {
                                $slugs = collect($product->product_slugs);
                                $regionSlug = $slugs->firstWhere('account_type', $accountType);
                                if ($regionSlug && isset($regionSlug['slug'])) {
                                    $productName = $regionSlug['slug'];
                                }
                            } else {
                                $productName = $product->product_slug ?? $product->product_name;
                            }
                        }

                        /** @var PurchaseOrders $order */
                        $order = PurchaseOrders::create([
                            'product_id' => $product->id,
                            'product_name' => $productName,
                            'product_edition' => $product->product_edition,
                            'account_type' => $accountType,
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
