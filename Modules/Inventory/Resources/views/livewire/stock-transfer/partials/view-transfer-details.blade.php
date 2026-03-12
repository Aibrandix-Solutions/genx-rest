<div class="space-y-6">
    <!-- Transfer Header -->
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inventory::modules.transfers.transfer_number') }}</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedTransfer->transfer_number }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inventory::modules.transfers.status') }}</p>
                <span @class([
                    'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' => $selectedTransfer->status === 'pending',
                    'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' => $selectedTransfer->status === 'in_transit',
                    'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' => $selectedTransfer->status === 'completed',
                    'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' => $selectedTransfer->status === 'cancelled',
                ])>
                    {{ __('inventory::modules.transfers.status_' . $selectedTransfer->status) }}
                </span>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inventory::modules.transfers.from') }}</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ $selectedTransfer->sourceLocation ? $selectedTransfer->sourceLocation->name : $selectedTransfer->sourceBranch->name }}
                    @if($selectedTransfer->sourceLocation && $selectedTransfer->sourceLocation->type !== 'branch')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 ml-1">
                            {{ ucfirst($selectedTransfer->sourceLocation->type) }}
                        </span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inventory::modules.transfers.to') }}</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ $selectedTransfer->destinationLocation ? $selectedTransfer->destinationLocation->name : $selectedTransfer->destinationBranch->name }}
                    @if($selectedTransfer->destinationLocation && $selectedTransfer->destinationLocation->type !== 'branch')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 ml-1">
                            {{ ucfirst($selectedTransfer->destinationLocation->type) }}
                        </span>
                    @endif
                </p>
            </div>
            @if($selectedTransfer->expected_delivery_date)
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inventory::modules.transfers.expected_delivery_date') }}</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $selectedTransfer->expected_delivery_date->translatedFormat('M d, Y') }}
                    </p>
                </div>
            @endif
            @if($selectedTransfer->confirmed_at)
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inventory::modules.transfers.confirmed_at') }}</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $selectedTransfer->confirmed_at->timezone(timezone())->translatedFormat('M d, Y h:i A') }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('inventory::modules.transfers.confirmed_by') }}: {{ $selectedTransfer->confirmedBy->name ?? '--' }}
                    </p>
                </div>
            @endif
        </div>
        @if($selectedTransfer->notes)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inventory::modules.transfers.notes') }}</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedTransfer->notes }}</p>
            </div>
        @endif
    </div>

    <!-- Items Table -->
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ __('inventory::modules.transfers.items') }}
        </h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            {{ __('inventory::modules.transfers.item') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            {{ __('inventory::modules.transfers.requested_quantity') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            {{ __('inventory::modules.transfers.confirmed_quantity') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            {{ __('inventory::modules.transfers.status') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            {{ __('inventory::modules.transfers.notes') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($selectedTransfer->items as $item)
                        <tr class="align-top">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $item->sourceItem->name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ number_format($item->requested_quantity, 2) }} {{ $item->unit?->symbol ?? $item->sourceItem->unit?->symbol ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $item->confirmed_quantity ? number_format($item->confirmed_quantity, 2) . ' ' . ($item->unit?->symbol ?? $item->destinationItem->unit?->symbol ?? '') : '--' }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span @class([
                                    'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' => $item->status === 'pending',
                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' => $item->status === 'in_transit',
                                    'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' => $item->status === 'completed',
                                    'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300' => $item->status === 'partially_received',
                                    'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' => $item->status === 'cancelled',
                                ])>
                                    {{ __('inventory::modules.transfers.item_status_' . $item->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-[160px]">
                                {{ $item->notes ?: '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>


