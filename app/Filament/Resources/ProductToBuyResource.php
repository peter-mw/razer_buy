<?php

namespace App\Filament\Resources;

use App\Filament\Exports\CodeExporter;
use App\Filament\Resources\ProductToBuyResource\Pages;
use App\Filament\Resources\ProductToBuyResource\RelationManagers;
use App\Models\ProductToBuy;
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
use Illuminate\Support\Facades\Artisan;

class ProductToBuyResource extends Resource
{
    protected static ?string $model = ProductToBuy::class;
    protected static ?string $label = 'Purchase order';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 3;

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
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $product = Product::find($state);
                            if ($product) {
                                $set('product_name', $product->product_slug);
                                $set('product_edition', $product->product_edition);
                                $set('buy_value', $product->product_buy_value);
                                $set('product_face_value', $product->product_face_value);
                                $set('account_type', $product->account_type);
                            }
                        }
                    }),
                Forms\Components\Hidden::make('product_name')
                    ->label('Product Name (slug)')


                ,
                Forms\Components\Hidden::make('product_edition')

                ,

                Forms\Components\Select::make('account_type')
                    ->required()
                    ->options([
                        'global' => 'Global',
                        'usa' => 'USA',
                    ])
                    ->default('standard'),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\Hidden::make('buy_value')
                    ,
                Forms\Components\Hidden::make('product_face_value')
                    ,
              /*  Forms\Components\Toggle::make('is_active')
                ,*/
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
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
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('buy_value')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_face_value')
                    ->money()
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
                        $products = ProductToBuy::where('order_status', '!=', 'completed')->get();
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

                            $exitCode = Artisan::call('app:process-buy', [
                                'product' => $product->id,
                                'quantity' => $product->quantity
                            ]);

                            $output .= Artisan::output();

                            $product->update([
                                'order_status' => $exitCode === 0 ? 'completed' : 'failed'
                            ]);
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
                            ->default(fn(ProductToBuy $record) => $record->quantity)
                            ->minValue(1)
                            ->maxValue(fn(ProductToBuy $record) => $record->quantity)
                    ])
                    ->action(function (ProductToBuy $record, array $data) {
                        if ($record->order_status === 'completed') {
                            Notification::make()
                                ->title('Order already completed')
                                ->warning()
                                ->send();
                            return;
                        }

                        $record->update(['order_status' => 'processing']);

                        $exitCode = Artisan::call('app:process-buy', [
                            'product' => $record->id,
                            'quantity' => $data['quantity']
                        ]);

                        if ($exitCode === 0) {
                            $record->update(['order_status' => 'completed']);
                            Notification::make()
                                ->title('Buy process completed')
                                ->success()
                                ->body(Artisan::output())
                                ->send();
                        } else {
                            $record->update(['order_status' => 'failed']);
                            Notification::make()
                                ->title('Failed to process buy')
                                ->danger()
                                ->body(Artisan::output())
                                ->send();
                        }
                    })
                    ->hidden(fn(ProductToBuy $record): bool => $record->order_status === 'completed')
                ,
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
