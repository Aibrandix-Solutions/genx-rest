<div>
    {{-- Header --}}
    <div class="p-4 bg-white block dark:bg-gray-800">
        <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white mb-1">Unified Finance Report</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Combined view of Hotel + Restaurant revenue & expenses</p>

        {{-- Date Range Controls --}}
        <div class="mt-4 flex flex-wrap gap-3 items-end justify-between">
            <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Period</label>
                <select wire:model.live="dateRangeType"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="currentWeek">This Week</option>
                    <option value="last7Days">Last 7 Days</option>
                    <option value="currentMonth">This Month</option>
                    <option value="lastMonth">Last Month</option>
                    <option value="currentYear">This Year</option>
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

    {{-- ─────────────── REVENUE SUMMARY CARDS ─────────────── --}}
    <div class="px-4 pb-2 mt-4">
        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Revenue Breakdown</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

            {{-- Restaurant Sales --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-blue-100 dark:border-blue-900 p-4 shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2 5h14M7 13v4h10v-4"/></svg>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Restaurant Sales</span>
                </div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ currency_format($summary['restaurantSales'], $currencyId) }}</div>
                <div class="text-xs text-gray-400 mt-1">Dine-in, delivery & pickup</div>
            </div>

            {{-- Room Service --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-purple-100 dark:border-purple-900 p-4 shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Room Service</span>
                </div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ currency_format($summary['roomServiceSales'], $currencyId) }}</div>
                <div class="text-xs text-gray-400 mt-1">In-room F&B orders</div>
            </div>

            {{-- Room Night Revenue --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-green-100 dark:border-green-900 p-4 shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Room Revenue</span>
                </div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ currency_format($summary['roomNightRevenue'], $currencyId) }}</div>
                <div class="text-xs text-gray-400 mt-1">Nightly room charges</div>
            </div>

            {{-- Hotel Add-ons --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-orange-100 dark:border-orange-900 p-4 shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Hotel Add-ons</span>
                </div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ currency_format($summary['hotelAddOns'], $currencyId) }}</div>
                <div class="text-xs text-gray-400 mt-1">Minibar, laundry, service</div>
            </div>
        </div>

        {{-- Total Revenue Banner --}}
        <div class="mt-4 bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-800 dark:to-indigo-800 rounded-xl p-4 text-white flex items-center justify-between">
            <div>
                <div class="text-xs font-medium opacity-80 uppercase tracking-wide">Total Property Revenue</div>
                <div class="text-3xl font-bold mt-1">{{ currency_format($summary['totalRevenue'], $currencyId) }}</div>
            </div>
            <svg class="w-10 h-10 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
    </div>

    {{-- ─────────────── PAYMENTS & EXPENSES ROW ─────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-4 mt-4">

        {{-- Payments Received --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Payments Collected</div>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Hotel Payments</span>
                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">{{ currency_format($summary['hotelPaymentsReceived'], $currencyId) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Restaurant Payments</span>
                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">{{ currency_format($summary['restaurantPaymentsReceived'], $currencyId) }}</span>
                </div>
                @if($summary['hotelRefunds'] > 0)
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Hotel Refunds</span>
                    <span class="text-sm font-semibold text-red-500 dark:text-red-400">-{{ currency_format($summary['hotelRefunds'], $currencyId) }}</span>
                </div>
                @endif
                <div class="border-t dark:border-gray-700 pt-2 mt-2 flex justify-between items-center">
                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Total Collected</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ currency_format($summary['totalCollected'], $currencyId) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Expenses --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Hotel Expenses</div>
            @if($summary['hotelExpensesByDept']->isNotEmpty())
                <div class="space-y-1">
                    @foreach($summary['hotelExpensesByDept'] as $dept => $amount)
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $dept) }}</span>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ currency_format($amount, $currencyId) }}</span>
                        </div>
                    @endforeach
                    <div class="border-t dark:border-gray-700 pt-2 mt-2 flex justify-between items-center">
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Total Expenses</span>
                        <span class="text-sm font-bold text-red-600 dark:text-red-400">{{ currency_format($summary['hotelExpenses'], $currencyId) }}</span>
                    </div>
                </div>
            @else
                <div class="text-xs text-gray-400 dark:text-gray-500">No expenses recorded for this period.</div>
                <div class="mt-2"><a href="{{ route('hotel.expenses') }}" class="text-xs text-blue-600 hover:underline dark:text-blue-400">Add expenses →</a></div>
            @endif
        </div>

        {{-- Outstanding --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-yellow-200 dark:border-yellow-900 p-4 shadow-sm">
            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Hotel Outstanding</div>
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ currency_format($summary['hotelOutstanding'], $currencyId) }}</div>
            <div class="text-xs text-gray-400 mt-1">Pending balances on active reservations</div>
            <a href="{{ route('hotel.billing') }}" class="mt-3 inline-block text-xs text-yellow-600 hover:underline dark:text-yellow-400">View Billing →</a>
        </div>
    </div>

    {{-- ─────────────── DAILY BREAKDOWN TABLE ─────────────── --}}
    <div class="px-4 mt-6 pb-8">
        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Daily Breakdown</h2>
        @if($dailyBreakdown->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center text-gray-400 dark:text-gray-500 text-sm">
                No transactions found for this period.
            </div>
        @else
        <div class="overflow-x-auto shadow rounded-xl border border-gray-200 dark:border-gray-600">
            <table class="min-w-full w-full table-fixed border-collapse bg-white dark:bg-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase dark:text-gray-300 border border-gray-200 dark:border-gray-600">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase dark:text-gray-300 border border-gray-200 dark:border-gray-600">Restaurant</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase dark:text-gray-300 border border-gray-200 dark:border-gray-600">Room Service</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase dark:text-gray-300 border border-gray-200 dark:border-gray-600">Hotel Charges</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase dark:text-gray-300 border border-gray-200 dark:border-gray-600">Total Revenue</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase dark:text-gray-300 border border-gray-200 dark:border-gray-600">Expenses</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase dark:text-gray-300 border border-gray-200 dark:border-gray-600">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dailyBreakdown as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap font-medium border border-gray-200 dark:border-gray-600">
                            {{ \Carbon\Carbon::parse($row['day'])->format('D, M d') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-600">
                            {{ $row['restaurant'] > 0 ? currency_format($row['restaurant'], $currencyId) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-purple-600 dark:text-purple-400 border border-gray-200 dark:border-gray-600">
                            {{ $row['room_service'] > 0 ? currency_format($row['room_service'], $currencyId) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-green-600 dark:text-green-400 border border-gray-200 dark:border-gray-600">
                            {{ $row['hotel_charges'] > 0 ? currency_format($row['hotel_charges'], $currencyId) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600">
                            {{ currency_format($row['total_revenue'], $currencyId) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-red-500 dark:text-red-400 border border-gray-200 dark:border-gray-600">
                            {{ $row['expenses'] > 0 ? currency_format($row['expenses'], $currencyId) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-bold whitespace-nowrap border border-gray-200 dark:border-gray-600
                            {{ $row['net'] >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-600 dark:text-red-400' }}">
                            {{ currency_format($row['net'], $currencyId) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-700 font-semibold">
                    <tr>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-300 uppercase border border-gray-200 dark:border-gray-600">Total</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">{{ currency_format($dailyBreakdown->sum('restaurant'), $currencyId) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-purple-600 dark:text-purple-400 border border-gray-200 dark:border-gray-600">{{ currency_format($dailyBreakdown->sum('room_service'), $currencyId) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-600 dark:text-green-400 border border-gray-200 dark:border-gray-600">{{ currency_format($dailyBreakdown->sum('hotel_charges'), $currencyId) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600">{{ currency_format($dailyBreakdown->sum('total_revenue'), $currencyId) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-red-500 dark:text-red-400 border border-gray-200 dark:border-gray-600">{{ currency_format($dailyBreakdown->sum('expenses'), $currencyId) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold border border-gray-200 dark:border-gray-600
                            {{ $dailyBreakdown->sum('net') >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-600' }}">
                            {{ currency_format($dailyBreakdown->sum('net'), $currencyId) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>
