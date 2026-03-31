<div>
    <div class="space-y-6">

        <!-- Read-only location info -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400 mb-0.5">{{ __('inventory::modules.transfers.source_location') }}</p>
                <p class="text-gray-900 dark:text-white font-semibold">
                    {{ $transfer->sourceLocation?->name ?? $transfer->sourceBranch?->name ?? '—' }}
                </p>
            </div>
            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400 mb-0.5">{{ __('inventory::modules.transfers.destination_location') }}</p>
                <p class="text-gray-900 dark:text-white font-semibold">
                    {{ $transfer->destinationLocation?->name ?? $transfer->destinationBranch?->name ?? '—' }}
                </p>
            </div>
        </div>

        <!-- Expected Delivery Date -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('inventory::modules.transfers.expected_delivery_date') }}
            </label>
            <input type="date" wire:model="expectedDeliveryDate"
                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                min="{{ date('Y-m-d') }}">
            @error('expectedDeliveryDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Notes -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('inventory::modules.transfers.notes') }}
            </label>
            <textarea wire:model="notes" rows="3"
                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                placeholder="{{ __('inventory::modules.transfers.notes_placeholder') }}"></textarea>
            @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Transfer Items -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('inventory::modules.transfers.transfer_items') }} <span class="text-red-500">*</span>
                </label>
                <button type="button" wire:click="addTransferItem"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    {{ __('inventory::modules.transfers.add_item') }}
                </button>
            </div>

            @if(count($transferItems) > 0)
                <div class="space-y-4">
                    @php $availableItemsJson = $availableItems->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'unit_id' => $i->unit_id, 'unit_symbol' => $i->unit?->symbol ?? ''])->toJson(); @endphp
                    @foreach($transferItems as $index => $item)
                        <div wire:key="edit-item-{{ $index }}" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('inventory::modules.transfers.transfer_item_label') }} #{{ $index + 1 }}
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
                                <!-- Item (searchable) -->
                                <div x-data="{
                                        open: false,
                                        search: '',
                                        selectedLabel: '',
                                        items: {{ $availableItemsJson }},
                                        get filtered() {
                                            if (!this.search) return this.items;
                                            const q = this.search.toLowerCase();
                                            return this.items.filter(i => i.name.toLowerCase().includes(q));
                                        },
                                        selectItem(item) {
                                            this.selectedLabel = item.name;
                                            this.open = false;
                                            this.search = '';
                                            $wire.set('transferItems.{{ $index }}.source_item_id', item.id);
                                        },
                                        init() {
                                            const currentId = {{ $item['source_item_id'] ?? 'null' }};
                                            if (currentId) {
                                                const found = this.items.find(i => i.id == currentId);
                                                if (found) this.selectedLabel = found.name;
                                            }
                                        }
                                    }" x-init="init()" @click.away="open = false" class="relative">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('inventory::modules.transfers.item') }} <span class="text-red-500">*</span>
                                    </label>
                                    <button type="button" @click="open = !open"
                                        class="w-full text-left border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <span x-text="selectedLabel || '{{ __('inventory::modules.transfers.select_item') }}'" class="truncate" :class="selectedLabel ? '' : 'text-gray-400'"></span>
                                        <svg class="w-4 h-4 text-gray-400 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" x-cloak class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg">
                                        <div class="p-2 border-b border-gray-100 dark:border-gray-600">
                                            <input x-model="search" type="text" placeholder="{{ __('app.search') }}..."
                                                class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-1.5 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                @click.stop />
                                        </div>
                                        <ul class="max-h-48 overflow-y-auto py-1">
                                            <template x-for="item in filtered" :key="item.id">
                                                <li @click="selectItem(item)"
                                                    class="px-4 py-2 text-sm cursor-pointer hover:bg-blue-50 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100"
                                                    x-text="item.name"></li>
                                            </template>
                                            <li x-show="filtered.length === 0" class="px-4 py-2 text-sm text-gray-400">{{ __('app.noResultFound') }}</li>
                                        </ul>
                                    </div>
                                    @error("transferItems.{$index}.source_item_id") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    @if(isset($item['source_item_id']) && $item['source_item_id'])
                                        <p class="text-xs mt-1 {{ $item['available_stock'] > 0 ? 'text-gray-500 dark:text-gray-400' : 'text-red-500 dark:text-red-400' }}">
                                            {{ __('inventory::modules.transfers.available_stock') }}:
                                            <span class="font-medium">{{ number_format($item['available_stock'], 2) }}</span>
                                        </p>
                                    @endif
                                </div>

                                <!-- Unit (read-only) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('inventory::modules.transfers.unit') }}
                                    </label>
                                    <div class="w-full border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2.5 bg-gray-100 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 min-h-[42px] flex items-center">
                                        @if(isset($item['source_item_id']) && $item['source_item_id'])
                                            @php $unitObj = $availableItems->find($item['source_item_id'])?->unit; @endphp
                                            @if($unitObj)
                                                <span class="font-medium">{{ $unitObj->symbol }}</span>
                                                <span class="ml-1.5 text-gray-400 text-xs">({{ $unitObj->name }})</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400 text-sm">{{ __('inventory::modules.transfers.select_item') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Quantity -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('inventory::modules.transfers.quantity') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" wire:model="transferItems.{{ $index }}.quantity"
                                        step="0.01" min="0.01"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="0.00">
                                    @error("transferItems.{$index}.quantity") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            @error('transferItems') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <button type="button" wire:click="$dispatch('closeEditTransferModal')"
                class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                {{ __('app.cancel') }}
            </button>
            <button type="button" wire:click="updateTransfer"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                {{ __('inventory::modules.transfers.update_transfer') }}
            </button>
        </div>

    </div>
</div>
