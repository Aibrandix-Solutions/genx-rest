<div class="py-6 px-4 dark:bg-gray-900">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">
                @lang('inventory::modules.consumption.report.title')
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.consumption.report.subtitle')
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('inventory.consumption.report.print', request()->query()) }}"
               target="_blank"
               rel="noopener"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-purple-500 text-purple-600 dark:text-purple-400 dark:border-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/30 transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                @lang('inventory::modules.consumption.report.print')
            </a>

            <a href="{{ route('inventory.consumption.index') }}"
               wire:navigate
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                @lang('inventory::modules.consumption.report.backToConsumption')
            </a>
        </div>
    </div>

    <x-inventory::stock.tabs />

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 rounded-lg shadow-sm p-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.consumption.totalConsumed')
            </p>
            <h3 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">
                {{ number_format($totals['consumed'], 2) }}
            </h3>
        </div>
        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/30 dark:to-indigo-800/30 rounded-lg shadow-sm p-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.consumption.entries')
            </p>
            <h3 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">
                {{ number_format($totals['entries']) }}
            </h3>
        </div>
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-lg shadow-sm p-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                @lang('inventory::modules.consumption.uniqueItems')
            </p>
            <h3 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">
                {{ number_format($totals['items']) }}
            </h3>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="lg:col-span-2 relative">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="@lang('inventory::modules.consumption.report.searchPlaceholder')"
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
            <select wire:model.live="branchFilter"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-purple-600 focus:border-transparent">
                <option value="all">@lang('inventory::modules.consumption.allBranches')</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
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

        {{-- View mode toggle --}}
        <div class="lg:col-span-2 flex items-center gap-2">
            <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                <button type="button"
                        wire:click="$set('viewMode', 'summary')"
                        class="px-3 py-2 text-sm {{ $viewMode === 'summary' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    @lang('inventory::modules.consumption.report.viewSummary')
                </button>
                <button type="button"
                        wire:click="$set('viewMode', 'detail')"
                        class="px-3 py-2 text-sm border-l border-gray-300 dark:border-gray-600 {{ $viewMode === 'detail' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    @lang('inventory::modules.consumption.report.viewDetail')
                </button>
            </div>

            @if($search || $branchFilter !== 'all' || $itemFilter)
                <button wire:click="clearFilters"
                        class="inline-flex items-center justify-center px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200 text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    @lang('inventory::modules.stock.clearFilters')
                </button>
            @endif
        </div>
    </div>

    {{-- Summary view --}}
    @if($viewMode === 'summary')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                @lang('inventory::modules.consumption.item')
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                @lang('inventory::modules.consumption.report.beforeConsumption')
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                @lang('inventory::modules.consumption.report.consumed')
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                @lang('inventory::modules.consumption.report.afterConsumption')
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                @lang('inventory::modules.consumption.entries')
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                @lang('inventory::modules.consumption.report.dateRange')
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($summaryRows as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $row->item_name }}</div>
                                    @if(!empty($row->item_code))
                                        <div class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $row->item_code }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                    {{ number_format($row->opening, 2) }}
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $row->unit_symbol }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                                        - {{ number_format($row->consumed, 2) }}
                                        {{ $row->unit_symbol }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($row->closing, 2) }}
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $row->unit_symbol }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-700 dark:text-gray-300">
                                    {{ number_format($row->entries) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    {{ optional($row->first_date)->format('M d, Y') }} - {{ optional($row->last_date)->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                    @lang('inventory::modules.consumption.noEntries')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $summaryPaginator->links() }}
            </div>
        </div>

        {{-- Disposal summary section --}}
        <div class="mt-8">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">
                    @lang('inventory::modules.disposal.reportSection')
                </h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 ml-1">
                    {{ number_format($disposalTotals['disposed'], 2) }} @lang('inventory::modules.disposal.totalDisposedUnit')
                    &nbsp;&bull;&nbsp; {{ $disposalTotals['entries'] }} @lang('inventory::modules.disposal.entries')
                </span>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-red-50 dark:bg-red-900/20">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    @lang('inventory::modules.disposal.item')
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    @lang('inventory::modules.disposal.totalDisposed')
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    @lang('inventory::modules.disposal.entries')
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    @lang('inventory::modules.consumption.report.dateRange')
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($disposalSummaryRows as $row)
                                <tr class="hover:bg-red-50 dark:hover:bg-red-900/10">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $row->item_name }}</div>
                                        @if(!empty($row->item_code))
                                            <div class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $row->item_code }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                            - {{ number_format($row->disposed, 2) }} {{ $row->unit_symbol }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-700 dark:text-gray-300">
                                        {{ number_format($row->entries) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                        {{ optional($row->first_date)->format('M d, Y') }} - {{ optional($row->last_date)->format('M d, Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                        @lang('inventory::modules.disposal.noEntries')
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    {{ $disposalSummaryPaginator->links() }}
                </div>
            </div>
        </div>

    @else
        {{-- Detailed entry view --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.consumption.date')</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.consumption.item')</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.consumption.branch')</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.consumption.report.beforeConsumption')</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.consumption.report.consumed')</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.consumption.report.afterConsumption')</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang('inventory::modules.consumption.recordedBy')</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($detailRows as $row)
                            @php
                                $unitSymbol = optional($row->item?->unit)->symbol;
                                $before = $row->stock_before;
                                $after = $row->stock_after;
                            @endphp
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
                                    {{ $row->branch->name ?? '--' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                    @if($before !== null)
                                        {{ number_format((float) $before, 2) }}
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $unitSymbol }}</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">--</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                                        - {{ number_format((float) $row->quantity, 2) }}
                                        {{ $unitSymbol }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    @if($after !== null)
                                        {{ number_format((float) $after, 2) }}
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $unitSymbol }}</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">--</span>
                                    @endif
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
                {{ $detailRows->links() }}
            </div>
        </div>
    @endif

        {{-- Disposal detail section (shown in detail mode) --}}
    @if($viewMode === 'detail')
        <div class="mt-8">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">
                    @lang('inventory::modules.disposal.reportSection')
                </h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 ml-1">
                    {{ number_format($disposalTotals['disposed'], 2) }} @lang('inventory::modules.disposal.totalDisposedUnit')
                    &nbsp;&bull;&nbsp; {{ $disposalTotals['entries'] }} @lang('inventory::modules.disposal.entries')
                </span>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-red-50 dark:bg-red-900/20">
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
                            @forelse($disposalDetailRows as $row)
                                <tr class="hover:bg-red-50 dark:hover:bg-red-900/10">
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
                                            - {{ number_format((float) $row->quantity, 2) }} {{ optional($row->item?->unit)->symbol }}
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
                    {{ $disposalDetailRows->links() }}
                </div>
            </div>
        </div>
    @endif
</div>
