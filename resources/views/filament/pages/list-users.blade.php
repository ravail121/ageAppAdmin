<x-filament-panels::page>
    <div wire:loading.class="opacity-50"
         wire:target="tableFilters,applyTableFilters,resetTableFiltersForm, nextPage, gotoPage, previousPage, tableRecordsPerPage"
         class="relative">
        {{ $this->table }}

        <div
            wire:loading
            wire:target="tableFilters,applyTableFilters,resetTableFiltersForm, nextPage, gotoPage, previousPage, tableRecordsPerPage"
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2" style="left: 50%;">
            <x-filament::loading-indicator class="h-10 w-10 text-primary-500"/>
        </div>
    </div>
</x-filament-panels::page>