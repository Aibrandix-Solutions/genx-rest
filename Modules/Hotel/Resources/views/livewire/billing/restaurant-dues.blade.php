<div>
    <div class="p-4 bg-white border-b dark:bg-gray-800 dark:border-gray-700">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">@lang('hotel::modules.restaurantDues.title')</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('hotel::modules.restaurantDues.subtitle')</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="window.print()"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                    @lang('app.print')
                </button>
                <button type="button" wire:click="exportPdf"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                    @lang('modules.report.exportPdf')
                </button>
                <button type="button" wire:click="exportExcel"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                    @lang('modules.report.exportExcel')
                </button>
                <button type="button" wire:click="openAddPaymentModal" @disabled(empty($reportRows) || !user_can('process_hotel_payment'))
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    @lang('hotel::modules.restaurantDues.addPayment')
                </button>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="mt-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 px-4">
        <nav class="flex gap-0 -mb-px overflow-x-auto" aria-label="Restaurant Dues Tabs">
            <button wire:click="$set('activeTab', 'report')"
                class="inline-flex items-center gap-2 py-3 px-4 text-sm font-medium border-b-2 whitespace-nowrap transition {{ $activeTab === 'report' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                @lang('hotel::modules.restaurantDues.reportTab')
            </button>
            <button wire:click="$set('activeTab', 'ledger')"
                class="inline-flex items-center gap-2 py-3 px-4 text-sm font-medium border-b-2 whitespace-nowrap transition {{ $activeTab === 'ledger' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                @lang('hotel::modules.restaurantDues.ledgerTab')
            </button>
        </nav>
    </div>

    <div class="p-4 bg-gray-50 dark:bg-gray-900 min-h-[calc(100vh-220px)]">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4">
                <div class="text-xs uppercase text-gray-500 dark:text-gray-400">@lang('hotel::modules.restaurantDues.totalCharges')</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ currency_format($summary['total_charges'], restaurant()->currency_id) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4">
                <div class="text-xs uppercase text-gray-500 dark:text-gray-400">@lang('hotel::modules.restaurantDues.totalPaid')</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ currency_format($summary['total_paid'], restaurant()->currency_id) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4">
                <div class="text-xs uppercase text-gray-500 dark:text-gray-400">@lang('hotel::modules.restaurantDues.outstandingAmount')</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ currency_format($summary['outstanding_amount'], restaurant()->currency_id) }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4 mb-4 grid grid-cols-1 md:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">@lang('hotel::modules.restaurantDues.restaurantBranch')</label>
                <select wire:model.live="restaurantBranchId" class="w-full rounded-lg border-gray-300 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">@lang('app.all')</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">@lang('hotel::modules.restaurantDues.roomNumber')</label>
                <x-input wire:model.live.debounce.400ms="roomNumber" />
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">@lang('hotel::modules.restaurantDues.guest')</label>
                <x-input wire:model.live.debounce.400ms="guest" />
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">@lang('app.from')</label>
                <x-input type="date" wire:model.live="dateFrom" />
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">@lang('app.to')</label>
                <x-input type="date" wire:model.live="dateTo" />
            </div>
            @if($activeTab === 'report')
            <div>
                <label class="block text-xs text-gray-500 mb-1">@lang('hotel::modules.restaurantDues.settlementStatus')</label>
                <select wire:model.live="settlementStatus" class="w-full rounded-lg border-gray-300 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">@lang('app.all')</option>
                    <option value="outstanding">@lang('hotel::modules.restaurantDues.statusOutstanding')</option>
                    <option value="partially_paid">@lang('hotel::modules.restaurantDues.statusPartiallyPaid')</option>
                    <option value="paid">@lang('hotel::modules.restaurantDues.statusPaid')</option>
                </select>
            </div>
            @endif
        </div>

        @if($activeTab === 'report')
            <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left">@lang('hotel::modules.restaurantDues.roomNumber')</th>
                            <th class="px-4 py-3 text-left">@lang('hotel::modules.restaurantDues.guest')</th>
                            <th class="px-4 py-3 text-left">@lang('hotel::modules.restaurantDues.restaurantBranch')</th>
                            <th class="px-4 py-3 text-left">@lang('hotel::modules.restaurantDues.orders')</th>
                            <th class="px-4 py-3 text-right">@lang('hotel::modules.restaurantDues.totalCharges')</th>
                            <th class="px-4 py-3 text-right">@lang('hotel::modules.restaurantDues.totalPaid')</th>
                            <th class="px-4 py-3 text-right">@lang('hotel::modules.restaurantDues.outstandingAmount')</th>
                            <th class="px-4 py-3 text-left">@lang('hotel::modules.restaurantDues.settlementStatus')</th>
                            <th class="px-4 py-3 text-left">@lang('app.action')</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($reportRows as $row)
                            <tr>
                                <td class="px-4 py-3">{{ $row['room_number'] }}</td>
                                <td class="px-4 py-3">{{ $row['guest_name'] }}</td>
                                <td class="px-4 py-3">{{ $row['restaurant_branch_name'] }}</td>
                                <td class="px-4 py-3">{{ $row['orders_count'] }}</td>
                                <td class="px-4 py-3 text-right">{{ currency_format($row['total_charges'], restaurant()->currency_id) }}</td>
                                <td class="px-4 py-3 text-right">{{ currency_format($row['total_paid'], restaurant()->currency_id) }}</td>
                                <td class="px-4 py-3 text-right">{{ currency_format($row['outstanding_amount'], restaurant()->currency_id) }}</td>
                                <td class="px-4 py-3">
                                    @if($row['status'] === 'paid')
                                        <span class="px-2 py-1 rounded bg-green-100 text-green-700 text-xs">@lang('hotel::modules.restaurantDues.statusPaid')</span>
                                    @elseif($row['status'] === 'partially_paid')
                                        <span class="px-2 py-1 rounded bg-orange-100 text-orange-700 text-xs">@lang('hotel::modules.restaurantDues.statusPartiallyPaid')</span>
                                    @else
                                        <span class="px-2 py-1 rounded bg-red-100 text-red-700 text-xs">@lang('hotel::modules.restaurantDues.statusOutstanding')</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 space-x-2">
                                    <button type="button" wire:click="openDetails({{ $row['reservation_id'] }}, {{ $row['restaurant_branch_id'] }})" class="text-indigo-600 hover:underline dark:text-indigo-400">@lang('app.view')</button>
                                    @if(user_can('process_hotel_payment') && $row['outstanding_amount'] > 0)
                                        <button type="button" wire:click="openPaymentModal({{ $row['reservation_id'] }}, {{ $row['restaurant_branch_id'] }})" class="text-green-600 hover:underline dark:text-green-400">@lang('hotel::modules.restaurantDues.addPayment')</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-gray-500">@lang('messages.noItemAdded')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4">
                    <div class="text-xs uppercase text-gray-500 dark:text-gray-400">@lang('hotel::modules.restaurantDues.totalDebit')</div>
                    <div class="text-2xl font-bold mt-1">{{ currency_format($ledgerSummary['total_debit'], restaurant()->currency_id) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4">
                    <div class="text-xs uppercase text-gray-500 dark:text-gray-400">@lang('hotel::modules.restaurantDues.totalCredit')</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ currency_format($ledgerSummary['total_credit'], restaurant()->currency_id) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4">
                    <div class="text-xs uppercase text-gray-500 dark:text-gray-400">@lang('hotel::modules.restaurantDues.outstandingBalance')</div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ currency_format($ledgerSummary['outstanding_balance'], restaurant()->currency_id) }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left">@lang('app.date')</th>
                            <th class="px-4 py-3 text-left">@lang('app.description')</th>
                            <th class="px-4 py-3 text-right">@lang('hotel::modules.restaurantDues.debit')</th>
                            <th class="px-4 py-3 text-right">@lang('hotel::modules.restaurantDues.credit')</th>
                            <th class="px-4 py-3 text-right">@lang('hotel::modules.restaurantDues.balance')</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($ledgerRows as $row)
                            <tr>
                                <td class="px-4 py-3">{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                                <td class="px-4 py-3">{{ $row['description'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['debit'] > 0 ? currency_format($row['debit'], restaurant()->currency_id) : '-' }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['credit'] > 0 ? currency_format($row['credit'], restaurant()->currency_id) : '-' }}</td>
                                <td class="px-4 py-3 text-right">{{ currency_format($row['balance'], restaurant()->currency_id) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-gray-500">@lang('messages.noItemAdded')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <x-right-modal wire:model.live="showDetailsDrawer" maxWidth="3xl">
        <x-slot name="title">@lang('hotel::modules.restaurantDues.detailsTitle')</x-slot>
        <x-slot name="content">
            @if($details['reservation'])
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <div><span class="font-semibold">@lang('hotel::modules.restaurantDues.roomNumber'):</span> {{ $details['reservation']->room?->room_number ?? '--' }}</div>
                        <div><span class="font-semibold">@lang('hotel::modules.restaurantDues.guest'):</span> {{ $details['reservation']->guest?->full_name ?? '--' }}</div>
                        <div><span class="font-semibold">@lang('hotel::modules.restaurantDues.hotelBranch'):</span> {{ $details['reservation']->branch?->name ?? '--' }}</div>
                    </div>

                    <div class="overflow-x-auto border rounded-lg dark:border-gray-700">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-3 py-2 text-left">@lang('app.date')</th>
                                    <th class="px-3 py-2 text-left">@lang('modules.order.orderNumber')</th>
                                    <th class="px-3 py-2 text-right">@lang('modules.order.subTotal')</th>
                                    <th class="px-3 py-2 text-right">@lang('modules.order.discount')</th>
                                    <th class="px-3 py-2 text-right">@lang('modules.order.tax')</th>
                                    <th class="px-3 py-2 text-right">@lang('modules.order.amount')</th>
                                    <th class="px-3 py-2 text-right">@lang('hotel::modules.restaurantDues.totalPaid')</th>
                                    <th class="px-3 py-2 text-right">@lang('hotel::modules.restaurantDues.outstandingAmount')</th>
                                    <th class="px-3 py-2 text-left">@lang('hotel::modules.restaurantDues.settlementStatus')</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($details['orders'] as $order)
                                    <tr>
                                        <td class="px-3 py-2">{{ $order['date']?->format('d M Y') }}</td>
                                        <td class="px-3 py-2">{{ $order['order_number'] }}</td>
                                        <td class="px-3 py-2 text-right">{{ currency_format($order['subtotal'], restaurant()->currency_id) }}</td>
                                        <td class="px-3 py-2 text-right">{{ currency_format($order['discount'], restaurant()->currency_id) }}</td>
                                        <td class="px-3 py-2 text-right">{{ currency_format($order['tax'], restaurant()->currency_id) }}</td>
                                        <td class="px-3 py-2 text-right">{{ currency_format($order['amount'], restaurant()->currency_id) }}</td>
                                        <td class="px-3 py-2 text-right">{{ currency_format($order['paid'], restaurant()->currency_id) }}</td>
                                        <td class="px-3 py-2 text-right">{{ currency_format($order['outstanding'], restaurant()->currency_id) }}</td>
                                        <td class="px-3 py-2">
                                            @if($order['status'] === 'paid')
                                                <span class="px-2 py-1 rounded bg-green-100 text-green-700 text-xs">@lang('hotel::modules.restaurantDues.statusPaid')</span>
                                            @elseif($order['status'] === 'partially_paid')
                                                <span class="px-2 py-1 rounded bg-orange-100 text-orange-700 text-xs">@lang('hotel::modules.restaurantDues.statusPartiallyPaid')</span>
                                            @else
                                                <span class="px-2 py-1 rounded bg-red-100 text-red-700 text-xs">@lang('hotel::modules.restaurantDues.statusOutstanding')</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm font-semibold">
                        <div>@lang('hotel::modules.restaurantDues.totalCharges'): {{ currency_format($details['totals']['charges'], restaurant()->currency_id) }}</div>
                        <div>@lang('hotel::modules.restaurantDues.totalPaid'): {{ currency_format($details['totals']['paid'], restaurant()->currency_id) }}</div>
                        <div>@lang('hotel::modules.restaurantDues.outstandingAmount'): {{ currency_format($details['totals']['outstanding'], restaurant()->currency_id) }}</div>
                    </div>
                </div>
            @endif
        </x-slot>
    </x-right-modal>

    <x-right-modal wire:model.live="showPaymentModal" maxWidth="lg">
        <x-slot name="title">@lang('hotel::modules.restaurantDues.addPayment')</x-slot>
        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm mb-1">@lang('hotel::modules.restaurantDues.restaurantBranch')</label>
                    <select wire:model="paymentRestaurantBranchId" class="w-full rounded-lg border-gray-300 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">@lang('app.select') @lang('hotel::modules.restaurantDues.restaurantBranch')</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @error('paymentRestaurantBranchId') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm mb-1">@lang('hotel::modules.restaurantDues.roomNumber')</label>
                        <x-input :value="$paymentRoom" disabled />
                    </div>
                    <div>
                        <label class="block text-sm mb-1">@lang('hotel::modules.restaurantDues.guest')</label>
                        <x-input :value="$paymentGuest" disabled />
                    </div>
                </div>

                <div>
                    <label class="block text-sm mb-1">@lang('hotel::modules.restaurantDues.outstandingAmount')</label>
                    <x-input :value="currency_format($paymentOutstandingAmount, restaurant()->currency_id)" disabled />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm mb-1">@lang('hotel::modules.restaurantDues.paymentDate')</label>
                        <x-input type="date" wire:model="paymentDate" />
                        @error('paymentDate') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm mb-1">@lang('hotel::modules.restaurantDues.paymentAmount')</label>
                        <x-input type="number" step="0.01" min="0" wire:model="paymentAmount" />
                        @error('paymentAmount') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm mb-1">@lang('hotel::modules.restaurantDues.paymentMethod')</label>
                        <select wire:model="paymentMethod" class="w-full rounded-lg border-gray-300 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="cash">@lang('modules.order.cash')</option>
                            <option value="card">@lang('modules.order.card')</option>
                            <option value="upi">@lang('modules.order.upi')</option>
                            <option value="bank_transfer">@lang('modules.order.bank_transfer')</option>
                            <option value="other">@lang('modules.order.other')</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">@lang('hotel::modules.restaurantDues.referenceNumber')</label>
                        <x-input wire:model="paymentReference" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm mb-1">@lang('hotel::modules.restaurantDues.remarks')</label>
                    <textarea wire:model="paymentRemarks" rows="3" class="w-full rounded-lg border-gray-300 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closePaymentModal"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        @lang('app.cancel')
                    </button>
                    <button type="button" wire:click="savePayment" wire:loading.attr="disabled" @disabled($paymentSubmitInProgress)
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        @lang('app.save')
                    </button>
                </div>
            </div>
        </x-slot>
    </x-right-modal>
</div>
