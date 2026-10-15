<x-filament-panels::page>
    <form wire:submit="create" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-document-plus">
            Buat Invoice
        </x-filament::button>
    </form>
</x-filament-panels::page>
