<div class="py-6 px-4 dark:bg-gray-900">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">
                @lang('inventory::modules.disposal.title')
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.disposal.subtitle')
            </p>
        </div>
    </div>

    {{-- Tabs --}}
    <x-inventory::stock.tabs />

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 rounded-lg shadow-sm p-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.disposal.totalDisposed')
            </p>
            <h3 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">
                {{ number_format($stats['total_qty'], 2) }}
            </h3>
        </div>
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 rounded-lg shadow-sm p-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.disposal.entries')
            </p>
            <h3 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">
                {{ number_format($stats['entries']) }}
            </h3>
        </div>
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/30 dark:to-yellow-800/30 rounded-lg shadow-sm p-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.disposal.uniqueItems')
            </p>
            <h3 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">
                {{ number_format($stats['unique_items']) }}
            </h3>
        </div>
        <div class="bg-gradient-to-br from-pink-50 to-pink-100 dark:from-pink-900/30 dark:to-pink-800/30 rounded-lg shadow-sm p-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.disposal.branches')
            </p>
            <h3 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">
                {{ number_format($stats['unique_branches']) }}
            </h3>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="lg:col-span-2 relative">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="@lang('inventory::modules.disposal.searchPlaceholder')"
                   class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
            <div class="absolute left-3 top-2.5">
                <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <div>
            <input type="date"
                   wire:model.live="startDate"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-red-500 focus:border-transparent">
        </div>
        <div>
            <input type="date"
                   wire:model.live="endDate"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-red-500 focus:border-transparent">
        </div>

        <div>
            <select wire:model.live="branchFilter"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                <option value="all">@lang('inventory::modules.disposal.allBranches')</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-2">
            <select wire:model.live="itemFilter"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                <option value="">@lang('inventory::modules.disposal.allItems')</option>
                @foreach($items as $it)
                    <option value="{{ $it->id }}">{{ $it->name }}@if($it->item_code) ({{ $it->item_code }})@endif</option>
                @endforeach
            </select>
        </div>

        @if($search || $branchFilter !== 'all' || $itemFilter)
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

    <!-- Breakdown Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        {{-- Per-item totals --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                    @lang('inventory::modules.disposal.perItemTotals')
                </h3>
            </div>
            <div class="overflow-y-auto max-h-64">
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
                                    @lang('inventory::modules.disposal.noData')
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
                    @lang('inventory::modules.disposal.perBranchBreakdown')
                </h3>
            </div>
            <div class="overflow-y-auto max-h-64">
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($branchTotals as $row)
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">
                                    {{ $row->branch->name ?? '--' }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-white">
                                    {{ number_format((float) $row->total_qty, 2) }}
                                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ $row->entries }})</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                    @lang('inventory::modules.disposal.noData')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Disposal Entries Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.disposal.date')</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.disposal.item')</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.disposal.branch')</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.disposal.quantity')</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.disposal.reason')</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.disposal.recordedBy')</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($disposals as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                {{ optional($row->disposal_date)->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $row->item->name ?? '--' }}</div>
                                @if(!empty($row->item?->item_code))
                                    <div class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $row->item->item_code }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                {{ $row->branch->name ?? '--' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                    - {{ number_format((float) $row->quantity, 2) }}
                                    {{ optional($row->item?->unit)->symbol }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 max-w-xs">
                                <span title="{{ $row->reason }}">
                                    {{ \Illuminate\Support\Str::limit($row->reason, 60) ?: '--' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $row->addedBy->name ?? '--' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                @lang('inventory::modules.disposal.noEntries')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $disposals->links() }}
        </div>
    </div>
</div>
