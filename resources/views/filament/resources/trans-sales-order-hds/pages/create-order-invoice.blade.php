<x-filament-panels::page>
    <form wire:submit="create" class="space-y-6">
        {{ $this->form }}

        <div class="pt-4">
            <x-filament::button type="submit">
                Buat Invoice
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
