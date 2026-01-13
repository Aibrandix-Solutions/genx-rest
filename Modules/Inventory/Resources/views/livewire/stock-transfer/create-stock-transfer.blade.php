<div>
    <div class="space-y-6">
        <!-- Source Location -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('inventory::modules.transfers.source_location') }} <span class="text-red-500">*</span>
            </label>
            <select wire:model.live="sourceLocation" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                <option value="">{{ __('inventory::modules.transfers.select_source_location') }}</option>
                @foreach($availableLocations as $location)
                    <option value="{{ $location->id }}">
                        {{ $location->name }} 
                        @if($location->type !== 'branch')
                            ({{ ucfirst($location->type) }})
                        @endif
                    </option>
                @endforeach
            </select>
            @error('sourceLocation') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Destination Location -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('inventory::modules.transfers.destination_location') }} <span class="text-red-500">*</span>
            </label>
            <select wire:model.live="destinationLocation" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                <option value="">{{ __('inventory::modules.transfers.select_destination_location') }}</option>
                @foreach($availableLocations as $location)
                    <option value="{{ $location->id }}">
                        {{ $location->name }} 
                        @if($location->type !== 'branch')
                            ({{ ucfirst($location->type) }})
                        @endif
                    </option>
                @endforeach
            </select>
            @error('destinationLocation') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Expected Delivery Date -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('inventory::modules.transfers.expected_delivery_date') }}
            </label>
            <input type="date" wire:model="expectedDeliveryDate" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500" min="{{ date('Y-m-d') }}">
            @error('expectedDeliveryDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Notes -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('inventory::modules.transfers.notes') }}
            </label>
            <textarea wire:model="notes" rows="3" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500" placeholder="{{ __('inventory::modules.transfers.notes_placeholder') }}"></textarea>
            @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Transfer Items -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('inventory::modules.transfers.transfer_items') }} <span class="text-red-500">*</span>
                </label>
                <button type="button" wire:click="addTransferItem" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    {{ __('inventory::modules.transfers.add_item') }}
                </button>
            </div>

            @if(count($transferItems) > 0)
                <div class="space-y-4">
                    @foreach($transferItems as $index => $item)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('inventory::modules.transfers.item') }} #{{ $index + 1 }}
                                </h4>
                                @if(count($transferItems) > 1)
                                    <button type="button" wire:click="removeTransferItem({{ $index }})" class="text-red-600 hover:text-red-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Source Item -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('inventory::modules.transfers.source_item') }} <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model.live="transferItems.{{ $index }}.source_item_id" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">{{ __('inventory::modules.transfers.select_item') }}</option>
                                        @foreach($availableItems as $invItem)
                                            <option value="{{ $invItem->id }}">{{ $invItem->name }}</option>
                                        @endforeach
                                    </select>
                                    @error("transferItems.{$index}.source_item_id") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    @if(isset($item['available_stock']) && $item['available_stock'] > 0)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ __('inventory::modules.transfers.available_stock') }}: <span class="font-medium">{{ number_format($item['available_stock'], 2) }}</span>
                                        </p>
                                    @endif
                                </div>

                                <!-- Destination Item -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('inventory::modules.transfers.destination_item') }}
                                        <span class="text-xs text-gray-500">(optional - will auto-create if not found)</span>
                                    </label>
                                    @if($destinationLocation)
                                        <select wire:model.live="transferItems.{{ $index }}.destination_item_id" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">{{ __('inventory::modules.transfers.auto_create_item') }}</option>
                                            @forelse($destinationItems as $destItem)
                                                <option value="{{ $destItem->id }}">
                                                    {{ $destItem->name }}
                                                    @if($destItem->unit)
                                                        ({{ $destItem->unit->symbol ?? '' }})
                                                    @endif
                                                </option>
                                            @empty
                                                <option value="" disabled>{{ __('inventory::modules.transfers.select_or_auto_create') }}</option>
                                            @endforelse
                                        </select>
                                        @if(count($destinationItems) === 0)
                                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                                {{ __('inventory::modules.transfers.items_will_be_auto_created') }}
                                            </p>
                                        @endif
                                    @else
                                        <select disabled class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-400">
                                            <option>{{ __('inventory::modules.transfers.select_destination_location_first') }}</option>
                                        </select>
                                    @endif
                                    @error("transferItems.{$index}.destination_item_id") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Quantity -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('inventory::modules.transfers.quantity') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" wire:model="transferItems.{{ $index }}.quantity" step="0.01" min="0.01" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500" placeholder="0.00">
                                    @error("transferItems.{$index}.quantity") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center">
                    <p class="text-gray-500 dark:text-gray-400 mb-4">{{ __('inventory::modules.transfers.no_items_added') }}</p>
                    <button type="button" wire:click="addTransferItem" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        {{ __('inventory::modules.transfers.add_first_item') }}
                    </button>
                </div>
            @endif
            @error('transferItems') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <button type="button" wire:click="closeModal" class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                {{ __('app.cancel') }}
            </button>
            <button type="button" wire:click="createTransfer" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                {{ __('inventory::modules.transfers.create_transfer') }}
            </button>
        </div>
    </div>
</div>


