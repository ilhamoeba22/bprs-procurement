<x-filament-panels::page>
    <form wire:submit="create">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Submit Pengajuan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>