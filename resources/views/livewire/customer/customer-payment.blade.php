<div class="space-y-6">
    <!-- Summary Card -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-blue-600 dark:text-blue-400">@lang('modules.customer.total_outstanding')</p>
                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                    {{ currency_format($totalOutstanding, restaurant()->currency_id) }}
                </p>
            </div>
            <div>
                <p class="text-sm text-blue-600 dark:text-blue-400">@lang('modules.customer.selected_amount')</p>
                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                    {{ currency_format($paymentAmount, restaurant()->currency_id) }}
                </p>
            </div>
        </div>
    </div>

    @if(count($outstandingOrders) > 0)
        <!-- Outstanding Orders -->
        <div>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    @lang('modules.customer.outstanding_orders')
                </h3>
                <div class="space-x-2">
                    <button wire:click="selectAll" 
                        class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">
                        @lang('app.selectAll')
                    </button>
                    <button wire:click="deselectAll" 
                        class="text-xs text-gray-600 hover:text-gray-800 dark:text-gray-400">
                        @lang('app.clear')
                    </button>
                </div>
            </div>

            <div class="space-y-3 max-h-96 overflow-y-auto">
                @foreach($outstandingOrders as $order)
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-3 flex-1">
                                <input type="checkbox" 
                                    wire:model.live="selectedOrders" 
                                    value="{{ $order['id'] }}"
                                    class="mt-1 w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ $order['order_number'] }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $order['date'] }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">@lang('modules.order.total'):</span>
                                            <span class="font-medium text-gray-900 dark:text-white">
                                                {{ currency_format($order['total'], restaurant()->currency_id) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">@lang('modules.customer.paid'):</span>
                                            <span class="font-medium text-green-600 dark:text-green-400">
                                                {{ currency_format($order['paid'], restaurant()->currency_id) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">@lang('modules.customer.outstanding'):</span>
                                            <span class="font-medium text-red-600 dark:text-red-400">
                                                {{ currency_format($order['outstanding'], restaurant()->currency_id) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Payment Form -->
        @if(count($selectedOrders) > 0)
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    @lang('modules.customer.payment_details')
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-label for="paymentMethod" :value="__('modules.order.paymentMethod')" />
                        <x-select id="paymentMethod" class="block mt-1 w-full" wire:model="paymentMethod">
                            <option value="cash">@lang('modules.order.cash')</option>
                            <option value="card">@lang('modules.order.card')</option>
                            <option value="upi">@lang('modules.order.upi')</option>
                            <option value="bank_transfer">@lang('modules.order.bank_transfer')</option>
                        </x-select>
                    </div>

                    <div>
                        <x-label for="paymentAmount" :value="__('modules.customer.payment_amount')" />
                        <x-input id="paymentAmount" 
                            type="number" 
                            step="0.01" 
                            min="0" 
                            max="{{ $totalOutstanding }}"
                            class="block mt-1 w-full" 
                            wire:model.live="paymentAmount" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @lang('modules.customer.max_payment'): {{ currency_format($totalOutstanding, restaurant()->currency_id) }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <x-label for="notes" :value="__('app.note')" />
                        <x-textarea id="notes" 
                            class="block mt-1 w-full" 
                            wire:model="notes" 
                            rows="2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-button wire:click="submitPayment" wire:loading.attr="disabled">
                        @lang('modules.customer.record_payment')
                    </x-button>
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-8">
            <p class="text-gray-500 dark:text-gray-400">@lang('modules.customer.no_outstanding_orders')</p>
        </div>
    @endif
</div>

