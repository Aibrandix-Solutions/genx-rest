<div class="border-b border-gray-200 dark:border-gray-700 mb-6">
    <nav class="-mb-px flex space-x-8" aria-label="Inventory">
        <a href="{{ route('inventory-stocks.index') }}"
           wire:navigate
           class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ request()->routeIs('inventory-stocks.index')
               ? 'border-skin-base text-skin-base dark:text-skin-base'
               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
            {{ __('inventory::modules.stock.stockInventory') }}
        </a>

        <a href="{{ route('inventory.consumption.index') }}"
           wire:navigate
           class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ request()->routeIs('inventory.consumption.index')
               ? 'border-skin-base text-skin-base dark:text-skin-base'
               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
            {{ __('inventory::modules.consumption.title') }}
        </a>

        <a href="{{ route('inventory.disposal.index') }}"
           wire:navigate
           class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ request()->routeIs('inventory.disposal.index')
               ? 'border-skin-base text-skin-base dark:text-skin-base'
               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
            {{ __('inventory::modules.disposal.tabLabel') }}
        </a>

        <a href="{{ route('inventory.consumption.report') }}"
           wire:navigate
           class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ request()->routeIs('inventory.consumption.report')
               ? 'border-skin-base text-skin-base dark:text-skin-base'
               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
            {{ __('inventory::modules.consumption.report.tabLabel') }}
        </a>
    </nav>
</div>
