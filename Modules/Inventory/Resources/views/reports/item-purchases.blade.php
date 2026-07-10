<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('inventory::modules.reports.item_purchases.title') }}
        </h2>
    </x-slot>

    <div>
        <div class="mx-auto sm:px-6 lg:px-8">
            @livewire('inventory::reports.item-purchases-report')
        </div>
    </div>
</x-app-layout>
