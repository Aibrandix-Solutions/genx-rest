<div>
    <x-modal wire:model="showModal" maxWidth="4xl">
        <div class="p-6 bg-white dark:bg-gray-800">
            <div class="text-lg font-medium text-gray-900 dark:text-white mb-6">
                {{ $isEditing ? 'Edit Purchase Return' : 'Create Purchase Return' }}
            </div>

            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Purchase Order (Optional) -->
                    <div>
                        <x-label for="purchase_order_id" value="Purchase (Optional)" 
                                class="text-gray-700 dark:text-gray-300" />
                        <select id="purchase_order_id" wire:model.live="purchaseOrderId" 
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Select Purchase...</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->id }}">{{ $po->po_number }} - {{ $po->supplier->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="purchaseOrderId" class="mt-1" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Selecting a purchase will auto-fill items</p>
                    </div>

                    <!-- Supplier -->
                    <div>
                        <x-label for="supplier_id" value="Supplier *" 
                                class="text-gray-700 dark:text-gray-300" />
                        <select id="supplier_id" wire:model.live="supplierId" 
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Select Supplier...</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="supplierId" class="mt-1" />
                    </div>

                    <!-- Return Date -->
                    <div>
                        <x-label for="return_date" value="Return Date *"
                                class="text-gray-700 dark:text-gray-300" />
                        <x-input type="date" id="return_date" wire:model="returnDate" 
                                class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" />
                        <x-input-error for="returnDate" class="mt-1" />
                    </div>
                </div>

                <!-- Items -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Return Items</h3>
                        @if(!$purchaseOrderId)
                            <x-button type="button" wire:click="addItem">
                                Add Item
                            </x-button>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">Items from selected purchase (locked)</p>
                        @endif
                    </div>

                    <div class="overflow-x-auto overflow-y-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Item Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Quantity
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Unit Price
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Subtotal
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($items as $index => $item)
                                    <tr>
                                        <td class="px-6 py-4">
                                            @if($purchaseOrderId)
                                                {{-- Purchase-linked: Show item name (locked) --}}
                                                @php
                                                    $invItem = $inventoryItems->firstWhere('id', $item['inventoryItemId']);
                                                @endphp
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $invItem->display_name ?? 'Unknown Item' }}
                                                </div>
                                                @if(isset($item['purchasedQuantity']))
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        Purchased: {{ number_format($item['purchasedQuantity'], 2) }} | 
                                                        Stock: {{ number_format($item['availableStock'] ?? 0, 2) }}
                                                    </p>
                                                @endif
                                            @else
                                                {{-- Generic return: Allow item selection --}}
                                                <x-select wire:model.live="items.{{ $index }}.inventoryItemId"
                                                        wire:change="fetchUnitPrice({{ $index }})"
                                                         class="block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                                                         required>
                                                    <option value="">Select Item...</option>
                                                    @foreach($inventoryItems as $invItem)
                                                        <option value="{{ $invItem->id }}">{{ $invItem->display_name }}</option>
                                                    @endforeach
                                                </x-select>
                                                @error("items.{$index}.inventoryItemId") <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                                                @if(isset($item['maxQuantity']))
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        Available: {{ number_format($item['maxQuantity'], 2) }}
                                                    </p>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <input type="number" step="0.01"
                                                    @if(isset($item['maxQuantity']))
                                                        min="0" max="{{ $item['maxQuantity'] }}"
                                                    @endif
                                                    wire:model="items.{{ $index }}.quantity"
                                                    wire:change="calculateSubtotal({{ $index }})"
                                                    placeholder="0.00"
                                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                            @error("items.{$index}.quantity") <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                                            @if(isset($item['maxQuantity']) && $item['maxQuantity'] > 0)
                                                <p class="text-xs text-green-600 dark:text-green-400 mt-1 font-medium">
                                                    Max: {{ number_format($item['maxQuantity'], 2) }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <input type="number" step="0.01" min="0"
                                                    value="{{ number_format($item['unitPrice'] ?? 0, 2, '.', '') }}"
                                                    readonly
                                                    disabled
                                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 cursor-not-allowed opacity-75 shadow-sm sm:text-sm" />
                                            @error("items.{$index}.unitPrice") <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                            @php
                                                $quantity = isset($item['quantity']) && $item['quantity'] !== '' ? (float)$item['quantity'] : 0;
                                                $unitPrice = isset($item['unitPrice']) && $item['unitPrice'] !== '' ? (float)$item['unitPrice'] : 0;
                                                $subtotal = $quantity * $unitPrice;
                                            @endphp
                                            {{ currency_format($subtotal, restaurant()->currency_id) }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($purchaseOrderId)
                                                {{-- Purchase-linked: Cannot remove items --}}
                                                <span class="text-gray-400 dark:text-gray-500 text-sm">-</span>
                                            @else
                                                {{-- Generic return: Allow removal --}}
                                                <button type="button" wire:click="removeItem({{ $index }})"
                                                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                                    Remove
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 whitespace-nowrap text-lg text-gray-900 dark:text-white font-bold text-right">
                                        Total:
                                    </td>
                                    <td colspan="2" class="px-6 py-4 whitespace-nowrap text-lg text-gray-900 dark:text-white font-bold">
                                        {{ currency_format($totalAmount, restaurant()->currency_id) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-6">
                    <x-label for="note" value="Notes"
                            class="text-gray-700 dark:text-gray-300" />
                    <textarea id="note" wire:model="note" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    <x-input-error for="note" class="mt-1" />
                </div>
                                <!-- Process Immediately Option -->
                @if(!$isEditing)
                <div class="mb-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="processImmediately" 
                               class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">
                            Process & Reduce Stock Immediately
                        </span>
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-6">
                        ⚠️ Stock will be reduced immediately. This action cannot be undone.
                    </p>
                </div>
                @endif

                <div class="flex justify-end space-x-3 pt-5 border-t border-gray-200 dark:border-gray-700">
                    <x-secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled">
                        {{ trans('app.close') }}
                    </x-secondary-button>

                    <x-button type="submit" wire:loading.attr="disabled">
                        {{ trans('app.save') }}
                    </x-button>
                </div>
            </form>

            @if($isEditing && $purchaseReturn && $purchaseReturn->status === 'pending')
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <x-danger-button wire:click="processReturn" wire:loading.attr="disabled">
                    Process Return (Reduce Stock)
                </x-danger-button>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Processing the return will reduce inventory stock for all items in this return.
                </p>
            </div>
            @endif
        </div>
    </x-modal>
</div>

