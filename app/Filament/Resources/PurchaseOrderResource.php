<?php

namespace App\Filament\Resources;

use App\Filament\Exports\CodeExporter;
use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrders;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Account;
use App\Models\Product;
use Filament\Widgets\Widget;
use App\Filament\Widgets\AccountBalancesWidget;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Jobs\ProcessBuyJob;
use Illuminate\Validation\ValidationException;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrders::class;
    protected static ?string $label = 'Purchase order';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 3;

    private static function validateBalance(Forms\Get $get, Forms\Set $set): void
    {
        $accountId = $get('account_id');
        $quantity = $get('quantity');
        $buyValue = $get('buy_value');

        if (!$accountId || !$quantity || !$buyValue) {
            $set('balance_check', null);
            return;
        }

        $account = Account::find($accountId);
        if (!$account) {
            $set('balance_check', null);
            return;
        }

        $totalCost = $quantity * $buyValue;
        $hasSufficientBalance = $account->ballance_gold >= $totalCost;

        $balanceInfo = [
            'account_balance' => $account->ballance_gold,
            'total_cost' => $totalCost,
            'quantity' => $quantity,
            'buy_value' => $buyValue,
            'has_sufficient_balance' => $hasSufficientBalance
        ];

        $set('balance_check', json_encode($balanceInfo));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'product_name')
                    ->label('Product')
                    ->required()
                    ->live()
                    ->reactive()
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->product_name} - {$record->product_edition} - \${$record->product_buy_value}")
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            $product = Product::find($state);

                            if ($product) {
                                $set('product_name', $product->product_slug);
                                $set('product_edition', $product->product_edition);
                                $set('buy_value', $product->product_buy_value);
                                $set('product_face_value', $product->product_face_value);
                                $set('account_type', $product->account_type);

                                // Validate after product change
                                static::validateBalance($get, $set);
                            }
                        }
                    }),
                Forms\Components\Hidden::make('product_name')
                    ->label('Product Name (slug)'),
                Forms\Components\Hidden::make('product_edition'),
                Forms\Components\Select::make('account_type')
                    ->required()
                    ->options([
                        'global' => 'Global',
                        'usa' => 'USA',
                    ])
                    ->default('standard'),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->reactive()
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        // Validate after quantity change
                        static::validateBalance($get, $set);
                    }),
                Forms\Components\Hidden::make('buy_value'),
                Forms\Components\Hidden::make('product_face_value'),
                Forms\Components\Select::make('account_id')
                    ->relationship(
                        'account',
                        'name',
                        function ($query, Forms\Get $get) {
                            $query->orderByDesc('ballance_gold');

                            $accountType = $get('account_type');
                            if ($accountType) {
                                $query->where('account_type', $accountType);
                            }

                            return $query;
                        }
                    )
                    ->label('Account')
                    ->searchable()
                    ->reactive()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} - Balance: \${$record->ballance_gold}")
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        // Validate after account change
                        static::validateBalance($get, $set);
                    }),
                Forms\Components\Select::make('order_status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed'
                    ])
                    ->default('pending')
                    ->required(),

                Forms\Components\Hidden::make('balance_check'),

                Forms\Components\Section::make('Balance Information')
                    ->schema([
                        Forms\Components\ViewField::make('balance_check')
                            ->view('filament.forms.components.balance-info')
                            ->columnSpanFull()
                    ])
                    ->columns(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->actionsPosition(Tables\Enums\ActionsPosition::BeforeColumns)
            ->columns([
                Tables\Columns\TextColumn::make('id')->toggleable(),
                Tables\Columns\TextColumn::make('product_id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_edition')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'global' => 'warning',
                        'usa' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('To buy')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('codes_count')
                    ->label('Codes')
                    ->counts('codes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('buy_value')
                    ->money()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_face_value')
                    ->money()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\Action::make('buy_all_products')
                    ->label('Buy All Products')
                    ->icon('heroicon-o-shopping-cart')
                    ->action(function () {
                        $products = PurchaseOrders::where('order_status', '!=', 'completed')->get();
                        if ($products->isEmpty()) {
                            Notification::make()
                                ->title('No pending orders to process')
                                ->warning()
                                ->send();
                            return;
                        }

                        $output = '';
                        foreach ($products as $product) {
                            $product->update(['order_status' => 'processing']);

                            ProcessBuyJob::dispatch($product->id, $product->quantity);
                        }

                        Notification::make()
                            ->title('All products processed')
                            ->body($output)
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('processBuy')
                    ->label('Process Buy')
                    ->icon('heroicon-o-shopping-cart')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->default(fn(PurchaseOrders $record) => $record->quantity)
                            ->minValue(1)
                            ->maxValue(fn(PurchaseOrders $record) => $record->quantity)
                    ])
                    ->action(function (PurchaseOrders $record, array $data) {
                        if ($record->order_status === 'completed') {
                            Notification::make()
                                ->title('Order already completed')
                                ->warning()
                                ->send();
                            return;
                        }

                        $record->update(['order_status' => 'processing']);

                        ProcessBuyJob::dispatch($record->id, $data['quantity']);

                        Notification::make()
                            ->title('Buy process started')
                            ->body('The purchase order is being processed in the background')
                            ->success()
                            ->send();
                    })
                    ->hidden(fn(PurchaseOrders $record): bool => $record->order_status === 'completed'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CodesRelationManager::class,
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductToBuys::route('/'),
            'create' => Pages\CreateProductToBuy::route('/create'),
            'edit' => Pages\EditProductToBuy::route('/{record}/edit'),
        ];
    }
}
