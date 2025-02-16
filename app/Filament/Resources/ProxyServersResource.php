<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProxyServersResource\Pages;
use App\Models\ProxyServer;
use App\Models\AccountType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProxyServersResource extends Resource
{
    protected static ?string $model = ProxyServer::class;
    protected static ?int $navigationSort = 999;
    protected static ?string $navigationGroup = 'Other';
    protected static ?string $navigationIcon = 'heroicon-o-server';

    public static function form(Form $form): Form
    {
        $accountTypes = AccountType::where('is_active', true)->pluck('name', 'code')->toArray();

        return $form
            ->schema([
                Forms\Components\TextInput::make('proxy_server_ip')
                    ->required()
                    ->ipv4()
                    ->label('Proxy Server IP'),

                Forms\Components\TextInput::make('proxy_server_port')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(65535)
                    ->label('Proxy Server Port'),

                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true)
                    ->label('Is Active'),

                Forms\Components\DateTimePicker::make('last_used_time')
                    ->nullable()
                    ->label('Last Used Time'),

                Forms\Components\CheckboxList::make('proxy_account_type')
                    ->options($accountTypes)
                    ->columns(3)
                    ->gridDirection('row')
                    ->label('Account Types'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('proxy_server_ip')
                    ->searchable()
                    ->sortable()
                    ->label('IP Address'),

                Tables\Columns\TextColumn::make('proxy_server_port')
                    ->searchable()
                    ->sortable()
                    ->label('Port'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('last_used_time')
                    ->dateTime()
                    ->sortable()
                    ->label('Last Used'),

                Tables\Columns\TextColumn::make('proxy_account_type')

                    ->label('Account Types'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProxyServers::route('/'),
            'create' => Pages\CreateProxyServer::route('/create'),
            'edit' => Pages\EditProxyServer::route('/{record}/edit'),
        ];
    }
}
