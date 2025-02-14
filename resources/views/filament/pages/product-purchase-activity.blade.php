<x-filament-panels::page>
    <div class="filament-widgets-container">
        @foreach($this->getHeaderWidgets() as $widget)
            {!! $widget !!}
        @endforeach
    </div>
</x-filament-panels::page>
