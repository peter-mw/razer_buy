<?php

namespace App\Filament\Resources;

use App\Filament\Exports\ProductExporter;
use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Filament\Widgets\ProductActivityInlineChartWidget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Products Catalog';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->label('Product ID')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('product_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('product_slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Radio::make('account_type')
                    ->required()
                    ->options([
                        'global' => 'Global',
                        'usa' => 'USA',
                    ]),

                Forms\Components\TextInput::make('product_edition')
                    ->label('Product Edition (same as product name if not applicable)')
                    ->maxLength(255),
                Forms\Components\TextInput::make('product_buy_value')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->step('0.01'),
                Forms\Components\TextInput::make('product_face_value')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->step('0.01'),
                Forms\Components\TextInput::make('remote_crm_product_name')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->defaultSort('id', 'desc')
            ->actionsPosition(Tables\Enums\ActionsPosition::BeforeColumns)
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->importer(ProductImporter::class),
                Tables\Actions\ExportAction::make()
                    ->exporter(ProductExporter::class),
            ])
            ->columns([
                \LaraZeus\InlineChart\Tables\Columns\InlineChart::make('activity')
                    ->chart(ProductActivityInlineChartWidget::class)
                    ->maxWidth(350)
                    ->maxHeight(90)
                    ->description('Last 7 days activity')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('id')
                    ->label('Product ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_slug')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),


                Tables\Columns\TextColumn::make('product_buy_value')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_face_value')
                    ->money()
                    ->sortable(),


                Tables\Columns\TextColumn::make('account_type')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('product_edition')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('remote_crm_product_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account_type')
                    ->options([
                        'global' => 'Global',
                        'usa' => 'USA',
                    ]),
                Tables\Filters\SelectFilter::make('product_slug')
                    ->options(fn(): array => Product::query()
                        ->whereNotNull('product_slug')
                        ->pluck('product_slug', 'product_slug')
                        ->toArray()
                    )
            ])
            ->actions([
                /*    Tables\Actions\Action::make('create_order')
                        ->label('Create Order')
                        ->icon('heroicon-o-shopping-cart')
                        ->url(fn (Product $record): string =>
                            PurchaseOrderResource::getUrl('create', [
                                'product_id' => $record->id,
                                'account_type' => $record->account_type
                            ])
                        )
                        ->openUrlInNewTab(),*/
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ExportBulkAction::make()
                        ->exporter(ProductExporter::class),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
