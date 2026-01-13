<div class="max-w-6xl mx-auto space-y-6">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">Add Purchase</h1>
        <form wire:submit.prevent="savePurchase" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-label value="Supplier" />
                    <x-select wire:model.live="supplierId" class="w-full">
                        <option value="">Select supplier...</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </x-select>
                    @error('supplierId') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-label value="Order Date" />
                    <x-input type="date" wire:model.live="orderDate" class="w-full" />
                    @error('orderDate') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-label value="Location" />
                    <x-select wire:model.live="location_id" class="w-full">
                        <option value="">Select location...</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->display_name }}</option>
                        @endforeach
                    </x-select>
                    @error('location_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-label value="Status" />
                    <x-select wire:model.live="status" class="w-full">
                        <option value="ordered">Ordered</option>
                        <option value="pending">Pending</option>
                        <option value="received">Received</option>
                        <option value="cancelled">Cancelled</option>
                    </x-select>
                    @error('status') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <x-label value="Notes" />
                    <textarea wire:model.live="notes" rows="2" class="w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700"></textarea>
                    @error('notes') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-label value="Order Discount" />
                    <div class="flex gap-2">
                        <x-input type="number" step="0.01" min="0" wire:model.live="discount" class="w-full" />
                        <x-select wire:model.live="discount_type">
                            <option value="fixed">Fixed</option>
                            <option value="percentage">%</option>
                        </x-select>
                    </div>
                    @error('discount') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    @error('discount_type') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Items</h2>
                </div>

                <!-- Item Search Bar -->
                <div class="relative">
                    <x-label value="Search and Add Items" />
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            wire:model.live="searchItem" 
                            wire:change="searchItems"
                            placeholder="Search item by name..."
                            class="flex-1 rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                        />
                    </div>
                    
                    @if($showSearchResults && !empty($filteredItems))
                        <div class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-lg z-10">
                            @foreach($filteredItems as $item)
                                <button 
                                    type="button"
                                    wire:click="selectItem({{ $item->id }})"
                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-200 dark:border-gray-700 last:border-b-0 transition"
                                >
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $item->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Price: {{ currency_format($item->purchase_price, restaurant()->currency_id) }}</div>
                                </button>
                            @endforeach
                        </div>
                    @elseif($showSearchResults && $searchItem)
                        <div class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-lg z-10 p-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center">No items found</p>
                        </div>
                    @endif
                </div>

                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-200">
                            <tr>
                                <th class="px-4 py-2 text-left">Item</th>
                                <th class="px-4 py-2 text-left">Qty</th>
                                <th class="px-4 py-2 text-left">Unit Price</th>
                                <th class="px-4 py-2 text-left">Discount</th>
                                <th class="px-4 py-2 text-right">Subtotal</th>
                                <th class="px-4 py-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($items as $index => $item)
                                <tr wire:key="purchase-item-{{ $item['_key'] ?? $index }}">
                                    <td class="px-4 py-2 min-w-[180px]">
                                        <x-select wire:model.live="items.{{ $index }}.inventory_item_id" wire:change="updateItemPrice({{ $index }})" class="w-full">
                                            <option value="">Select item...</option>
                                            @foreach($inventoryItems as $inventoryItem)
                                                <option value="{{ $inventoryItem->id }}">{{ $inventoryItem->name }}</option>
                                            @endforeach
                                        </x-select>
                                        @error('items.'.$index.'.inventory_item_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-4 py-2 w-28">
                                        <x-input type="number" step="0.01" min="0.01" wire:model.live="items.{{ $index }}.quantity" class="w-full" />
                                        @error('items.'.$index.'.quantity') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-4 py-2 w-32">
                                        <x-input type="number" step="0.01" min="0" wire:model.live="items.{{ $index }}.unit_price" class="w-full" />
                                        @error('items.'.$index.'.unit_price') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-4 py-2 w-40">
                                        <div class="flex gap-2">
                                            <x-input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                wire:model.live="items.{{ $index }}.discount"
                                                placeholder="{{ $item['discount'] ?? 0 }}"
                                                value="{{ $item['discount'] ?? 0 }}"
                                                class="w-full placeholder:text-gray-400 dark:placeholder:text-gray-500 text-gray-900 dark:text-gray-300"
                                            />
                                            <x-select wire:model.live="items.{{ $index }}.discount_type">
                                                <option value="fixed" @selected(($item['discount_type'] ?? 'fixed') === 'fixed')>Fixed</option>
                                                <option value="percentage" @selected(($item['discount_type'] ?? 'fixed') === 'percentage')>%</option>
                                            </x-select>
                                        </div>
                                        @error('items.'.$index.'.discount') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                        @error('items.'.$index.'.discount_type') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-4 py-2 text-right font-semibold">
                                        @php
                                            $lineTotal = ((float)($item['quantity'] ?? 0)) * ((float)($item['unit_price'] ?? 0));
                                            $lineDiscount = ($item['discount_type'] ?? 'fixed') === 'percentage'
                                                ? $lineTotal * (((float)($item['discount'] ?? 0)) / 100)
                                                : ((float)($item['discount'] ?? 0));
                                        @endphp
                                        {{ currency_format(max(0, $lineTotal - $lineDiscount), restaurant()->currency_id) }}
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <x-secondary-button type="button" wire:click="removeItem({{ $index }})">Remove</x-secondary-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center gap-2">
                        <input id="record-payment" type="checkbox" wire:model.live="recordPayment" class="rounded border-gray-300">
                        <label for="record-payment" class="text-sm text-gray-700 dark:text-gray-200">Record payment now</label>
                    </div>

                    @if($recordPayment)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-label value="Payment Amount" />
                                <x-input type="number" step="0.01" min="0" wire:model.live="paymentAmount" class="w-full" />
                                @error('paymentAmount') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-label value="Payment Date" />
                                <x-input type="datetime-local" wire:model.live="paymentDate" class="w-full" />
                                @error('paymentDate') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-label value="Payment Method" />
                                <x-select wire:model.live="paymentMethod" class="w-full">
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method }}">{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                                    @endforeach
                                </x-select>
                            </div>
                            @if(!empty($paymentAccounts) && count($paymentAccounts) > 0)
                                <div>
                                    <x-label value="Payment Account (Optional)" />
                                    <x-select wire:model.live="paymentAccountId" class="w-full">
                                        <option value="">Select Payment Account</option>
                                        @foreach($paymentAccounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                                        @endforeach
                                    </x-select>
                                    @error('paymentAccountId') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                            @endif
                            <div>
                                <x-label value="Payment Note" />
                                <textarea wire:model.live="paymentNote" rows="2" class="w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700"></textarea>
                                @error('paymentNote') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif
                </div>

                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-300">
                        <span>Items Subtotal</span>
                        <span>{{ currency_format($itemSubtotal, restaurant()->currency_id) }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-300">
                        <span>Order Discount</span>
                        <span>-{{ currency_format($discountAmount, restaurant()->currency_id) }}</span>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex justify-between text-lg font-semibold text-gray-900 dark:text-white">
                        <span>Final Total</span>
                        <span>{{ currency_format($finalTotal, restaurant()->currency_id) }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <x-button type="submit" wire:loading.attr="disabled">Save Purchase</x-button>
            </div>
        </form>
    </div>
</div>
