<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-x-3">
            @if($this->modifyMode)
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-warning-50 px-3 py-1.5 text-sm font-medium text-warning-700 dark:bg-warning-400/10 dark:text-warning-400">
                    <x-filament::icon
                        icon="heroicon-m-exclamation-triangle"
                        class="h-4 w-4"
                    />
                    Modify Mode Active
                </span>
            @endif

            <x-filament::button
                type="submit"
                size="lg"
            >
                <x-filament::icon
                    icon="heroicon-m-check"
                    class="mr-1 h-5 w-5"
                />
                Save Settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
