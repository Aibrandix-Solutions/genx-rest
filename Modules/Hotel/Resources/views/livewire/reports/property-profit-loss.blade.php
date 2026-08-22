<div>
    {{-- Header --}}
    <div class="p-4 bg-white dark:bg-gray-800">
        <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white mb-1">Property P&L</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Combined Profit & Loss for Hotel + Restaurant operations</p>

        {{-- Date Controls --}}
        <div class="mt-4 flex flex-wrap gap-3 items-end justify-between">
            <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Period</label>
                <select wire:model.live="dateRangeType"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="today">Today</option>
                    <option value="currentWeek">This Week</option>
                    <option value="currentMonth">This Month</option>
                    <option value="lastMonth">Last Month</option>
                    <option value="currentYear">This Year</option>
                    <option value="lastYear">Last Year</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">From</label>
                    <x-input type="date" wire:model.live="startDate" class="text-sm" />
                </div>
                <span class="text-gray-400 mt-5">→</span>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">To</label>
                    <x-input type="date" wire:model.live="endDate" class="text-sm" />
                </div>
            </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition disabled:opacity-60">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span wire:loading.remove wire:target="exportPdf">@lang('modules.report.exportPdf')</span>
                    <span wire:loading wire:target="exportPdf">Exporting...</span>
                </button>
                <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition disabled:opacity-60">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                    <span wire:loading.remove wire:target="exportExcel">@lang('modules.report.exportExcel')</span>
                    <span wire:loading wire:target="exportExcel">Exporting...</span>
                </button>
            </div>
        </div>
    </div>

    @php $d = $data; @endphp

    {{-- ─── NET PROFIT HERO (card style matching Finance Report) ─── --}}
    <div class="px-4 mt-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

            {{-- Net Profit --}}
            <div class="sm:col-span-1 bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm
                @if($d['netProfit'] >= 0) border-2 border-green-500 @else border-2 border-red-500 @endif">
                <div class="flex items-center gap-2 mb-2">
                    @if($d['netProfit'] >= 0)
                        <div class="w-9 h-9 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <span class="text-xs font-semibold text-green-700 dark:text-green-400 uppercase tracking-wide">Net Profit</span>
                    @else
                        <div class="w-9 h-9 rounded-lg bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                        </div>
                        <span class="text-xs font-semibold text-red-700 dark:text-red-400 uppercase tracking-wide">Net Loss</span>
                    @endif
                </div>
                <div class="text-3xl font-bold @if($d['netProfit'] >= 0) text-green-700 dark:text-green-300 @else text-red-700 dark:text-red-300 @endif">
                    {{ currency_format(abs($d['netProfit']), $currencyId) }}
                </div>
                <div class="text-sm font-medium mt-1 @if($d['netProfit'] >= 0) text-green-600 dark:text-green-400 @else text-red-600 dark:text-red-400 @endif">
                    {{ $d['netProfit'] >= 0 ? '▲ Profit' : '▼ Loss' }} · {{ $d['profitMargin'] }}% margin
                </div>
            </div>

            {{-- Total Revenue --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-green-200 dark:border-green-900 p-5 shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-9 h-9 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Total Revenue</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ currency_format($d['totalRevenue'], $currencyId) }}</div>
                <div class="text-xs text-gray-400 mt-1">Hotel + Restaurant combined</div>
            </div>

            {{-- Total Expenses --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-900 p-5 shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-9 h-9 rounded-lg bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Total Expenses</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ currency_format($d['totalExpenses'], $currencyId) }}</div>
                <div class="text-xs text-gray-400 mt-1">Hotel + Restaurant combined</div>
            </div>

        </div>
    </div>

    {{-- ─── REVENUE VS EXPENSES COLUMNS ─── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-4 mt-4">

        {{-- Revenue --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                Revenue Breakdown
            </h3>
            <div class="space-y-3">
                @php
                    $items = [
                        ['Room Nights',   $d['roomNightRevenue'],  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'],
                        ['Room Service',  $d['roomServiceSales'],  'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'],
                        ['Restaurant',    $d['restaurantSales'],   'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'],
                        ['Hotel Add-ons', $d['hotelAddOns'],       'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'],
                    ];
                @endphp
                @foreach($items as [$label, $amount, $badge])
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badge }}">{{ $label }}</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ currency_format($amount, $currencyId) }}</span>
                    </div>
                    @if($d['totalRevenue'] > 0)
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1 -mt-1 mb-1">
                            <div class="bg-green-500 h-1 rounded-full" style="width: {{ min(100, round(($amount / $d['totalRevenue']) * 100)) }}%"></div>
                        </div>
                    @endif
                @endforeach
                <div class="border-t dark:border-gray-600 pt-3 flex justify-between font-bold">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Total Revenue</span>
                    <span class="text-sm text-green-600 dark:text-green-400">{{ currency_format($d['totalRevenue'], $currencyId) }}</span>
                </div>
            </div>
        </div>

        {{-- Expenses --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                Expense Breakdown
            </h3>
            <div class="space-y-2">
                {{-- Hotel Expenses by Dept --}}
                @forelse($d['hotelExpByDept'] as $exp)
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400 capitalize">
                            Hotel · {{ str_replace('_', ' ', $exp->department) }}
                        </span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ currency_format($exp->total, $currencyId) }}</span>
                    </div>
                @empty
                    <div class="text-xs text-gray-400">No hotel expenses this period.</div>
                @endforelse

                @if($d['restaurantExpenses'] > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Restaurant Expenses</span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ currency_format($d['restaurantExpenses'], $currencyId) }}</span>
                    </div>
                @endif

                <div class="border-t dark:border-gray-600 pt-3 flex justify-between font-bold">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Total Expenses</span>
                    <span class="text-sm text-red-600 dark:text-red-400">{{ currency_format($d['totalExpenses'], $currencyId) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── 6-MONTH TREND ─── --}}
    <div class="px-4 mt-6 pb-8">
        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">6-Month Trend</h2>
        <div class="overflow-x-auto shadow rounded-xl">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Month</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Revenue</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Expenses</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Net Profit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Margin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($d['trend'] as $t)
                    @php $margin = $t['revenue'] > 0 ? round(($t['profit'] / $t['revenue']) * 100, 1) : 0; @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t['label'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-600 dark:text-green-400">{{ currency_format($t['revenue'], $currencyId) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-red-500 dark:text-red-400">{{ currency_format($t['expenses'], $currencyId) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold {{ $t['profit'] >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-600 dark:text-red-400' }}">
                            {{ currency_format($t['profit'], $currencyId) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <span @class(['px-2 py-0.5 rounded text-xs font-medium', 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $margin >= 0, 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' => $margin < 0])>
                                {{ $margin }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
