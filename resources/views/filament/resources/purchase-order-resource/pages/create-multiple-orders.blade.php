<x-filament-panels::page>
    <form wire:submit="createOrders">
        {{ $this->form }}

        <div class="flex justify-end space-x-4 mt-6">
            <x-filament::button
                type="submit"
                color="primary"
            >
                Create Orders
            </x-filament::button>

            <x-filament::button
                wire:click="createAndExecuteOrders"
                color="success"
            >
                Create and Execute Orders
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
