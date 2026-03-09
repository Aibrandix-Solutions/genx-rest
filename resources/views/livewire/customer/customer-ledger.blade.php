<div class="space-y-6">
    <!-- Filters and Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('modules.customer.ledger_title') }}</h3>
            <button wire:click="downloadPdf" 
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ __('app.download') }} PDF
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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

            <div>
                <x-label for="search" :value="__('app.search')" />
                <x-input id="search" 
                    type="text" 
                    class="block mt-1 w-full" 
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('modules.customer.search_order_number') }}" />
            </div>

            <div class="flex items-end">
                <button wire:click="$set('search', '')" 
                    class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                    @lang('app.reset')
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
            <p class="text-sm text-blue-600 dark:text-blue-400">@lang('modules.customer.opening_balance')</p>
            <p class="text-xl font-bold text-blue-900 dark:text-blue-100">
                {{ currency_format($openingBalance, restaurant()->currency_id) }}
            </p>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
            <p class="text-sm text-green-600 dark:text-green-400">@lang('modules.customer.total_debit')</p>
            <p class="text-xl font-bold text-green-900 dark:text-green-100">
                {{ currency_format($totalDebit, restaurant()->currency_id) }}
            </p>
        </div>

        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800">
            <p class="text-sm text-orange-600 dark:text-orange-400">@lang('modules.customer.total_credit')</p>
            <p class="text-xl font-bold text-orange-900 dark:text-orange-100">
                {{ currency_format($totalCredit, restaurant()->currency_id) }}
            </p>
        </div>

        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
            <p class="text-sm text-purple-600 dark:text-purple-400">@lang('modules.customer.closing_balance')</p>
            <p class="text-xl font-bold text-purple-900 dark:text-purple-100">
                {{ currency_format($closingBalance, restaurant()->currency_id) }}
            </p>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            @lang('app.date')
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            @lang('modules.customer.reference')
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            @lang('app.description')
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            @lang('modules.customer.debit')
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            @lang('modules.customer.credit')
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            @lang('modules.customer.balance')
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ Carbon\Carbon::parse($transaction['date'])->format('M d, Y h:i A') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                @if($transaction['type'] === 'order')
                                    <button wire:click="viewOrder({{ $transaction['id'] }})"
                                        class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 hover:underline">
                                        {{ $transaction['reference'] }}
                                    </button>
                                @else
                                    {{ $transaction['reference'] }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <div>
                                    <span class="font-medium">{{ $transaction['description'] }}</span>
                                    @if($transaction['type'] === 'order')
                                        <span class="ml-2 px-2 py-1 text-xs rounded 
                                            {{ $transaction['status'] === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' }}">
                                            {{ strtoupper($transaction['status']) }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium 
                                {{ $transaction['debit'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500' }}">
                                {{ $transaction['debit'] > 0 ? currency_format($transaction['debit'], restaurant()->currency_id) : '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium 
                                {{ $transaction['credit'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-500' }}">
                                {{ $transaction['credit'] > 0 ? currency_format($transaction['credit'], restaurant()->currency_id) : '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold 
                                {{ $transaction['balance'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                {{ currency_format($transaction['balance'], restaurant()->currency_id) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                @lang('modules.customer.no_transactions_found')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>

