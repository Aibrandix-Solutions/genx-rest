@php
    use App\Services\RoomChargeOrderSettlement;
@endphp

<div>
    <div class="p-4 bg-white dark:bg-gray-800">
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">@lang('menu.roomChargeOrdersReport')</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @lang('modules.report.roomChargeOrdersReportMessage')
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

        <div class="flex flex-wrap justify-between items-center gap-4 p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
            <div class="lg:flex items-center gap-2 w-full lg:w-auto">
                <x-select id="dateRangeType" class="block w-full sm:w-fit mb-2 lg:mb-0" wire:model.live="dateRangeType">
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

                <div id="date-range-picker" date-rangepicker class="flex items-center w-full lg:w-auto mt-2 lg:mt-0">
                    <div class="relative">
                        <input id="datepicker-range-start" type="text"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            wire:model.change="startDate" placeholder="@lang('app.selectStartDate')">
                    </div>
                    <span class="mx-4 text-gray-500 dark:text-gray-100">@lang('app.to')</span>
                    <div class="relative">
                        <input id="datepicker-range-end" type="text"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            wire:model.live="endDate" placeholder="@lang('app.selectEndDate')">
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-2 lg:mt-0 lg:ms-2">
                    <x-input type="time" wire:model.live.debounce.500ms="startTime" />
                    <span class="text-gray-500 dark:text-gray-100">@lang('app.to')</span>
                    <x-input type="time" wire:model.live.debounce.500ms="endTime" />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                <div class="relative w-full sm:w-64">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="block w-full p-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="{{ __('app.search') }}...">
                </div>

                <select wire:model.live="filterStatus"
                    class="px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600">
                    <option value="">@lang('modules.report.allSettlementStatuses')</option>
                    <option value="outstanding">@lang('modules.report.settlementOutstanding')</option>
                    <option value="partially_paid">@lang('modules.report.settlementPartiallyPaid')</option>
                    <option value="paid">@lang('modules.report.settlementPaid')</option>
                </select>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('reports.roomChargeOrders.print', $this->exportQuery()) }}" target="_blank"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        @lang('app.print')
                    </a>
                    <button type="button" wire:click="exportPdf"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        @lang('modules.report.exportPdf')
                    </button>
                    <button type="button" wire:click="exportExcel"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        @lang('modules.report.exportExcel')
                    </button>
                    <button type="button" wire:click="exportCsv"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        @lang('modules.report.exportCsv')
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto bg-white dark:bg-gray-800 p-4">
        <table class="min-w-full border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="p-4 text-xs font-medium tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.report.dateAndTime')
                    </th>
                    <th class="p-4 text-xs font-medium tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.order.orderNumber')
                    </th>
                    <th class="p-4 text-xs font-medium tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.report.room')
                    </th>
                    <th class="p-4 text-xs font-medium tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.report.guest')
                    </th>
                    <th class="p-4 text-xs font-medium tracking-wider text-right text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.order.amount')
                    </th>
                    <th class="p-4 text-xs font-medium tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                        @lang('app.status')
                    </th>
                    <th class="p-4 text-xs font-medium tracking-wider text-center text-gray-600 uppercase dark:text-gray-300">
                        @lang('app.action')
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($orders as $order)
                    @php
                        $settlementStatus = RoomChargeOrderSettlement::status($order);
                        $statusClasses = match ($settlementStatus) {
                            'paid' => 'text-green-700 dark:text-green-400',
                            'partially_paid' => 'text-orange-700 dark:text-orange-400',
                            default => 'text-red-700 dark:text-red-400',
                        };
                        $dotClasses = match ($settlementStatus) {
                            'paid' => 'bg-green-500',
                            'partially_paid' => 'bg-orange-500',
                            default => 'bg-red-500',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700" wire:key="room-charge-order-{{ $order->id }}">
                        <td class="p-4 text-sm text-gray-900 dark:text-white whitespace-nowrap">
                            {{ $order->date_time?->timezone(timezone())->translatedFormat('d M Y, h:i A') }}
                        </td>
                        <td class="p-4 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $order->show_formatted_order_number }}
                        </td>
                        <td class="p-4 text-sm text-gray-900 dark:text-white">
                            {{ $order->hotelReservation?->room?->room_number ?? '--' }}
                        </td>
                        <td class="p-4 text-sm text-gray-900 dark:text-white">
                            {{ $order->hotelReservation?->guest?->full_name ?? '--' }}
                        </td>
                        <td class="p-4 text-sm font-medium text-right text-gray-900 dark:text-white">
                            {{ currency_format($order->total, $currencyId) }}
                        </td>
                        <td class="p-4 text-sm">
                            <span class="inline-flex items-center gap-2 {{ $statusClasses }}">
                                <span class="inline-block w-2 h-2 rounded-full {{ $dotClasses }}"></span>
                                @if ($settlementStatus === 'paid')
                                    @lang('modules.report.settlementPaid')
                                @elseif ($settlementStatus === 'partially_paid')
                                    @lang('modules.report.settlementPartiallyPaid')
                                @else
                                    @lang('modules.report.settlementOutstanding')
                                @endif
                            </span>
                        </td>
                        <td class="p-4 text-sm text-center">
                            <button type="button" wire:click="showOrder({{ $order->id }})"
                                class="font-medium text-skin-base hover:underline">
                                @lang('app.view')
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-sm text-center text-gray-500 dark:text-gray-400">
                            @lang('messages.noItemAdded')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>

    <x-right-modal wire:model.live="showDetailModal" maxWidth="3xl">
        <x-slot name="title">
            @lang('modules.report.roomChargeOrderDetails')
        </x-slot>

        <x-slot name="content">
            @if ($selectedOrder)
                @php
                    $settlementStatus = RoomChargeOrderSettlement::status($selectedOrder);
                    $settledAmount = RoomChargeOrderSettlement::settledAmount($selectedOrder);
                    $outstandingAmount = RoomChargeOrderSettlement::outstandingAmount($selectedOrder);
                    $taxTotal = RoomChargeOrderSettlement::taxTotal($selectedOrder);
                @endphp

                <div class="space-y-6 text-sm text-gray-700 dark:text-gray-300">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <span class="font-semibold text-gray-900 dark:text-white">@lang('modules.report.restaurantBranch'):</span>
                            <span class="ms-1">{{ $selectedOrder->branch?->name ?? '--' }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-900 dark:text-white">@lang('modules.report.hotelBranch'):</span>
                            <span class="ms-1">{{ $selectedOrder->hotelReservation?->branch?->name ?? '--' }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-900 dark:text-white">@lang('modules.report.room'):</span>
                            <span class="ms-1">{{ $selectedOrder->hotelReservation?->room?->room_number ?? '--' }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-900 dark:text-white">@lang('modules.report.guest'):</span>
                            <span class="ms-1">{{ $selectedOrder->hotelReservation?->guest?->full_name ?? '--' }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="font-semibold text-gray-900 dark:text-white">@lang('modules.order.paymentMethod'):</span>
                            <span class="ms-1">@lang('modules.order.roomCharge')</span>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">
                            @lang('modules.report.orderedItems')
                        </h3>
                        <div class="overflow-x-auto border border-gray-200 rounded-lg dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">@lang('modules.menu.item')</th>
                                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-600 uppercase dark:text-gray-300">@lang('modules.order.qty')</th>
                                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-600 uppercase dark:text-gray-300">@lang('modules.report.unitPrice')</th>
                                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-600 uppercase dark:text-gray-300">@lang('modules.order.total')</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($selectedOrder->items as $item)
                                        @php
                                            $qty = max(1, (int) $item->quantity);
                                            $unitPrice = (float) $item->amount / $qty;
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                {{ $item->menuItem?->item_name ?? $item->item_name ?? '--' }}
                                                @if ($item->menuItemVariation?->variation)
                                                    <span class="text-xs text-gray-500">({{ $item->menuItemVariation->variation }})</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center text-gray-900 dark:text-white">{{ $qty }}</td>
                                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ currency_format($unitPrice, $currencyId) }}</td>
                                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ currency_format($item->amount, $currencyId) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="p-4 border border-gray-200 rounded-lg dark:border-gray-700">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span>@lang('modules.order.subTotal')</span>
                                <span>{{ currency_format($selectedOrder->sub_total, $currencyId) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>@lang('modules.order.discount')</span>
                                <span>{{ currency_format($selectedOrder->discount_amount ?? 0, $currencyId) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>@lang('modules.order.tax')</span>
                                <span>{{ currency_format($taxTotal, $currencyId) }}</span>
                            </div>
                            <div class="flex items-center justify-between pt-2 text-base font-semibold text-gray-900 border-t border-gray-200 dark:border-gray-700 dark:text-white">
                                <span>@lang('modules.report.grandTotal')</span>
                                <span>{{ currency_format($selectedOrder->total, $currencyId) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 border border-gray-200 rounded-lg dark:border-gray-700">
                        <h3 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">
                            @lang('modules.report.settlementDetails')
                        </h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span>@lang('modules.report.settlementStatus')</span>
                                <span class="font-medium">
                                    @if ($settlementStatus === 'paid')
                                        @lang('modules.report.settlementPaid')
                                    @elseif ($settlementStatus === 'partially_paid')
                                        @lang('modules.report.settlementPartiallyPaid')
                                    @else
                                        @lang('modules.report.settlementOutstanding')
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>@lang('modules.report.orderAmount')</span>
                                <span>{{ currency_format($selectedOrder->total, $currencyId) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>@lang('modules.report.settledAmount')</span>
                                <span>{{ currency_format($settledAmount, $currencyId) }}</span>
                            </div>
                            <div class="flex items-center justify-between font-semibold text-gray-900 dark:text-white">
                                <span>@lang('modules.report.outstandingAmount')</span>
                                <span>{{ currency_format($outstandingAmount, $currencyId) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-secondary-button wire:click="closeDetail" wire:loading.attr="disabled">
                            @lang('app.close')
                        </x-secondary-button>
                    </div>
                </div>
            @endif
        </x-slot>
    </x-right-modal>

    @script
    <script>
        const startInput = document.getElementById('datepicker-range-start');
        const endInput = document.getElementById('datepicker-range-end');

        if (startInput) {
            startInput.addEventListener('changeDate', (event) => {
                $wire.dispatch('setStartDate', { start: event.target.value });
            });
        }

        if (endInput) {
            endInput.addEventListener('changeDate', (event) => {
                $wire.dispatch('setEndDate', { end: event.target.value });
            });
        }
    </script>
    @endscript
</div>
