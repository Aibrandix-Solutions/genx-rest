<div
    x-data="{
        open: false,
        itemName: '',
        sales: [],
        show(name, sales) {
            this.itemName = name;
            this.sales = sales;
            this.open = true;
        },
        close() { this.open = false; }
    }"
    @keydown.escape.window="close()"
>
    {{-- ── Header ── --}}
    <div class="p-4 bg-white dark:bg-gray-800">
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">@lang('menu.menuItemReport')</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @lang('modules.report.menuItemReportMessage')
                @php
                    $formattedStartTime = \Carbon\Carbon::parse($startTime)->format('h:i A');
                    $formattedEndTime   = \Carbon\Carbon::parse($endTime)->format('h:i A');
                @endphp
                <strong>
                    ({{ $startDate === $endDate
                        ? __('modules.report.salesDataFor')   . " $startDate, "  . __('modules.report.timePeriod')        . " $formattedStartTime - $formattedEndTime"
                        : __('modules.report.salesDataFrom')  . " $startDate "   . __('app.to') . " $endDate, " . __('modules.report.timePeriodEachDay') . " $formattedStartTime - $formattedEndTime" }})
                </strong>
            </p>
        </div>

        {{-- ── Stat Cards ── --}}
        <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 md:grid-cols-3">
            <div class="p-4 bg-skin-base/10 rounded-xl shadow-sm border border-skin-base/30 dark:border-skin-base/40">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-skin-base">@lang('modules.report.sumOfTotalRevenue')</h3>
                    <div class="p-2 bg-skin-base/10 rounded-lg">
                        <svg class="w-4 h-4 text-skin-base" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-skin-base break-words">{{ currency_format($totalRevenue, restaurant()->currency_id) }}</p>
            </div>

            <div class="p-4 bg-emerald-50 dark:bg-emerald-900/10 rounded-xl shadow-sm border border-emerald-100 dark:border-emerald-800">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-emerald-600 dark:text-emerald-400">@lang('modules.report.totalQuantitySold')</h3>
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($totalQtySold) }}</p>
            </div>

            <div class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-xl shadow-sm border border-blue-100 dark:border-blue-800">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Menu Items</h3>
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ $items->total() }}</p>
            </div>
        </div>

        {{-- ── Filters ── --}}
        <div class="flex flex-wrap justify-between items-center gap-4 p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
            <div class="lg:flex items-center gap-2">
                <x-select wire:model.live="dateRangeType" wire:change="setDateRange" class="block w-full sm:w-fit">
                    <option value="today">@lang('app.today')</option>
                    <option value="yesterday">@lang('app.yesterday')</option>
                    <option value="currentWeek">@lang('app.currentWeek')</option>
                    <option value="lastWeek">@lang('app.lastWeek')</option>
                    <option value="last7Days">@lang('app.last7Days')</option>
                    <option value="currentMonth">@lang('app.currentMonth')</option>
                    <option value="lastMonth">@lang('app.lastMonth')</option>
                    <option value="currentYear">@lang('app.currentYear')</option>
                    <option value="lastYear">@lang('app.lastYear')</option>
                </x-select>

                <div id="date-range-picker" date-rangepicker class="flex items-center">
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20zM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2"/></svg>
                        </div>
                        <input id="datepicker-range-start" type="text" wire:model.change="startDate"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="@lang('app.selectStartDate')">
                    </div>
                    <span class="mx-3 text-gray-500 dark:text-gray-100">@lang('app.to')</span>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20zM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2"/></svg>
                        </div>
                        <input id="datepicker-range-end" type="text" wire:model.live="endDate"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="@lang('app.selectEndDate')">
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative w-28">
                        <div class="absolute inset-y-0 end-0 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" viewBox="0 0 15 15" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 7.5a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0m7 0V3h1v4.293l2.854 2.853-.708.708-3-3A.5.5 0 0 1 7 7.5" fill="currentColor"/></svg>
                        </div>
                        <x-input type="time" wire:model.live.debounce.500ms="startTime" />
                    </div>
                    <span class="text-gray-500 dark:text-gray-100">@lang('app.to')</span>
                    <div class="relative w-28">
                        <div class="absolute inset-y-0 end-0 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" viewBox="0 0 15 15" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 7.5a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0m7 0V3h1v4.293l2.854 2.853-.708.708-3-3A.5.5 0 0 1 7 7.5" fill="currentColor"/></svg>
                        </div>
                        <x-input type="time" wire:model.live.debounce.500ms="endTime" />
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/></svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="searchTerm"
                        class="block w-48 p-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="{{ __('app.search') }}...">
                </div>

                <select wire:model.live="filterCategoryId"
                    class="px-3 py-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                    @endforeach
                </select>

                @include('livewire.reports.partials.branch-filter')
            </div>
        </div>
    </div>

    {{-- ── Table ── --}}
    @php $menuItemColspan = 8 + (($showBranchColumn ?? false) ? 1 : 0); @endphp
    <div class="overflow-x-auto w-full -mx-4 px-4 sm:mx-0 sm:px-4 bg-white dark:bg-gray-800 p-4">
        <table class="min-w-full border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.menu.itemName')
                    </th>
                    @if($showBranchColumn ?? false)
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                        @lang('app.branch')
                    </th>
                    @endif
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                        Item Code
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.menu.categoryName')
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.menu.price')
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-600 uppercase dark:text-gray-300">
                        Available
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.report.quantitySold')
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.report.totalRevenue')
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-600 uppercase dark:text-gray-300">
                        @lang('app.action')
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @forelse($items as $item)
                @php
                    $sales    = $salesByItem[$item->id]    ?? null;
                    $detail   = $salesDetailByItem[$item->id] ?? collect();
                    $qtySold  = $sales?->qty_sold  ?? 0;
                    $revenue  = $sales?->revenue   ?? 0;
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                        {{ $item->item_name }}
                    </td>
                    @if($showBranchColumn ?? false)
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ $item->branch_name ?? '--' }}
                    </td>
                    @endif
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                        @if($item->item_code)
                            <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ $item->item_code }}</span>
                        @else
                            <span class="text-gray-300 dark:text-gray-600">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ $item->category_name ?: '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-center text-gray-900 dark:text-white">
                        {{ $item->price ? currency_format($item->price, restaurant()->currency_id) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($item->is_available)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Yes</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">No</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-center font-semibold {{ $qtySold > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500' }}">
                        {{ number_format($qtySold) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-semibold {{ $revenue > 0 ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                        {{ currency_format($revenue, restaurant()->currency_id) }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($qtySold > 0)
                            <button
                                type="button"
                                @click="show({{ Js::from($item->item_name) }}, {{ Js::from($detail) }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:text-indigo-300 dark:bg-indigo-900/30 dark:hover:bg-indigo-800/50 rounded-lg transition"
                                title="View sales"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                @lang('app.view')
                            </button>
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500 italic">No sales</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $menuItemColspan }}" class="px-4 py-6 text-sm text-center text-gray-500 dark:text-gray-400">
                        @lang('messages.noItemAdded')
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4">
        {{ $items->links() }}
    </div>

    {{-- ── Sales Detail Modal (Alpine — instant, no round-trip) ── --}}
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 bg-black/50"
            @click="close()"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <div class="relative z-10 w-full max-w-2xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl flex flex-col max-h-[80vh]"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="itemName"></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        <span x-text="sales.length"></span> sale transaction<span x-show="sales.length !== 1">s</span>
                    </p>
                </div>
                <button type="button" @click="close()"
                    class="p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-gray-200 transition"
                    aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="overflow-y-auto flex-1">
                <template x-if="sales.length > 0">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Order</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                <th class="px-4 py-2.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Qty</th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Unit Price</th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                <th class="px-4 py-2.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="(row, i) in sales" :key="i">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <td class="px-4 py-3 text-gray-400 dark:text-gray-500" x-text="i + 1"></td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white" x-text="'#' + row.order_number"></td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap" x-text="row.date"></td>
                                    <td class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-white" x-text="row.qty"></td>
                                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300" x-text="row.price"></td>
                                    <td class="px-4 py-3 text-right font-semibold text-indigo-600 dark:text-indigo-400" x-text="row.amount"></td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            @click="close(); $dispatch('showOrderDetail', { id: row.order_id, fromReport: true })"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:text-indigo-300 dark:bg-indigo-900/30 dark:hover:bg-indigo-800/50 rounded-lg transition"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            View
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </template>
                <template x-if="sales.length === 0">
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-500">
                        <p class="text-sm">No sales found for this item.</p>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-end flex-shrink-0">
                <button type="button" @click="close()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                    {{ __('app.close') }}
                </button>
            </div>
        </div>
    </div>

    @script
    <script>
        const s = document.getElementById('datepicker-range-start');
        if (s) s.addEventListener('changeDate', () => $wire.dispatch('setStartDate', { start: s.value }));
        const e = document.getElementById('datepicker-range-end');
        if (e) e.addEventListener('changeDate', () => $wire.dispatch('setEndDate', { end: e.value }));
    </script>
    @endscript
</div>
