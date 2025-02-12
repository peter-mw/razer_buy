<?php

namespace App\Forms\Components;

use Filament\Forms\Components\TextInput;

class QuantityInput extends TextInput
{
    public function maxQuantity(int $value): static
    {
        $this->type('number');
        $this->maxValue($value);
        return $this;
    }

    public function configure(): static
    {
        parent::configure();
        
        $this->type('number')
            ->minValue(0)
            ->extraAttributes(['class' => 'w-24']);

        return $this;
    }
}
