<div>
    @if($transfer)
        <div class="space-y-6">
            <!-- Transfer Info -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inventory::modules.transfers.transfer_number') }}</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $transfer->transfer_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inventory::modules.transfers.from_to') }}</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $transfer->sourceLocation ? $transfer->sourceLocation->name : $transfer->sourceBranch->name }} → {{ $transfer->destinationLocation ? $transfer->destinationLocation->name : $transfer->destinationBranch->name }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('inventory::modules.transfers.items_to_receive') }}
                </h3>
                
                <div class="space-y-4">
                    @foreach($transfer->items as $item)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $item->sourceItem->name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ __('inventory::modules.transfers.requested') }}: 
                                        <span class="font-medium">{{ number_format($item->requested_quantity, 2) }} {{ $item->sourceItem->unit->symbol ?? '' }}</span>
                                    </p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('inventory::modules.transfers.confirmed_quantity') }}
                                    </label>
                                    <input type="number" 
                                           wire:model="receivedItems.{{ $item->id }}.confirmed_quantity" 
                                           step="0.01" 
                                           min="0" 
                                           max="{{ $item->requested_quantity }}"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                    @error("receivedItems.{$item->id}.confirmed_quantity") 
                                        <span class="text-red-500 text-sm">{{ $message }}</span> 
                                    @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('inventory::modules.transfers.notes') }}
                                    </label>
                                    <input type="text" 
                                           wire:model="receivedItems.{{ $item->id }}.notes" 
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                           placeholder="{{ __('inventory::modules.transfers.notes_placeholder') }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" wire:click="closeModal" class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    {{ __('app.cancel') }}
                </button>
                <button type="button" wire:click="confirmReceive" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    {{ __('inventory::modules.transfers.confirm_receive') }}
                </button>
            </div>
        </div>
    @endif
</div>


