<div>
    <div class="p-4 mx-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-xl font-semibold dark:text-white">@lang('modules.combo.comboPacks')</h3>
                <x-help-text class="mt-2">Create combo packs by grouping menu items together with a discounted price.</x-help-text>
            </div>
            @if (!$showComboForm)
                <x-button wire:click="openCreateForm" wire:loading.attr="disabled">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    @lang('modules.combo.createComboPack')
                </x-button>
            @endif
        </div>

        @if ($showComboForm)
            <!-- Combo Pack Form -->
            <form wire:submit.prevent="saveCombo" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Name -->
                    <div>
                        <x-label for="name" :value="__('modules.combo.comboPackName')" />
                        <x-input id="name" type="text" class="block mt-1 w-full" wire:model="name" />
                        <x-input-error for="name" class="mt-2" />
                    </div>

                    <!-- Image -->
                    <div>
                        <x-label for="imageTemp" :value="__('modules.combo.image')" />
                        <x-input id="imageTemp" type="file" accept="image/*" class="block mt-1 w-full" wire:model="imageTemp" />
                        @if ($image && !$imageTemp)
                            <div class="mt-2 flex items-center gap-3">
                                <img src="{{ asset_url_local_s3('combo_packs/' . $image) }}" alt="Combo Image" class="h-20 w-20 object-cover rounded">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Current image</span>
                            </div>
                        @elseif ($imageTemp)
                            <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">New image selected &mdash; will replace the current one on save.</p>
                        @endif
                        <x-input-error for="imageTemp" class="mt-2" />
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <x-label for="description" :value="__('modules.combo.description')" />
                    <x-textarea id="description" class="block mt-1 w-full" wire:model="description" rows="3" />
                    <x-input-error for="description" class="mt-2" />
                </div>

                <!-- Select Items -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h4 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">@lang('modules.combo.selectItems')</h4>
                    
                    <!-- Available Items -->
                    <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-label value="Available Menu Items" class="mb-2" />
                        <div class="grid grid-cols-2 gap-2 md:grid-cols-4 lg:grid-cols-6 max-h-48 overflow-y-auto">
                            @foreach ($availableMenuItems as $menuItem)
                                @php
                                    $hasVariations = $menuItem->variations->count() > 0;
                                    $itemOutOfStock = !($menuItem->in_stock ?? true);
                                    $itemUnavailable = isset($menuItem->is_available) && !$menuItem->is_available;
                                @endphp
                                @if ($hasVariations)
                                    @foreach ($menuItem->variations as $variation)
                                        @php
                                            $key = $menuItem->id . '_' . $variation->id;
                                            $isSelected = in_array($key, $selectedItems);
                                            $isDisabled = $isSelected || $itemOutOfStock || $itemUnavailable;
                                        @endphp
                                        <div class="relative flex flex-col">
                                            <button
                                                type="button"
                                                wire:click="addItem('{{ $menuItem->id }}_{{ $variation->id }}')"
                                                @class([
                                                    'p-2 text-sm border rounded transition',
                                                    'hover:bg-skin-base hover:text-white' => !$isDisabled,
                                                    'bg-skin-base text-white border-skin-base' => $isSelected,
                                                    'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600' => !$isSelected && !$isDisabled,
                                                    'opacity-50 cursor-not-allowed bg-gray-100 dark:bg-gray-700 border-gray-200' => $isDisabled && !$isSelected,
                                                ])
                                                @disabled($isDisabled)
                                            >
                                                {{ $menuItem->item_name }} - {{ $variation->variation }}
                                                <span class="block text-xs font-semibold text-blue-600 dark:text-blue-400">{{ currency_format($variation->price, restaurant()->currency_id) }}</span>
                                                @if ($itemOutOfStock)
                                                    <span class="block text-xs text-red-500 font-semibold">Out of stock</span>
                                                @elseif ($itemUnavailable)
                                                    <span class="block text-xs text-orange-500 font-semibold">Unavailable</span>
                                                @endif
                                            </button>
                                        </div>
                                    @endforeach
                                @else
                                    @php
                                        $key = $menuItem->id . '_0';
                                        $isSelected = in_array($key, $selectedItems);
                                        $isDisabled = $isSelected || $itemOutOfStock || $itemUnavailable;
                                    @endphp
                                    <div class="relative flex flex-col">
                                        <button
                                            type="button"
                                            wire:click="addItem('{{ $menuItem->id }}')"
                                            @class([
                                                'p-2 text-sm border rounded transition',
                                                'hover:bg-skin-base hover:text-white' => !$isDisabled,
                                                'bg-skin-base text-white border-skin-base' => $isSelected,
                                                'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600' => !$isSelected && !$isDisabled,
                                                'opacity-50 cursor-not-allowed bg-gray-100 dark:bg-gray-700 border-gray-200' => $isDisabled && !$isSelected,
                                            ])
                                            @disabled($isDisabled)
                                        >
                                            {{ $menuItem->item_name }}                                            <span class="block text-xs font-semibold text-blue-600 dark:text-blue-400">{{ currency_format($menuItem->price, restaurant()->currency_id) }}</span>                                            @if ($itemOutOfStock)
                                                <span class="block text-xs text-red-500 font-semibold">Out of stock</span>
                                            @elseif ($itemUnavailable)
                                                <span class="block text-xs text-orange-500 font-semibold">Unavailable</span>
                                            @endif
                                        </button>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <!-- Selected Items -->
                    <div>
                        <x-label value="Selected Items" class="mb-2" />
                        @if (count($selectedItems) > 0)
                            {{-- Header row --}}
                            <div class="hidden md:flex items-center gap-3 px-3 py-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <div class="flex-1">Item</div>
                                <div class="w-24 text-right">Unit Price</div>
                                <div class="w-20">Qty</div>
                                <div class="w-24 text-right">Subtotal</div>
                                <div class="w-8"></div>
                            </div>
                            <div class="space-y-2">
                                @foreach ($selectedItems as $key)
                                    @php
                                        [$menuItemId, $variationId] = explode('_', $key);
                                        $menuItem = $availableMenuItems->find($menuItemId);
                                        $variationId = $variationId !== '0' ? (int)$variationId : null;
                                        $variation = $variationId ? $menuItem->variations->find($variationId) : null;
                                        $unitPrice = $variation ? (float)$variation->price : (float)($menuItem->price ?? 0);
                                        $qty = (int)($itemQuantities[$key] ?? 1);
                                        $lineSubtotal = $unitPrice * $qty;
                                    @endphp
                                    @if ($menuItem)
                                        <div class="flex flex-wrap md:flex-nowrap items-center gap-3 p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded">
                                            <div class="flex-1 min-w-0">
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $menuItem->item_name }}</span>
                                                @if ($variation)
                                                    <span class="text-sm text-gray-500"> &mdash; {{ $variation->variation }}</span>
                                                @endif
                                            </div>
                                            <div class="w-24 text-right text-sm text-gray-500 dark:text-gray-400">
                                                {{ currency_format($unitPrice, restaurant()->currency_id) }}
                                            </div>
                                            <div class="w-20">
                                                <x-input
                                                    type="number"
                                                    min="1"
                                                    class="w-full h-8 text-sm text-center"
                                                    wire:model.live="itemQuantities.{{ $key }}"
                                                />
                                            </div>
                                            <div class="w-24 text-right text-sm font-semibold text-gray-800 dark:text-gray-200">
                                                {{ currency_format($lineSubtotal, restaurant()->currency_id) }}
                                            </div>
                                            <div class="w-8">
                                                <x-danger-button-table
                                                    type="button"
                                                    wire:click="removeItem('{{ $key }}')"
                                                >
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </x-danger-button-table>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No items selected. Click on items above to add them to the combo.</p>
                        @endif
                        <x-input-error for="selectedItems" class="mt-2" />
                    </div>
                </div>

                <!-- Pricing -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h4 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">Pricing</h4>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded">
                            <x-label value="Regular Price (Auto-calculated)" class="text-sm" />
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ currency_format($regularPrice, restaurant()->currency_id) }}</p>
                        </div>
                        <div>
                            <x-label for="discountType" :value="__('modules.combo.discountType')" />
                            <x-select id="discountType" class="block mt-1 w-full" wire:model.live="discountType">
                                <option value="fixed">@lang('modules.combo.fixed')</option>
                                <option value="percent">@lang('modules.combo.percent')</option>
                            </x-select>
                        </div>
                        <div>
                            <x-label for="discountValue" :value="__('modules.combo.discountValue')" />
                            <x-input 
                                id="discountValue" 
                                type="number" 
                                step="0.01" 
                                min="0"
                                class="block mt-1 w-full" 
                                wire:model.live="discountValue"
                            />
                            <x-input-error for="discountValue" class="mt-2" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mt-4">
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded">
                            <x-label value="Discounted Price" class="text-sm" />
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ currency_format($discountedPrice, restaurant()->currency_id) }}</p>
                        </div>
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded">
                            <x-label value="Discount Amount" class="text-sm" />
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ currency_format($discountAmount, restaurant()->currency_id) }} 
                                @if ($discountPercent > 0)
                                    <span class="text-sm">({{ number_format($discountPercent, 1) }}%)</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Additional Settings -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="flex items-center">
                            <x-checkbox id="isActive" wire:model="isActive" />
                            <x-label for="isActive" :value="__('modules.combo.isActive')" class="ml-2" />
                        </div>
                        <div>
                            <x-label for="sortOrder" :value="__('modules.combo.sortOrder')" />
                            <x-input id="sortOrder" type="number" class="block mt-1 w-full" wire:model="sortOrder" />
                            <x-help-text class="mt-1">Lower numbers appear first. Leave blank to sort by creation date.</x-help-text>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <x-secondary-button type="button" wire:click="cancelForm" wire:loading.attr="disabled">
                        @lang('modules.combo.cancel')
                    </x-secondary-button>
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="saveCombo">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="saveCombo">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg wire:loading wire:target="saveCombo" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        @lang('modules.combo.saveComboPack')
                    </x-button>
                </div>
            </form>
        @else
            <!-- Combo Packs List -->
            @if ($comboPacks->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">@lang('modules.combo.comboPackName')</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Regular Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Discounted Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Discount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-300">@lang('app.action')</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach ($comboPacks as $combo)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            @if ($combo->image)
                                                <img src="{{ $combo->combo_image_url }}" alt="{{ $combo->getTranslation('name', app()->getLocale()) }}" class="h-12 w-12 object-cover rounded">
                                            @endif
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $combo->getTranslation('name', app()->getLocale()) }}</div>
                                                @if ($combo->getTranslation('description', app()->getLocale()))
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($combo->getTranslation('description', app()->getLocale()), 50) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="space-y-1">
                                            @foreach ($combo->comboPackItems as $cItem)
                                                <div class="text-sm text-gray-900 dark:text-white whitespace-nowrap">
                                                    <span class="font-medium">{{ $cItem->quantity }}&times;</span>
                                                    {{ $cItem->menuItem?->item_name ?? '&mdash;' }}
                                                    @if ($cItem->menuItemVariation)
                                                        <span class="text-xs text-gray-500">({{ $cItem->menuItemVariation->variation }})</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900 dark:text-white line-through">{{ currency_format($combo->regular_price, restaurant()->currency_id) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ currency_format($combo->discounted_price, restaurant()->currency_id) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                            {{ currency_format($combo->discount_amount, restaurant()->currency_id) }}
                                            @if ($combo->discount_percent)
                                                <span class="text-xs">({{ number_format($combo->discount_percent, 1) }}%)</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" wire:click="toggleActive({{ $combo->id }})" {{ $combo->is_active ? 'checked' : '' }} class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                        </label>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <x-secondary-button-table wire:click="openEditForm({{ $combo->id }})" wire:loading.attr="disabled">
                                                @lang('modules.combo.edit')
                                            </x-secondary-button-table>
                                            <x-danger-button-table wire:click="deleteCombo({{ $combo->id }})" wire:loading.attr="disabled" onclick="return confirm('{{ __('modules.combo.deleteConfirmation') }}')">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                            </x-danger-button-table>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center justify-center p-12 text-center">
                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="text-lg font-medium text-gray-500 dark:text-gray-400 mb-2">@lang('modules.combo.noComboPacks')</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Create your first combo pack to offer discounted meal bundles to customers.</p>
                    <x-button wire:click="openCreateForm">
                        @lang('modules.combo.createComboPack')
                    </x-button>
                </div>
            @endif
        @endif
    </div>

</div>
