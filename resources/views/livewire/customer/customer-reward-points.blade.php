<div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
            <p class="text-sm text-amber-600 dark:text-amber-400">@lang('modules.reward.availablePoints')</p>
            <p class="text-2xl font-bold text-amber-900 dark:text-amber-100 flex items-center gap-2">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                {{ $balance ? $balance->available_points : 0 }}
            </p>
        </div>
        
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
            <p class="text-sm text-blue-600 dark:text-blue-400">@lang('modules.reward.earnedPoints')</p>
            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                {{ $balance ? $balance->total_earned : 0 }}
            </p>
        </div>
        
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
            <p class="text-sm text-green-600 dark:text-green-400">@lang('modules.reward.redeemedPoints')</p>
            <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                {{ $balance ? $balance->total_redeemed : 0 }}
            </p>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-end">
        @if(user_can('Update Customer'))
        <x-button wire:click="$set('showAdjustModal', true)" type="button">
            {{ __('modules.reward.adjustPointsTitle') }}
        </x-button>
        @endif
    </div>

    <!-- Transaction History -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                @lang('modules.reward.transactionHistory')
            </h3>
        </div>
        
        @if($transactions->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            @lang('app.date')
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            @lang('app.type')
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            @lang('app.description')
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            @lang('modules.reward.points')
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            @lang('modules.reward.balance')
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    @foreach($transactions as $tx)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $tx->created_at->format('M d, Y h:i A') }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            @if($tx->type === 'earn')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-500">
                                    @lang('modules.reward.earn')
                                </span>
                            @elseif($tx->type === 'redeem')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-500">
                                    @lang('modules.reward.redeem')
                                </span>
                            @elseif($tx->type === 'adjust')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    @lang('modules.reward.adjust')
                                </span>
                            @elseif($tx->type === 'expire')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-500">
                                    @lang('modules.reward.expire')
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $tx->description ?? '-' }}
                            @if($tx->order_id)
                                <a href="#" wire:click.prevent="$dispatch('showOrderDetail', { id: {{ $tx->order_id }} })" class="text-skin-base hover:underline ml-1">
                                    #{{ $tx->order->order_number ?? $tx->order_id }}
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium {{ $tx->points > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $tx->points > 0 ? '+' : '' }}{{ $tx->points }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                            {{ $tx->balance_after }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $transactions->links() }}
        </div>
        @else
        <div class="p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">@lang('modules.reward.noTransactions')</h3>
        </div>
        @endif
    </div>

    <!-- Adjust Points Modal -->
    <x-modal wire:model.live="showAdjustModal">
        <div class="px-6 py-4">
            <div class="text-lg font-medium text-gray-900 dark:text-white">
                {{ __('modules.reward.adjustPointsTitle') }}
            </div>

            <div class="mt-4 space-y-4">
                <div>
                    <x-label for="adjustPoints" :value="__('modules.reward.adjustPointsAmount')" />
                    <x-input id="adjustPoints" class="block mt-1 w-full" type="number" wire:model="adjustPoints" placeholder="e.g. 50 or -50" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('modules.reward.adjustPointsAmountHelp')</p>
                    <x-input-error for="adjustPoints" class="mt-2" />
                </div>

                <div>
                    <x-label for="adjustDescription" :value="__('modules.reward.adjustPointsDescription')" />
                    <x-input id="adjustDescription" class="block mt-1 w-full" type="text" wire:model="adjustDescription" placeholder="e.g. Customer support resolution" />
                    <x-input-error for="adjustDescription" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 text-end">
            <x-secondary-button wire:click="$set('showAdjustModal', false)" wire:loading.attr="disabled">
                {{ __('app.cancel') }}
            </x-secondary-button>

            <x-button class="ms-3" wire:click="saveAdjustment" wire:loading.attr="disabled">
                {{ __('app.save') }}
            </x-button>
        </div>
    </x-modal>
</div>
