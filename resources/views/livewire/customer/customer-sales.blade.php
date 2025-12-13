<div class="space-y-6">
    <!-- Period Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <x-label for="period" :value="__('app.period')" />
                <x-select id="period" class="block mt-1 w-full" wire:model.live="period">
                    <option value="7">@lang('app.last7Days')</option>
                    <option value="30">@lang('app.last30Days')</option>
                    <option value="90">@lang('app.last90Days')</option>
                    <option value="365">@lang('app.lastYear')</option>
                    <option value="custom">@lang('app.custom')</option>
                </x-select>
            </div>

            @if($period === 'custom')
                <div>
                    <x-label for="startDate" :value="__('app.startDate')" />
                    <x-input id="startDate" 
                        type="date" 
                        class="block mt-1 w-full" 
                        wire:model.live="startDate" />
                </div>

                <div>
                    <x-label for="endDate" :value="__('app.endDate')" />
                    <x-input id="endDate" 
                        type="date" 
                        class="block mt-1 w-full" 
                        wire:model.live="endDate" />
                </div>
            @else
                <div class="md:col-span-2">
                    <x-label :value="__('app.dateRange')" />
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
            <p class="text-sm text-blue-600 dark:text-blue-400">@lang('modules.customer.total_sales')</p>
            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                {{ currency_format($totalSales, restaurant()->currency_id) }}
            </p>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
            <p class="text-sm text-green-600 dark:text-green-400">@lang('modules.order.totalOrder')</p>
            <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                {{ $totalOrders }}
            </p>
        </div>

        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
            <p class="text-sm text-purple-600 dark:text-purple-400">@lang('modules.customer.average_order_value')</p>
            <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                {{ currency_format($averageOrderValue, restaurant()->currency_id) }}
            </p>
        </div>

        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800">
            <p class="text-sm text-orange-600 dark:text-orange-400">@lang('modules.customer.last_order_date')</p>
            <p class="text-lg font-bold text-orange-900 dark:text-orange-100">
                {{ $lastOrder ? $lastOrder->date_time->format('M d, Y') : '—' }}
            </p>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                @lang('modules.customer.payment_summary')
            </h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">@lang('modules.customer.total_paid'):</span>
                    <span class="font-semibold text-green-600 dark:text-green-400">
                        {{ currency_format($totalPaid, restaurant()->currency_id) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">@lang('modules.customer.outstanding_balance'):</span>
                    <span class="font-semibold text-red-600 dark:text-red-400">
                        {{ currency_format($outstandingBalance, restaurant()->currency_id) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                @lang('modules.customer.sales_by_status')
            </h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">@lang('modules.order.paid'):</span>
                    <span class="font-semibold text-green-600 dark:text-green-400">
                        {{ currency_format($salesByStatus['paid'], restaurant()->currency_id) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">@lang('modules.order.paymentDue'):</span>
                    <span class="font-semibold text-yellow-600 dark:text-yellow-400">
                        {{ currency_format($salesByStatus['payment_due'], restaurant()->currency_id) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Items -->
    @if($topItems->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                @lang('modules.customer.top_items')
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                                @lang('app.item')
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                                @lang('modules.customer.quantity')
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                                @lang('modules.customer.amount')
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($topItems as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                    {{ $item['name'] }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">
                                    {{ $item['quantity'] }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">
                                    {{ currency_format($item['amount'], restaurant()->currency_id) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Sales Trend (Simple bar representation) -->
    @if($salesByDay->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                @lang('modules.customer.sales_trend')
            </h3>
            <div class="space-y-2">
                @php
                    $maxAmount = $salesByDay->max('amount');
                @endphp
                @foreach($salesByDay->take(14) as $day)
                    <div class="flex items-center space-x-4">
                        <div class="w-20 text-xs text-gray-600 dark:text-gray-400">
                            {{ $day['date'] }}
                        </div>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-6 relative">
                            <div class="bg-blue-600 dark:bg-blue-500 h-6 rounded-full flex items-center justify-end pr-2"
                                style="width: {{ $maxAmount > 0 ? ($day['amount'] / $maxAmount * 100) : 0 }}%">
                                <span class="text-xs text-white font-medium">
                                    {{ $day['count'] }} @lang('app.orders')
                                </span>
                            </div>
                        </div>
                        <div class="w-24 text-right text-sm font-medium text-gray-900 dark:text-white">
                            {{ currency_format($day['amount'], restaurant()->currency_id) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

