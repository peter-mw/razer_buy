<?php

namespace App\Filament\Resources;

use App\Filament\Exports\PurchaseOrderCodesExporter;
use Filament\Tables\Actions\ExportAction;
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
    protected static ?int $navigationSort = 2;

    public static function validateBalance($get, $set): void
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

    public function mount(): void
    {

        $this->form->fill([
            'product_id' => request()->get('product_id') ?? null,
            'account_type' => request()->get('account_type') ?? null,
            'account_id' => request()->get('account_id') ?? null,
            'order_status' => request()->get('order_status') ?? 'pending'
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Select::make('account_type')
                    ->native(true)
                    ->required()
                    ->options([
                        'global' => 'Global',
                        'usa' => 'USA',
                    ])
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        // Clear product selection when account type changes
                        $set('product_id', null);
                        $set('account_id', null);

                        // Validate balance after account type change
                        static::validateBalance($get, $set);
                    }),


                Forms\Components\Select::make('account_id')
                    ->relationship(
                        'account',
                        'name',
                        function ($query, Forms\Get $get) {
                            $query->where('is_active', true)
                                ->orderByDesc('ballance_gold');

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
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} - Balance: \${$record->ballance_gold} ({$record->account_type}) id: {$record->id}")
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        // Validate after account change

                        if (!$get('account_type')) {
                            $set('account_type', Account::find($state)->account_type);
                        }


                        static::validateBalance($get, $set);
                    })
                ,


                Forms\Components\Select::make('product_id')
                    //  ->default(fn() => request()->get('product_id'))
                    ->relationship(
                        'product',
                        'product_name',
                        fn($query, Forms\Get $get) => $query->where('account_type', $get('account_type'))
                    )
                    ->label('Product')
                    ->required()
                    ->live()
                    ->reactive()
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->id} -{$record->product_name} - \${$record->product_buy_value}")
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            $product = Product::find($state);

                            if ($product) {
                                $set('product_name', $product->product_name);
                                $set('product_edition', $product->product_edition);
                                $set('buy_value', $product->product_buy_value);
                                $set('product_face_value', $product->product_face_value);
                                $set('account_type', $product->account_type);
                            }
                        }

                        // Always validate balance regardless of product state
                        static::validateBalance($get, $set);
                    }),
                Forms\Components\Hidden::make('product_name')
                    ->label('Product Name (slug)'),
                Forms\Components\Hidden::make('product_edition'),

                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->reactive()

                    ->live(debounce: 1500)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        // Validate after quantity change
                        static::validateBalance($get, $set);
                    }),
                Forms\Components\Hidden::make('buy_value'),
                Forms\Components\Hidden::make('product_face_value'),

                Forms\Components\Select::make('order_status')
                    ->default('pending')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed'
                    ])
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
            ->actionsPosition(Tables\Enums\ActionsPosition::BeforeCells)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('account_id')
                    ->label('Account')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                 ,

                Tables\Columns\TextColumn::make('product_id')
                    ->sortable()
                    ->label('Product')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_edition')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('order_status')
                    ->badge()
                    ->searchable()
                    ->label('Status')
                    ->color(fn(string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
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

                Tables\Columns\TextColumn::make('account_type')
                    ->badge()
                    ->label('Type')
                    ->color(fn(string $state): string => match ($state) {
                        'global' => 'warning',
                        'usa' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_multiple')
                    ->label('Create Multiple Orders')
                    ->icon('heroicon-o-plus')
                    ->url(fn(): string => static::getUrl('create-multiple')),
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
                /*                ExportAction::make()
                                    ->label('Export Codes')
                                    ->exporter(PurchaseOrderCodesExporter::class)
                //                    ->modifyQueryUsing(function (PurchaseOrders $record, $query) {
                //                        return $query->where('id', $record->id);
                //                    })
                                    ->visible(fn(PurchaseOrders $record): bool => $record->order_status === 'completed' &&
                                        $record->codes()->count() > 0
                                    ),*/
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
            'create-multiple' => Pages\CreateMultipleOrders::route('/create-multiple'),
        ];
    }
}
