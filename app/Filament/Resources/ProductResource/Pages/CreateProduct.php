<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\AccountType;
use App\Models\Game;
use App\Models\Catalog;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Action::make('selectFromCatalog')
                ->label('Select game from catalog')
                ->icon('heroicon-o-shopping-cart')
                ->modalHeading('Select Game from Catalog')
                ->modalDescription('Choose a game to pre-fill the product details.')
                ->form([
                    Select::make('region_id')
                        ->label('Region')
                        ->searchable()
                        ->required()
                        ->options(function () {
                            $regions = Catalog::query()
                                ->pluck('region_id')
                                ->unique()
                                ->toArray();

                            $options = [];
                            foreach ($regions as $regionId) {
                                $accountType = \App\Models\AccountType::where('region_id', $regionId)->first();
                                $options[$regionId] = $accountType ? $accountType->name : $regionId;
                            }


                            return $options;
                        })
                        ->live()
                        ->afterStateUpdated(fn($state, $set) => $set('game_id', null)),
                    Select::make('game_id')

                        ->afterStateUpdated(function ($get, $set) {

                            $gameId = $get('game_id');
                            $gameRegion = Game::find($gameId)->region_id ?? null;
                            if ($gameRegion) {
                                $set('region_id', $gameRegion);
                            }
                        })
                        ->label('Game')
                        ->searchable()
                        ->required()
                        ->options(function (callable $get) {
                            if (!$get('region_id')) {
                                return [];
                            }

                            $vals =  Game::query()
                                ->where('region_id', $get('region_id'))
                                ->whereNotNull('vanity_name')
                                ->pluck('vanity_name', 'id')
                                ->toArray();

                            //addp rice

                            foreach ($vals as $key => $val) {
                                $game = Game::find($key);
                                $vals[$key] = $val . ' - gold:' .  $game->unit_gold . ' id:' . $game->product_id;
                            }

                            return $vals;
                        })
                        ->live()
                ])
                ->action(function (array $data): void {


                    $game = Game::find($data['game_id']);


                    $catalog = Catalog::where('region_id', $game->region_id)
                        ->where('title', 'like', '%' . $game->product_name . '%')
                        ->first();


                    $accountType = AccountType::where('region_id', $game->region_id)->first();

                    // Pre-fill the form with game data
                    $this->form->fill([
                        'id' => $game->product_id,
                        'product_name' => $game->vanity_name ?? '',
                        'remote_crm_product_name' => $game->product_name ?? '',
                        'product_slug' => $catalog->permalink ?? '',
                        'product_slugs' => [
                            [
                                'account_type' => $accountType ? $accountType->name : 'default',
                                'slug' => $catalog->permalink ?? '',
                            ]
                        ],
                        'product_names' => [
                            [
                                'account_type' => $accountType ? $accountType->name : 'default',
                                'name' => $game->vanity_name ?? $catalog->permalink ?? '',
                            ]
                        ],
                        'account_type' => [$accountType ? $accountType->name : 'default'],
                        'product_edition' => $game->vanity_name,
                        'product_buy_value' => $game->unit_base_gold,
                        'product_face_value' => $game->unit_gold,
                    ]);
                })
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
