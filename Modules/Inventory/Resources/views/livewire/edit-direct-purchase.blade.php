<div class="p-4">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">Edit Purchase #{{ $purchase->po_number ?? '' }}</h1>
        <form wire:submit.prevent="updatePurchase" class="space-y-6">
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
                        <x-input type="number" step="0.01" min="0" wire:model.live="discount" placeholder="0.00" class="w-full" />
                        <x-select wire:model.live="discount_type" class="min-w-[100px]">
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
                    <x-label value="Search and Add More Items" />
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="searchItem" 
                            wire:keydown.escape="$set('showSearchResults', false)"
                            placeholder="Search item by name..."
                            class="flex-1 rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                        />
                    </div>
                    
                    @if($showSearchResults && !empty($filteredItems))
                        <div class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-lg z-10 max-h-60 overflow-auto">
                            @foreach($filteredItems as $item)
                                <button 
                                    type="button"
                                    wire:click="selectItem({{ $item->id }})"
                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-200 dark:border-gray-700 last:border-b-0 transition"
                                >
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $item->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Price: {{ currency_format($item->unit_purchase_price, restaurant()->currency_id) }}</div>
                                </button>
                            @endforeach
                        </div>
                    @elseif($showSearchResults && $searchItem)
                        <div class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-lg z-10 p-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center">No items found</p>
                        </div>
                    @endif
                </div>

                <!-- Mobile (card layout) -->
                <div class="space-y-3 md:hidden">
                    @foreach($items as $index => $item)
                        <div wire:key="purchase-item-card-{{ $item['_key'] ?? $index }}" class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                            <div class="space-y-3">
                                <div>
                                    <x-label value="Item" />
                                    <x-select wire:model.live="items.{{ $index }}.inventory_item_id" wire:change="updateItemPrice({{ $index }})" class="w-full">
                                        <option value="">Select item...</option>
                                        @foreach($inventoryItems as $inventoryItem)
                                            <option value="{{ $inventoryItem->id }}">{{ $inventoryItem->name }}</option>
                                        @endforeach
                                    </x-select>
                                    @error('items.'.$index.'.inventory_item_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <x-label value="Qty" />
                                        <x-input type="number" step="0.01" min="0.01" wire:model.live="items.{{ $index }}.quantity" class="w-full text-base" />
                                        @error('items.'.$index.'.quantity') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-label value="Unit Price" />
                                        <x-input type="number" step="0.01" min="0" wire:model.live="items.{{ $index }}.unit_price" class="w-full text-base" />
                                        @error('items.'.$index.'.unit_price') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <x-label value="Discount" />
                                        <x-input type="number" step="0.01" min="0" wire:model.live="items.{{ $index }}.discount" class="w-full text-base" />
                                        @error('items.'.$index.'.discount') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-label value="Type" />
                                        <x-select wire:model.live="items.{{ $index }}.discount_type" class="w-full">
                                            <option value="fixed" @selected(($item['discount_type'] ?? 'fixed') === 'fixed')>Fixed</option>
                                            <option value="percentage" @selected(($item['discount_type'] ?? 'fixed') === 'percentage')>%</option>
                                        </x-select>
                                        @error('items.'.$index.'.discount_type') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-300">Subtotal</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        @php
                                            $lineTotal = ((float)($item['quantity'] ?? 0)) * ((float)($item['unit_price'] ?? 0));
                                            $lineDiscount = ($item['discount_type'] ?? 'fixed') === 'percentage'
                                                ? $lineTotal * (((float)($item['discount'] ?? 0)) / 100)
                                                : ((float)($item['discount'] ?? 0));
                                        @endphp
                                        {{ currency_format(max(0, $lineTotal - $lineDiscount), restaurant()->currency_id) }}
                                    </span>
                                </div>

                                <div class="flex justify-end">
                                    <x-secondary-button type="button" wire:click="removeItem({{ $index }})">Remove</x-secondary-button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Desktop (table layout) -->
                <div class="hidden md:block overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                    <table class="min-w-[900px] w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
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
                                    <td class="px-4 py-2 min-w-[140px] md:w-28">
                                        <x-input type="number" step="0.01" min="0.01" wire:model.live="items.{{ $index }}.quantity" class="w-full text-base md:text-sm" />
                                        @error('items.'.$index.'.quantity') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-4 py-2 min-w-[160px] md:w-32">
                                        <x-input type="number" step="0.01" min="0" wire:model.live="items.{{ $index }}.unit_price" class="w-full text-base md:text-sm" />
                                        @error('items.'.$index.'.unit_price') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-4 py-2 min-w-[240px] md:w-48">
                                        <div class="flex flex-col sm:flex-row gap-2">
                                            <x-input type="number" step="0.01" min="0" wire:model.live="items.{{ $index }}.discount" class="w-full text-base md:text-sm" />
                                            <x-select wire:model.live="items.{{ $index }}.discount_type" class="w-full sm:w-auto">
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
                        <label for="record-payment" class="text-sm text-gray-700 dark:text-gray-200">Add payment for this purchase</label>
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
                            <div class="{{ empty($paymentAccounts) || count($paymentAccounts) == 0 ? '' : 'md:col-span-2' }}">
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

            {{-- ===== ATTACHMENTS ===== --}}
            <div class="space-y-3">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Attachments</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Attach invoices or images (PDF, JPG, PNG, GIF, WEBP – max 5 MB each).</p>

                {{-- Existing saved attachments --}}
                @if(!empty($existingAttachments))
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        @foreach($existingAttachments as $att)
                            <div class="relative group border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-800 flex flex-col items-center justify-center p-2 text-center">
                                @if($att['file_type'] === 'image')
                                    <a href="{{ $att['url'] }}" target="_blank" class="block w-full">
                                        <img src="{{ $att['url'] }}" alt="{{ $att['original_name'] }}" class="w-full h-20 object-cover rounded">
                                    </a>
                                @else
                                    <a href="{{ $att['url'] }}" target="_blank" class="flex flex-col items-center gap-1">
                                        <svg class="w-10 h-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </a>
                                @endif
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 truncate w-full">{{ $att['original_name'] }}</p>
                                <button type="button"
                                        x-on:click="Swal.fire({
                                            title: '{{ __('app.delete') }} {{ __('app.file') }}?',
                                            text: 'This action cannot be undone.',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#ef4444',
                                            cancelButtonColor: '#6b7280',
                                            confirmButtonText: '{{ __('app.delete') }}',
                                            cancelButtonText: '{{ __('app.cancel') }}'
                                        }).then(result => { if (result.isConfirmed) $wire.deleteAttachment({{ $att['id'] }}) })"
                                        class="mt-1 text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 transition">
                                    {{ __('app.delete') }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Add new files (Alpine-managed preview) --}}
                <div x-data="{
                        files: [],
                        syncing: false,
                        addFiles(newFiles) {
                            if (this.syncing) { this.syncing = false; return; }
                            Array.from(newFiles).forEach(f => {
                                this.files.push({
                                    name: f.name,
                                    isImage: f.type.startsWith('image/'),
                                    url: f.type.startsWith('image/') ? URL.createObjectURL(f) : null,
                                    file: f
                                });
                            });
                            this.syncToLivewire();
                        },
                        removeFile(index) {
                            this.files.splice(index, 1);
                            this.syncToLivewire();
                        },
                        syncToLivewire() {
                            const dt = new DataTransfer();
                            this.files.forEach(f => dt.items.add(f.file));
                            const input = document.getElementById('edit-purchase-attachment-input');
                            input.files = dt.files;
                            this.syncing = true;
                            input.dispatchEvent(new Event('change'));
                        }
                     }" class="space-y-3">

                    <div class="flex flex-wrap gap-3 items-center">
                        <label for="edit-purchase-attachment-input"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-700 dark:text-gray-200 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                            Add Files
                        </label>
                        <input id="edit-purchase-attachment-input"
                               type="file"
                               wire:model="attachments"
                               multiple
                               accept="image/*,.pdf"
                               class="sr-only"
                               @change="addFiles($event.target.files)" />

                        <label for="edit-purchase-camera-input"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-blue-300 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/40 text-sm text-blue-700 dark:text-blue-300 cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-800/50 transition">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Capture Photo
                        </label>
                        <input id="edit-purchase-camera-input"
                               type="file"
                               accept="image/*"
                               capture="environment"
                               class="sr-only"
                               @change="addFiles($event.target.files); $event.target.value = ''" />
                    </div>

                    <div wire:loading wire:target="attachments" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
                        <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        Uploading...
                    </div>

                    @error('attachments.*') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror

                    {{-- New file previews (Alpine persisted) --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 mt-2">
                        <template x-for="(file, index) in files" :key="index">
                            <div class="relative border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-800 flex flex-col items-center justify-center p-2 text-center group">
                                <button type="button"
                                        @click="removeFile(index)"
                                        class="absolute top-1 right-1 z-10 w-5 h-5 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-xs shadow transition"
                                        title="Remove">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                                <template x-if="file.isImage">
                                    <img :src="file.url" class="w-full h-20 object-cover rounded" :alt="file.name">
                                </template>
                                <template x-if="!file.isImage">
                                    <div class="w-12 h-12 flex items-center justify-center text-gray-400 dark:text-gray-500 mt-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                </template>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 truncate w-full" x-text="file.name"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('purchases.index') }}" class="inline-block px-4 py-2 bg-gray-400 text-white rounded-md hover:bg-gray-500 transition">
                    Cancel
                </a>
                <x-button type="submit" wire:loading.attr="disabled">Update Purchase</x-button>
            </div>
        </form>
    </div>
</div>
