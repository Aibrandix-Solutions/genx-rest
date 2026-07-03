<div class="py-6 px-4 dark:bg-gray-900">
    <!-- Header Section -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">
                @lang('inventory::modules.consumption.title')
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.consumption.subtitle')
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('inventory.consumption.report') }}"
               wire:navigate
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg border border-purple-500 text-purple-600 dark:text-purple-400 dark:border-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/30 transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h4v6m4 0V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10m12 0H5"/>
                </svg>
                @lang('inventory::modules.consumption.report.viewReport')
            </a>
        </div>
    </div>

    {{-- Inventory tabs (Stock / Consumption) --}}
    <x-inventory::stock.tabs />

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 rounded-lg shadow-sm p-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.consumption.totalConsumed')
            </p>
            <h3 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">
                {{ number_format($stats['total_qty'], 2) }}
            </h3>
        </div>
        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/30 dark:to-indigo-800/30 rounded-lg shadow-sm p-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.consumption.entries')
            </p>
            <h3 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">
                {{ number_format($stats['entries']) }}
            </h3>
        </div>
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-lg shadow-sm p-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.consumption.uniqueItems')
            </p>
            <h3 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">
                {{ number_format($stats['unique_items']) }}
            </h3>
        </div>
        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/30 rounded-lg shadow-sm p-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.stock.location')
            </p>
            <h3 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">
                {{ number_format($stats['unique_locations']) }}
            </h3>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="lg:col-span-2 relative">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="@lang('inventory::modules.consumption.searchPlaceholder')"
                   class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-600 focus:border-transparent">
            <div class="absolute left-3 top-2.5">
                <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <div>
            <input type="date"
                   wire:model.live="startDate"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-purple-600 focus:border-transparent">
        </div>
        <div>
            <input type="date"
                   wire:model.live="endDate"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-purple-600 focus:border-transparent">
        </div>

        <div>
            <select wire:model.live="locationFilter"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-purple-600 focus:border-transparent">
                <option value="all">@lang('inventory::modules.stock.allLocations')</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->display_name ?? $location->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-2">
            <select wire:model.live="itemFilter"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-purple-600 focus:border-transparent">
                <option value="">@lang('inventory::modules.consumption.allItems')</option>
                @foreach($items as $it)
                    <option value="{{ $it->id }}">{{ $it->name }}@if($it->item_code) ({{ $it->item_code }})@endif</option>
                @endforeach
            </select>
        </div>

        @if($search || $locationFilter !== 'all' || $itemFilter)
            <div>
                <button wire:click="clearFilters"
                        class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    @lang('inventory::modules.stock.clearFilters')
                </button>
            </div>
        @endif
    </div>

    <!-- Breakdown Cards: Per-item, Per-branch, Per-menu -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        {{-- Per-item totals --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                    @lang('inventory::modules.consumption.perItemTotals')
                </h3>
            </div>
            <div class="overflow-y-auto max-h-72">
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($itemTotals as $row)
                            <tr>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $row->item->name ?? '--' }}</div>
                                    @if(!empty($row->item?->item_code))
                                        <div class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $row->item->item_code }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-white">
                                    {{ number_format((float) $row->total_qty, 2) }}
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ optional($row->item?->unit)->symbol }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                    @lang('inventory::modules.consumption.noData')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Per-branch breakdown --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                    @lang('inventory::modules.stock.location')
                </h3>
            </div>
            <div class="overflow-y-auto max-h-72">
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($locationTotals as $row)
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">
                                    {{ $row->location?->display_name ?? $row->location?->name ?? '--' }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-white">
                                    {{ number_format((float) $row->total_qty, 2) }}
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        ({{ $row->entries }})
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                    @lang('inventory::modules.consumption.noData')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Menu-wise usage --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                    @lang('inventory::modules.consumption.menuWiseUsage')
                </h3>
            </div>
            <div class="overflow-y-auto max-h-72">
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($menuTotals as $row)
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">
                                    {{ $row->menu_item_name }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-white">
                                    {{ number_format((float) $row->total_qty, 2) }}
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        ({{ $row->entries }})
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                    @lang('inventory::modules.consumption.noData')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Consumption Entries Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.consumption.date')</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.consumption.item')</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.stock.location')</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.consumption.quantity')</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.consumption.menusUsed')</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.consumption.note')</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.consumption.recordedBy')</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($consumptions as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                {{ optional($row->consumption_date)->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $row->item->name ?? '--' }}</div>
                                @if(!empty($row->item?->item_code))
                                    <div class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $row->item->item_code }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                {{ $row->location?->display_name ?? $row->location?->name ?? '--' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                {{ number_format((float) $row->quantity, 2) }}
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ optional($row->item?->unit)->symbol }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @if($row->menuItems->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @foreach($row->menuItems as $mi)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                                                {{ $mi->item_name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">--</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 max-w-xs">
                                <span title="{{ $row->note }}">
                                    {{ \Illuminate\Support\Str::limit($row->note, 60) ?: '--' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $row->addedBy->name ?? '--' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                @lang('inventory::modules.consumption.noEntries')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $consumptions->links() }}
        </div>
    </div>
</div>
