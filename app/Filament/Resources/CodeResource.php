<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CodeResource\Pages;
use App\Filament\Resources\CodeResource\RelationManagers;
use App\Models\Code;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\ExportBulkAction;
use App\Filament\Exports\CodeExporter;
use App\Filament\Exports\CustomRemoteCrmExporter;

class CodeResource extends Resource
{
    protected static ?string $model = Code::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Select::make('account_id')
                    ->relationship('account', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'id')
                    ->searchable(),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('serial_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'product_name')
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('product_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('product_edition')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('buy_date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('buy_value')
                    ->required()
                    ->numeric()
                    ->step('0.01'),
                Forms\Components\TextInput::make('transaction_ref')
                    ->maxLength(255),
                Forms\Components\TextInput::make('transaction_id')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([100, 250, 500, 1000, 5000, 10000, 'all'])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date')
                            ->required(),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date')
                            ->required(),
                    ])
                    ->exporter(CodeExporter::class)
                ,
                Tables\Actions\Action::make('remoteCrmExport')
                    ->label('Remote CRM Export')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date')
                            ->default(now()->subDays(30)),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date')
                            ->default(now()),
                        Forms\Components\TextInput::make('discount')
                            ->label('Discount (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                    ])
                    ->action(function (array $data): void {
                        $params = http_build_query([
                            'from_date' => $data['from_date'],
                            'to_date' => $data['to_date'],
                            'discount' => $data['discount'],
                        ]);
                        $url = route('export.remote-crm') . '?' . $params;
                        redirect()->away($url);
                    })
                ,
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->numeric()
                    ->searchable(),


                Tables\Columns\TextColumn::make('account.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.id')
                    ->label('Order')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('serial_number')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('product.id')
                    ->label('Product ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.remote_crm_product_name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Remote CRM Product')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_edition')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('buy_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('buy_value')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('transaction_ref')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([


                Tables\Filters\SelectFilter::make('account_id')
                    ->label('Account')
                    ->searchable()
                    ->options(fn(): array => \App\Models\Account::pluck('name', 'id')->toArray()),

                Tables\Filters\SelectFilter::make('product_name')
                    ->label('Product Name')
                    ->searchable()
                    ->options(fn(): array => Code::whereNotNull('product_name')->distinct()->pluck('product_name', 'product_name')->toArray()),
                Tables\Filters\SelectFilter::make('product_edition')
                    ->label('Product Edition')
                    ->searchable()
                    ->options(fn(): array => Code::whereNotNull('product_edition')->distinct()->pluck('product_edition', 'product_edition')->toArray()),


                Tables\Filters\Filter::make('buy_date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->modifyQueryUsing(fn(Builder $query, array $data) => $query
                        ->when(
                            $data['created_from'],
                            fn(Builder $query, $date) => $query->whereDate('buy_date', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn(Builder $query, $date) => $query->whereDate('buy_date', '<=', $date),
                        ))

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('remoteCrmExportBulk')
                        ->label('Remote CRM Export')
                        ->form([
                            Forms\Components\DatePicker::make('from_date')
                                ->label('From Date')
                                ->default(now()->subDays(30)),
                            Forms\Components\DatePicker::make('to_date')
                                ->label('To Date')
                                ->default(now()),
                            Forms\Components\TextInput::make('discount')
                                ->label('Discount (%)')
                                ->numeric()
                                ->default(17)
                                ->minValue(0)
                                ->maxValue(100),
                        ])
                        ->action(function (array $data): void {
                            $params = http_build_query([
                                'from_date' => $data['from_date'],
                                'to_date' => $data['to_date'],
                                'discount' => $data['discount'],
                            ]);
                            $url = route('export.remote-crm') . '?' . $params;
                            redirect()->away($url);
                        })

                    ,
                    ExportBulkAction::make()
                        ->exporter(CodeExporter::class)

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCodes::route('/'),
            'create' => Pages\CreateCode::route('/create'),
            'edit' => Pages\EditCode::route('/{record}/edit'),
        ];
    }
}
