<div>
    @php
        $rewardColumnVisible = in_array('Reward Point', restaurant_modules())
            && $rewardSettings
            && $rewardSettings->enable_reward_point;
        $customerTableEmptyColspan = 7 + ($rewardColumnVisible ? 1 : 0);
    @endphp
    <div class="flex flex-col">
        <div class="flex items-center justify-between px-4 py-2 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">@lang('app.perPage'):</span>
                <select wire:model.live="perPage"
                    class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1 pl-2 pr-6">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.customer.name')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.customer.email')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.customer.phone')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.order.totalOrder')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.customer.outstanding_balance')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.customer.total_sales')
                                </th>
                                @if($rewardColumnVisible)
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    {{ optional($rewardSettings)->reward_point_display_name ?? __('modules.reward.rewardPointDisplayName') }}
                                </th>
                                @endif
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">
                                    @lang('app.action')
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700" wire:key='customer-list-{{ microtime() }}'>
                            @forelse ($customers as $item)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700" wire:key='customer-{{ $item->id . rand(1111, 9999) . microtime() }}' wire:loading.class.delay='opacity-10'>
                                <td class="py-2.5 px-4 text-base text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $item->name }}
                                </td>
                                <td class="py-2.5 px-4 text-base text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $item->email ?? '--' }}
                                </td>
                                <td class="py-2.5 px-4 text-base text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $item->phone ?? '--' }}
                                </td>
                                <td class="py-2.5 px-4 text-base text-gray-900 whitespace-nowrap dark:text-white">
                                    <span
                                    @if(user_can('Show Order'))
                                     wire:click='showCustomerOrders({{ $item->id }})'
                                    @endif

                                     @class(['text-xs font-medium px-2 py-1 rounded uppercase tracking-wide whitespace-nowrap bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400 border border-gray-400 cursor-pointer'])>
                                        {{ $item->orders_count }} @lang('menu.orders')
                                    </span>
                                 </td>

                                <td class="py-2.5 px-4 text-base text-gray-900 whitespace-nowrap dark:text-white">
                                    @php
                                        $outstandingBalance = $item->outstanding_balance;
                                    @endphp
                                    @if($outstandingBalance > 0)
                                        <span class="text-xs font-medium px-2 py-1 rounded uppercase tracking-wide whitespace-nowrap bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 border border-red-200 dark:border-red-800">
                                            {{ currency_format($outstandingBalance, restaurant()->currency_id) }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">—</span>
                                    @endif
                                </td>

                                <td class="py-2.5 px-4 text-base text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ currency_format($item->total_sales, restaurant()->currency_id) }}
                                </td>
                                @if($rewardColumnVisible)
                                <td class="py-2.5 px-4 text-base text-gray-900 whitespace-nowrap dark:text-white">
                                    @php
                                        $balance = $item->rewardBalance->first();
                                    @endphp
                                    <button wire:click="showCustomerRewardPoints({{ $item->id }})" class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-500 dark:hover:bg-amber-900/50 transition-colors">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                        </svg>
                                        {{ $balance ? $balance->available_points : 0 }}
                                    </button>
                                </td>
                                @endif

                                <td class="py-2.5 px-4 space-x-2 whitespace-nowrap text-right rtl:space-x-reverse">
                                    @if(user_can('Create Payment') || user_can('Update Order'))
                                    <button wire:click='showCustomerPayment({{ $item->id }})' 
                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-green-700 bg-green-100 border border-green-300 rounded hover:bg-green-200 dark:bg-green-800/50 dark:text-green-300 dark:border-green-700 dark:hover:bg-green-700"
                                        title="@lang('modules.customer.pay')">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        @lang('modules.customer.pay')
                                    </button>
                                    @endif

                                    @if(user_can('Show Order'))
                                    <button wire:click='showCustomerLedger({{ $item->id }})' 
                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded hover:bg-blue-200 dark:bg-blue-800/50 dark:text-blue-300 dark:border-blue-700 dark:hover:bg-blue-700"
                                        title="@lang('modules.customer.ledger')">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        @lang('modules.customer.ledger')
                                    </button>
                                    @endif

                                    @if(user_can('Show Order'))
                                    <button wire:click='showCustomerSales({{ $item->id }})' 
                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-purple-700 bg-purple-100 border border-purple-300 rounded hover:bg-purple-200 dark:bg-purple-800/50 dark:text-purple-300 dark:border-purple-700 dark:hover:bg-purple-700"
                                        title="@lang('modules.customer.sales')">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                        @lang('modules.customer.sales')
                                    </button>
                                    @endif

                                    @if(user_can('Update Customer'))
                                    <x-secondary-button-table wire:click='showEditCustomer({{ $item->id }})' wire:key='customer-edit-{{ $item->id . microtime() }}'
                                        wire:key='editmenu-item-button-{{ $item->id }}'>
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z">
                                            </path>
                                            <path fill-rule="evenodd"
                                                d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        @lang('app.update')
                                    </x-secondary-button-table>
                                    @endif

                                    @if(user_can('Delete Customer'))
                                    <x-danger-button-table  wire:click="showDeleteCustomer({{ $item->id }})"  wire:key='customer-del-{{ $item->id . microtime() }}'>
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    </x-danger-button-table>
                                    @endif

                                </td>
                            </tr>
                            @empty
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="py-2.5 px-4 space-x-6 text-gray-500" colspan="{{ $customerTableEmptyColspan }}">
                                    @lang('messages.noCustomerFound')
                                </td>
                            </tr>
                            @endforelse

                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    <div wire:key='customer-table-paginate-{{ microtime() }}'
        class="sticky bottom-0 right-0 items-center w-full py-2.5 px-4 bg-white border-t border-gray-200 sm:flex sm:justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center mb-4 sm:mb-0 w-full">
            {{ $customers->links() }}
        </div>
    </div>

    <x-right-modal wire:model.live="showEditCustomerModal">
        <x-slot name="title">
            {{ __("modules.customer.editCustomer") }}
        </x-slot>

        <x-slot name="content">
            @if ($customer)
            @livewire('forms.editCustomer', ['customer' => $customer], key(str()->random(50)))
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditCustomerModal', false)" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-right-modal wire:model.live="showCustomerOrderModal" maxWidth="3xl">
        <x-slot name="title">
            {{ __("menu.orders") }}
        </x-slot>

        <x-slot name="content">
            @if ($customer)
            @livewire('customer.customerOrders', ['customer' => $customer], key(str()->random(50)))
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showCustomerOrderModal', false)" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-confirmation-modal wire:model="confirmDeleteCustomerModal">
        <x-slot name="title">
            @lang('modules.customer.deleteCustomer')?
        </x-slot>

        <x-slot name="content">
            @lang('modules.customer.deleteCustomerMessage')
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteCustomerModal')" wire:loading.attr="disabled">
                {{ __('app.cancel') }}
            </x-secondary-button>

            @if ($customer)
            <x-danger-button class="ml-3" wire:click='deleteCustomer({{ $customer->id }}, true)' wire:loading.attr="disabled">
                @lang('modules.customer.deleteWithOrder')
            </x-danger-button>

            <x-danger-button class="ml-3" wire:click='deleteCustomer({{ $customer->id }})' wire:loading.attr="disabled">
                {{ __('app.delete') }}
            </x-danger-button>
            @endif
         </x-slot>
    </x-confirmation-modal>

    <x-right-modal wire:model.live="showPaymentModal" maxWidth="3xl">
        <x-slot name="title">
            @lang('modules.customer.pay_for_customer'): {{ $customer->name ?? '' }}
        </x-slot>

        <x-slot name="content">
            @if ($customer)
                @livewire('customer.customer-payment', ['customer' => $customer], key('customer-payment-' . $customer->id))
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showPaymentModal', false)" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-right-modal wire:model.live="showLedgerModal" maxWidth="3xl">
        <x-slot name="title">
            @lang('modules.customer.ledger_for_customer'): {{ $customer->name ?? '' }}
        </x-slot>

        <x-slot name="content">
            @if ($customer)
                @livewire('customer.customer-ledger', ['customer' => $customer], key('customer-ledger-' . $customer->id))
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showLedgerModal', false)" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-right-modal wire:model.live="showSalesModal" maxWidth="3xl">
        <x-slot name="title">
            @lang('modules.customer.sales_for_customer'): {{ $customer->name ?? '' }}
        </x-slot>

        <x-slot name="content">
            @if ($customer)
                @livewire('customer.customer-sales', ['customer' => $customer], key('customer-sales-' . $customer->id))
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showSalesModal', false)" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-right-modal wire:model.live="showRewardPointsModal" maxWidth="3xl">
        <x-slot name="title">
            {{ optional($rewardSettings)->reward_point_display_name ?? 'Reward Points' }}: {{ $customer->name ?? '' }}
        </x-slot>

        <x-slot name="content">
            @if ($customer)
                @livewire('customer.customer-reward-points', ['customer' => $customer], key('customer-reward-' . $customer->id))
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showRewardPointsModal', false)" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-secondary-button>
        </x-slot>
    </x-right-modal>

</div>
