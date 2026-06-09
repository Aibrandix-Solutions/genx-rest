<div class="py-6 px-4 dark:bg-gray-900">
    <!-- Header Section -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">@lang("inventory::modules.stock.stockInventory")</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">@lang("inventory::modules.stock.stockInventoryDescription")</p>
        </div>

        <div class="flex gap-4">
            <x-secondary-button wire:click="export" wire:loading.attr="disabled">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                @lang('app.export')
            </x-secondary-button>

            @if(user_can('Create Inventory Movement'))
            <x-button wire:click="$set('showAddStockEntry', true)" >
                @lang("inventory::modules.stock.addStockEntry")
            </x-button>
            @endif
        </div>
    </div>

    <x-inventory::stock.tabs />

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Available Items -->
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
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang("inventory::modules.stock.availableItems")</p>
                            <h3 class="text-xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($stats['available_items']) }}</h3>
                        </div>
                    </div>
                    <div class="text-green-600 dark:text-green-400">
                        <span class="text-sm font-medium">+{{ number_format($stats['available_items'] / max(array_sum($stats), 1) * 100, 1) }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Items -->
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
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang("inventory::modules.stock.lowStockItems")</p>
                            <h3 class="text-xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($stats['low_stock']) }}</h3>
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
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang("inventory::modules.stock.outOfStock")</p>
                            <h3 class="text-xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($stats['out_of_stock']) }}</h3>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Total Cost -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-lg shadow-sm">
            <div class="px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/50 p-2 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang("inventory::modules.stock.totalCost")</p>
                            <h3 class="text-xl font-bold text-gray-700 dark:text-gray-200">{{ currency_format($stats['total_cost'], restaurant()->currency_id) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 space-y-4">
        <!-- Search Bar -->
        <div class="relative">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="@lang('inventory::modules.stock.searchByNameOrCode')"
                   class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-600 focus:border-transparent">
            <div class="absolute left-3 top-2.5">
                <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <!-- Category Filter -->
            <select wire:model.live="category" class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-4 focus:ring-2 focus:ring-indigo-600 focus:border-transparent">
                <option value="">@lang('inventory::modules.stock.allCategories')</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>

            <!-- Location Filter -->
            <select wire:model.live="locationFilter" class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-4 focus:ring-2 focus:ring-indigo-600 focus:border-transparent">
                <option value="all">@lang('inventory::modules.stock.allLocations')</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}">
                        {{ $location->name }} 
                        @if($location->type !== 'branch')
                            ({{ ucfirst($location->type) }})
                        @endif
                    </option>
                @endforeach
            </select>

            <!-- Stock Status Filter -->
            <select wire:model.live="stockStatus" class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-4 focus:ring-2 focus:ring-indigo-600 focus:border-transparent">
                <option value="">@lang('inventory::modules.stock.allStatus')</option>
                <option value="in_stock">@lang('inventory::modules.stock.inStock')</option>
                <option value="low_stock">@lang('inventory::modules.stock.lowStock')</option>
                <option value="out_of_stock">@lang('inventory::modules.stock.outOfStock')</option>
            </select>

            <!-- Per Page Dropdown -->
            <div>
                <x-dropdown align="left">
                    <x-slot name="trigger">
                        <span class="inline-flex rounded-md w-full">
                            <button type="button"
                                class="inline-flex items-center justify-between w-full px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 bg-white dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
                                <span>@lang('app.perPage')</span>
                                <div class="flex items-center gap-1">
                                    @if ($perPage != 20)
                                    <div class="inline-flex items-center justify-center w-5 h-5 text-xs font-medium text-white bg-red-500 rounded-md dark:border-gray-900">{{ $perPage }}</div>
                                    @endif
                                    <svg class="w-5 h-5" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path clip-rule="evenodd" fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                    </svg>
                                </div>
                            </button>
                        </span>
                    </x-slot>

                    <x-slot name="content">
                        <div class="block px-4 py-2 text-sm font-medium text-gray-500">
                            <h6 class="text-sm font-medium text-gray-900 dark:text-white">
                                @lang('app.perPage')
                            </h6>
                        </div>
                        
                        @foreach ([20, 50, 100, 200] as $items)
                        <x-dropdown-link class="flex items-center">
                            <input id="per-page-{{ $items }}" type="radio" value="{{ $items }}" wire:model.live='perPage'
                                class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-gray-600 focus:ring-gray-500 dark:focus:ring-gray-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500" />
                            <label for="per-page-{{ $items }}" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $items }} @lang('app.items')
                            </label>
                        </x-dropdown-link>
                        @endforeach

                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Clear Filters Button -->
            @if($search || $category || $stockStatus || $locationFilter !== 'all')
                <button
                    wire:click="clearFilters"
                    class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    @lang('inventory::modules.stock.clearFilters')
                </button>
            @endif
        </div>
    </div>

    <!-- Stock Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang("inventory::modules.inventoryItem.name")</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang("inventory::modules.inventoryItem.category")</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang("inventory::modules.stock.currentStock")</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang("inventory::modules.stock.stockStatus")</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang("inventory::modules.stock.cost")</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang("app.action")</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($stockItems as $item)
                        @php
                            $stockStatus = $item->getStockStatus();
                            $nearestExpiry = $item->stocks->min('expiry_date');
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->name }}</div>
                                @if(!empty($item->item_code))
                                    <div class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $item->item_code }}</div>
                                @else
                                    <div class="text-sm text-gray-500 dark:text-gray-400">#{{ $item->id }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $item->category->name ?? '-'}}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ number_format($item->filtered_stock ?? 0, 2) }} {{ $item->unit->symbol }}</div>
                                <div clas6="text-xs text-gray-500 dark:text-gray-400">@lang("inventory::modules.stock.minStock"): {{ number_format($item->threshold_quantity, 2) }} {{ $item->unit->symbol }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $stockStatus['class'] }}">
                                    {{ $stockStatus['status'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ currency_format($item->total_cost_value ?? 0, restaurant()->currency_id) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="viewStockLocations({{ $item->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                        title="@lang('inventory::modules.stock.viewByLocation')"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        @lang('app.view')
                                    </button>

                                    @if(user_can('Create Inventory Movement'))
                                        <button
                                            type="button"
                                            wire:click="$dispatch('openRecordConsumption', { itemId: {{ $item->id }} })"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-purple-500 text-purple-600 dark:text-purple-400 dark:border-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/30 transition"
                                            title="@lang('inventory::modules.consumption.recordConsumption')"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2h-3l-2-2H8L6 5H3a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            @lang('inventory::modules.consumption.consumption')
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="$dispatch('openRecordDisposal', { itemId: {{ $item->id }} })"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-red-400 text-red-600 dark:text-red-400 dark:border-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition"
                                            title="@lang('inventory::modules.disposal.recordDisposal')"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            @lang('inventory::modules.disposal.dispose')
                                        </button>
                                    @endif
                                </div>
                            </td>


                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                @lang("inventory::modules.stock.noStockItemsFound")
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $stockItems->links() }}
        </div>
    </div>

    <x-right-modal wire:model.live="showAddStockEntry">
        <x-slot name="title">
            @lang("inventory::modules.stock.addStockEntry")
        </x-slot>

        <x-slot name="content">
            <livewire:inventory::stock.add-stock-entry />
        </x-slot>
    </x-right-modal>

    {{-- Stock by Location modal --}}
    <x-dialog-modal wire:model.live="showStockLocationsModal" maxWidth="4xl">
        <x-slot name="title">
            @if($selectedItem)
                @lang('inventory::modules.stock.stockByLocationTitle')
            @else
                @lang('inventory::modules.stock.viewByLocation')
            @endif
        </x-slot>

        <x-slot name="content">
            @if($selectedItem)
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $selectedItem->name }}
                        </div>
                        <div class="mt-0.5 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            @if(!empty($selectedItem->item_code))
                                <span class="font-mono bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ $selectedItem->item_code }}</span>
                            @endif
                            @if($selectedItem->category)
                                <span>{{ $selectedItem->category->name }}</span>
                            @endif
                            @if($selectedItem->unit)
                                <span>&middot; {{ $selectedItem->unit->name }} ({{ $selectedItem->unit->symbol }})</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500 dark:text-gray-400">@lang('inventory::modules.stock.totalQuantity')</div>
                        <div class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ number_format($locationBreakdown->sum('quantity'), 2) }} {{ optional($selectedItem->unit)->symbol }}
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-md">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.stock.location')</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.inventoryMovement.type')</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.stock.quantity')</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.stock.cost')</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($locationBreakdown as $row)
                                <tr class="{{ $row->quantity <= 0 ? 'opacity-60' : '' }}">
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $row->name }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $row->type }}</td>
                                    <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                        {{ number_format($row->quantity, 2) }}
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ optional($selectedItem->unit)->symbol }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                        {{ currency_format($row->cost_value, restaurant()->currency_id) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-sm text-center text-gray-500 dark:text-gray-400">
                                        @lang('inventory::modules.stock.noLocationsAvailable')
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Recent Purchases ----------------------------------------- --}}
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                            @if($expandedPurchases)
                                @lang('inventory::modules.stock.allPurchases') ({{ $itemPurchasesTotal }})
                            @else
                                @lang('inventory::modules.stock.recentPurchases')
                            @endif
                        </h4>
                        @if($itemPurchasesTotal > \Modules\Inventory\Livewire\Stock\StockList::RECENT_PURCHASES_LIMIT)
                            <button type="button"
                                    wire:click="toggleExpandedPurchases"
                                    class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                @if($expandedPurchases)
                                    @lang('inventory::modules.stock.showRecentOnly')
                                @else
                                    @lang('inventory::modules.stock.viewAllPurchases', ['count' => $itemPurchasesTotal])
                                @endif
                            </button>
                        @endif
                    </div>

                    <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-md">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.purchaseOrder.po_number')</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.purchaseOrder.order_date')</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.purchaseOrder.supplier')</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.stock.location')</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.stock.quantity')</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.purchaseOrder.unit_price')</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('app.action')</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @php
                                    $purchaseRows = $expandedPurchases ? $itemPurchases->items() : $itemPurchases;
                                @endphp
                                @forelse($purchaseRows as $purchase)
                                    @php
                                        $line = $purchase->items->first();
                                        $locName = $purchase->location?->display_name
                                            ?? $purchase->location?->name
                                            ?? $purchase->branch?->name
                                            ?? '--';
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                            {{ $purchase->po_number ?? ('#' . $purchase->id) }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                            {{ optional($purchase->order_date)->format('M d, Y') ?? optional($purchase->created_at)->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $purchase->supplier?->name ?? '--' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $locName }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                            @if($line)
                                                {{ number_format((float) $line->quantity, 2) }}
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ optional($selectedItem->unit)->symbol }}</span>
                                            @else
                                                --
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                            @if($line)
                                                {{ currency_format((float) $line->unit_price, restaurant()->currency_id) }}
                                            @else
                                                --
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right">
                                            <button type="button"
                                                    wire:click="openPurchaseOrder({{ $purchase->id }})"
                                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                                    title="@lang('inventory::modules.purchaseOrder.view_details')">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                @lang('app.view')
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-3 text-sm text-center text-gray-500 dark:text-gray-400">
                                            @lang('inventory::modules.stock.noPurchasesForItem')
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        @if($expandedPurchases && $itemPurchases instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $itemPurchases->hasPages())
                            <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                                {{ $itemPurchases->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeStockLocationsModal">
                @lang('app.close')
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Reuses the same Purchase Order detail modal that the Purchases page
         uses; it listens for the `viewPurchaseOrder` event we dispatch above. --}}
    <livewire:inventory::purchase-order.view-purchase-order />

    {{-- Record Consumption modal (listens for `openRecordConsumption` event) --}}
    <livewire:inventory::stock.record-consumption />

    {{-- Record Disposal modal (listens for `openRecordDisposal` event) --}}
    <livewire:inventory::stock.record-disposal />
</div>
