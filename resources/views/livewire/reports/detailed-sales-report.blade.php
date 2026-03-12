<div>
    <!-- Header Section -->
    <div class="p-4 bg-white dark:bg-gray-800">
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">@lang('menu.detailedSalesReport')</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @lang('modules.report.detailedSalesReportMessage')
                @php
                    $formattedStartTime = \Carbon\Carbon::parse($startTime)->format('h:i A');
                    $formattedEndTime = \Carbon\Carbon::parse($endTime)->format('h:i A');
                @endphp
                <strong>
                    ({{ $startDate === $endDate
                        ? __('modules.report.salesDataFor') . " $startDate, " . __('modules.report.timePeriod') . " $formattedStartTime - $formattedEndTime"
                        : __('modules.report.salesDataFrom') . " $startDate " . __('app.to') . " $endDate, " . __('modules.report.timePeriodEachDay') . " $formattedStartTime - $formattedEndTime" }})
                </strong>
            </p>
        </div>

        <!-- Filter Section -->
        <div class="flex flex-wrap justify-between items-center gap-4 p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
            <div class="lg:flex items-center mb-4 sm:mb-0 gap-2">
                <form  action="#" method="GET" class="lg:flex gap-2 items-center">

                    <div class="lg:flex gap-2 items-center">
                        <x-select id="dateRangeType" class="block w-full sm:w-fit mb-2 lg:mb-0" wire:model.live="dateRangeType" wire:change="setDateRange">
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

                        <div id="date-range-picker" date-rangepicker class="flex items-center w-full">
                            <div class="relative">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20zM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2"/></svg>
                                </div>
                                <input id="datepicker-range-start" name="start" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.change='startDate' placeholder="@lang('app.selectStartDate')">
                            </div>
                            <span class="mx-4 text-gray-500 dark:text-gray-100">@lang('app.to')</span>
                            <div class="relative">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20zM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2"/></svg>
                                </div>
                                <input id="datepicker-range-end" name="end" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.live='endDate' placeholder="@lang('app.selectEndDate')">
                            </div>
                        </div>

                        <div class="lg:flex items-center gap-2 ms-2">
                            <div class="w-full max-w-[7rem]">
                                <label for="start-time" class="sr-only">@lang('modules.reservation.timeStart'):</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 end-0 top-0 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" width="24" height="24" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 7.5a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0m7 0V3h1v4.293l2.854 2.853-.708.708-3-3A.5.5 0 0 1 7 7.5" fill="currentColor"/></svg>
                                    </div>
                                    <x-input id="start-time" type="time" wire:model.live.debounce.500ms="startTime" />
                                </div>
                            </div>
                            <span class="mx-2 text-gray-500 dark:text-gray-100 w-10 text-center">@lang('app.to')</span>
                            <div class="w-full max-w-[7rem]">
                                <label for="end-time" class="sr-only">@lang('modules.reservation.timeEnd'):</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 end-0 top-0 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" width="24" height="24" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 7.5a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0m7 0V3h1v4.293l2.854 2.853-.708.708-3-3A.5.5 0 0 1 7 7.5" fill="currentColor"/></svg>
                                    </div>
                                    <x-input id="end-time" type="time" wire:model.live.debounce.500ms="endTime" />
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="inline-flex items-center gap-2 w-full sm:w-auto ms-2">
                <a href="javascript:;" wire:click='exportReport'
                class="inline-flex items-center  w-1/2 px-3 py-2 text-sm font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 sm:w-auto dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7.414A2 2 0 0 0 15.414 6L12 2.586A2 2 0 0 0 10.586 2zm5 6a1 1 0 1 0-2 0v3.586l-1.293-1.293a1 1 0 1 0-1.414 1.414l3 3a1 1 0 0 0 1.414 0l3-3a1 1 0 0 0-1.414-1.414L11 11.586z" clip-rule="evenodd"/></svg>
                    @lang('app.export')
                </a>

                 <div class="relative w-full sm:w-48 md:w-64">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search" class="block w-full p-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="{{ __('app.search') }}...">
                </div>

                <select wire:model.live="selectedWaiter" wire:change="filterWaiter" class="px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-4 focus:ring-primary-300 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                    <option value="">@lang('app.reportByWaiter')</option>
                    @foreach($waiters ?? [] as $waiter)
                        <option value="{{ $waiter->id }}">{{ $waiter->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterPaymentMethod" class="px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-4 focus:ring-primary-300 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                    <option value="">@lang('modules.order.paymentMethod')</option>
                    @foreach($paymentMethods ?? [] as $method)
                        <option value="{{ $method }}">{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                    @endforeach
                    <option value="due">@lang('modules.order.due')</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="overflow-x-auto bg-white dark:bg-gray-800 p-4">
        <table class="min-w-full border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
            <tr>
                <th class="p-4 text-xs font-medium tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                @lang('modules.order.orderNumber')
                </th>
                <th class="p-4 text-xs font-medium tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                @lang('app.date')
                </th>
                <th class="p-4 text-xs font-medium tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                @lang('modules.customer.customerName')
                </th>
                <th class="p-4 text-xs font-medium tracking-wider text-center text-gray-600 uppercase dark:text-gray-300">
                @lang('modules.table.staff')
                </th>

                <th class="p-4 text-xs font-medium tracking-wider text-right text-gray-600 uppercase dark:text-gray-300">
                @lang('modules.order.subTotal')
                </th>

                <!-- Charges Column Group -->
                @if(count($charges) > 0)
                <th colspan="{{ count($charges) }}" class="p-4 text-xs font-medium tracking-wider text-center text-gray-600 uppercase dark:text-gray-300 bg-blue-50 dark:bg-blue-900/20">
                    @lang('modules.order.extraCharges')
                </th>
                @endif

                <!-- Taxes Column Group -->
                <th class="p-4 text-xs font-medium tracking-wider text-right text-gray-600 uppercase dark:text-gray-300 bg-red-50 dark:bg-red-900/20">
                    @lang('modules.order.tax')
                </th>

                <th class="p-4 text-xs font-medium tracking-wider text-right text-gray-600 uppercase dark:text-gray-300">
                @lang('modules.order.deliveryFee')
                </th>
                <th class="p-4 text-xs font-medium tracking-wider text-right text-gray-600 uppercase dark:text-gray-300">
                @lang('modules.order.discount')
                </th>
                <th   class="p-4 text-xs font-medium tracking-wider text-right text-gray-600 uppercase dark:text-gray-300">
                @lang('modules.order.tip')
                </th>
                <th class="p-4 text-xs font-bold tracking-wider text-right text-gray-600 uppercase dark:text-gray-300">
                @lang('modules.order.total')
                </th>
                 <th class="p-4 text-xs font-medium tracking-wider text-center text-gray-600 uppercase dark:text-gray-300">
                @lang('modules.order.paymentMethod')
                </th>
            </tr>
            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>

                <!-- Charges Subheaders -->
                @foreach ($charges as $charge)
                <th class="p-4 text-xs font-medium tracking-wider text-right text-gray-600 uppercase dark:text-gray-300 bg-blue-50 dark:bg-blue-900/20">
                    {{ $charge->charge_name }}
                </th>
                @endforeach

                <!-- Tax Subheader -->
                 <th class="bg-red-50 dark:bg-red-900/20"></th>

                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
            @forelse ($orders as $order)
            @php
                $orderCharges = [];
                foreach ($charges as $charge) {
                    $amount = 0;
                    // Calculate charge for this specific order
                    // Since we are iterating orders, we can check order_charges table for this order
                    // But we don't have that relationship loaded efficiently.
                    // Let's re-use the logic if we can, or load it.
                    // For simplicity/performance in this view, we might need to load order charges.
                    // A better way: add 'charges' relationship to Order model or load it in query.
                    // Assuming we don't have it, let's do a quick query or fallback to 0 if not critical for row-level detail.
                    // Ideally we should eager load charges.
                    $chargeAmount = \Illuminate\Support\Facades\DB::table('order_charges')
                            ->where('order_id', $order->id)
                            ->where('charge_id', $charge->id)
                            ->join('restaurant_charges', 'order_charges.charge_id', '=', 'restaurant_charges.id')
                            ->value(DB::raw('CASE WHEN restaurant_charges.charge_type = "percent"
                                THEN (restaurant_charges.charge_value / 100) * '.$order->sub_total.'
                                ELSE restaurant_charges.charge_value END'));
                    $orderCharges[$charge->id] = $chargeAmount ?? 0;
                }
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="p-4 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
                <a href="javascript:;" wire:click="$dispatch('showOrderDetail', { id: {{ $order->id }} })" class="text-blue-600 hover:underline">
                    {{ $order->order_number }}
                </a>
                </td>
                <td class="p-4 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
                {{ $order->date_time->format('M d, Y h:i A') }}
                </td>
                <td class="p-4 text-sm text-gray-900 dark:text-white whitespace-nowrap">
                {{ $order->customer->name ?? '--' }}
                </td>
                <td class="p-4 text-sm text-center text-gray-900 dark:text-white">
                {{ $order->waiter->name ?? '--' }}
                </td>

                <td class="p-4 text-sm text-right text-gray-900 dark:text-white">
                {{ currency_format($order->sub_total, $currencyId) }}
                </td>

                @foreach ($charges as $charge)
                <td class="p-4 text-sm font-normal text-right text-gray-900 dark:text-gray-100 bg-blue-50/50 dark:bg-blue-900/10">
                {{ currency_format($orderCharges[$charge->id], $currencyId) }}
                </td>
                @endforeach

                <td class="p-4 text-sm font-normal text-right text-gray-900 dark:text-gray-100 bg-red-50/50 dark:bg-red-900/10">
                    {{ currency_format($order->total_tax, $currencyId) }}
                </td>

                <td class="p-4 text-sm text-right text-gray-900 dark:text-white ">
                    {{ currency_format($order->delivery_fee, $currencyId) }}
                </td>
                <td class="p-4 text-sm text-right text-gray-900 dark:text-white">
                    {{ currency_format($order->discount_amount, $currencyId) }}
                </td>
                <td class="p-4 text-sm text-right text-gray-900 dark:text-white">
                {{ currency_format($order->tip_amount, $currencyId) }}
                </td>
                <td class="p-4 text-sm font-bold text-right text-gray-900 dark:text-white">
                {{ currency_format($order->total, $currencyId) }}
                </td>
                 <td class="p-4 text-sm text-center text-gray-900 dark:text-white">
                    @if($order->payments->isNotEmpty())
                         <span class="capitalize">{{ $order->payments->first()->payment_method }}</span>
                    @else
                        <span class="text-red-500">@lang('modules.order.due')</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ 10 + count($charges) }}" class="p-4 text-sm text-center text-gray-500 dark:text-gray-400">
                @lang('messages.noItemAdded')
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">
        {{ $orders->links() }}
    </div>

    @script
    <script>
        const datepickerEl1 = document.getElementById('datepicker-range-start');

        if (datepickerEl1) {
            datepickerEl1.addEventListener('changeDate', (event) => {
                $wire.dispatch('setStartDate', { start: datepickerEl1.value });
            });
        }

        const datepickerEl2 = document.getElementById('datepicker-range-end');
        if (datepickerEl2) {
            datepickerEl2.addEventListener('changeDate', (event) => {
                $wire.dispatch('setEndDate', { end: datepickerEl2.value });
            });
        }
    </script>
    @endscript
</div>

