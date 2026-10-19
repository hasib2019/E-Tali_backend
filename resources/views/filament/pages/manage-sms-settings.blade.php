<x-filament-panels::page>
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ($this->getStats() as $label => $value)
            <x-filament::section>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ number_format($value) }}</div>
            </x-filament::section>
        @endforeach
    </div>

    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Save
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
