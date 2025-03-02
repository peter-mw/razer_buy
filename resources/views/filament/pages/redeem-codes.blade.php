<x-filament-panels::page>
    <form wire:submit="redeem">
        {{ $this->form }}
 
        <div class="mt-4">
            {{ $this->getFormActions()[0] }}
        </div>
    </form>
</x-filament-panels::page>
