<div class="py-6 px-4 dark:bg-gray-900">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">@lang('inventory::modules.admin.inventoryDashboard')</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">@lang('inventory::modules.admin.inventoryDashboardSubtitle')</p>
        </div>

        <x-secondary-button wire:click="export" wire:loading.attr="disabled">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            @lang('app.export')
        </x-secondary-button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- Total Items -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-lg shadow-sm">
            <div class="px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/50 p-2 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m0 0l8 4m0 0l8-4m0 0v10l-8 4m0 0l-8-4m0 0v10l8 4m0 0l8-4"/>
                            </svg>
                        </span>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang('inventory::modules.admin.totalItems')</p>
                            <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($stats['total_items']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Quantity -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 rounded-lg shadow-sm">
            <div class="px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/50 p-2 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang('inventory::modules.admin.totalQuantity')</p>
                            <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($stats['total_stock']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Value -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 rounded-lg shadow-sm">
            <div class="px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/50 p-2 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang('inventory::modules.admin.totalValue')</p>
                            <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ restaurant()->currency_symbol }}{{ number_format($stats['total_value'], 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock -->
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/30 dark:to-yellow-800/30 rounded-lg shadow-sm">
            <div class="px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/50 p-2 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </span>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang('inventory::modules.admin.lowStock')</p>
                            <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($stats['low_stock_count']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Out of Stock -->
        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 rounded-lg shadow-sm">
            <div class="px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/50 p-2 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                        </span>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang('inventory::modules.admin.outOfStock')</p>
                            <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($stats['out_of_stock_count']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Search -->
            <div>
                <x-label value="@lang('app.search')" />
                <x-input type="text" placeholder="@lang('inventory::modules.stock.searchPlaceholder')" wire:model.live="search" class="mt-1" />
            </div>

            <!-- Branch Filter -->
            <div>
                <x-label value="@lang('inventory::modules.stock.selectBranch')" />
                <select wire:model.live="branchFilter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="">@lang('app.all')</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Location Filter -->
            <div>
                <x-label value="@lang('inventory::modules.stock.location')" />
                <select wire:model.live="locationFilter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="">@lang('inventory::modules.stock.allLocations')</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }} ({{ ucfirst($location->type) }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Stock Status -->
            <div>
                <x-label value="@lang('inventory::modules.stock.stockStatus')" />
                <select wire:model.live="stockStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="">@lang('inventory::modules.stock.allStatus')</option>
                    <option value="in-stock">@lang('inventory::modules.stock.inStock')</option>
                    <option value="low-stock">@lang('inventory::modules.stock.lowStock')</option>
                    <option value="out-of-stock">@lang('app.outOfStock')</option>
                </select>
            </div>

            <!-- Clear Filters -->
            <div class="flex items-end">
                <x-secondary-button wire:click="$reset" class="w-full">
                    @lang('inventory::modules.stock.clearFilters')
                </x-secondary-button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('inventory::modules.stock.itemName')</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('app.branch')</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('inventory::modules.stock.location')</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('inventory::modules.stock.quantity')</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('inventory::modules.stock.unitPurchasePrice')</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('inventory::modules.stock.totalCost')</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('app.status')</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($items as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->category?->name ?? '—' }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $item->branch?->name ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @forelse($item->stocks->unique('location_id') as $stock)
                                <span class="inline-block px-2 py-1 mb-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $stock->location?->name ?? '—' }} ({{ $stock->quantity }})
                                </span>
                            @empty
                                <span class="text-sm text-gray-500">—</span>
                            @endforelse
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ number_format($item->total_quantity ?? 0) }} {{ $item->unit?->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-700 dark:text-gray-300">
                            {{ restaurant()->currency_symbol }}{{ number_format($item->unit_purchase_price, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ restaurant()->currency_symbol }}{{ number_format($item->total_cost_value ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $qty = $item->total_quantity ?? 0;
                                $threshold = $item->threshold_quantity ?? 0;
                            @endphp
                            @if($qty <= 0)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    @lang('inventory::modules.stock.status.out-of-stock')
                                </span>
                            @elseif($qty <= $threshold)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    @lang('inventory::modules.stock.status.low-stock')
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    @lang('inventory::modules.stock.status.adequate')
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('inventory::modules.stock.noStockItemsFound')</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $items->links() }}
    </div>
</div>
