<div>
    <x-dialog-modal wire:model.live="show" maxWidth="2xl">
        <x-slot name="title">
            @if($itemId)
                @lang('inventory::modules.consumption.modalTitle', ['name' => $itemName, 'code' => $itemCode])
            @else
                @lang('inventory::modules.consumption.recordConsumption')
            @endif
        </x-slot>

        <x-slot name="content">
            @if($itemId)
                <form wire:submit.prevent="submit" class="space-y-5">
                    {{-- Quantity --}}
                    <div>
                        <label for="consumption-quantity"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            @lang('inventory::modules.consumption.quantity')
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number"
                                   id="consumption-quantity"
                                   wire:model.live="quantity"
                                   step="0.01"
                                   min="0.01"
                                   max="{{ $availableStock }}"
                                   placeholder="0.00"
                                   class="block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm" />
                            @if($itemUnitSymbol)
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $itemUnitSymbol }}</span>
                            @endif
                        </div>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @lang('inventory::modules.consumption.available'):
                            <span class="font-medium">{{ number_format($availableStock, 2) }} {{ $itemUnitSymbol }}</span>
                        </p>

                        {{-- Inline validation: exceeds stock --}}
                        @if(is_numeric($quantity) && (float) $quantity > (float) $availableStock)
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                @lang('inventory::modules.consumption.quantityExceedsStock', [
                                    'available' => number_format((float) $availableStock, 2),
                                    'unit' => (string) $itemUnitSymbol,
                                ])
                            </p>
                        @endif
                        @error('quantity')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Branch & Date row --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="consumption-branch"
                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                @lang('inventory::modules.consumption.branch')
                                <span class="text-red-500">*</span>
                            </label>
                            <select id="consumption-branch"
                                    wire:model.live="branchId"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
                                <option value="">@lang('inventory::modules.consumption.selectBranch')</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branchId')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="consumption-date"
                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                @lang('inventory::modules.consumption.date')
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   id="consumption-date"
                                   wire:model.live="consumptionDate"
                                   max="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                   class="block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm" />
                            @error('consumptionDate')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Menus Used (multi-select with search) --}}
                    <div x-data="{ open: false }" class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            @lang('inventory::modules.consumption.menusUsed')
                        </label>

                        {{-- Selected chips --}}
                        <div class="flex flex-wrap gap-2 p-2 min-h-[42px] rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 cursor-text"
                             @click="open = true; $nextTick(() => $refs.menuSearchInput && $refs.menuSearchInput.focus())">
                            @forelse($selectedMenuItems as $mi)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                                    {{ $mi->item_name }}
                                    <button type="button"
                                            wire:click.stop="removeMenuItem({{ $mi->id }})"
                                            class="hover:text-purple-900 dark:hover:text-purple-100"
                                            aria-label="Remove">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </span>
                            @empty
                                <span class="text-sm text-gray-400 dark:text-gray-500">
                                    @lang('inventory::modules.consumption.menusUsedPlaceholder')
                                </span>
                            @endforelse
                        </div>

                        {{-- Dropdown --}}
                        <div x-show="open"
                             x-on:click.outside="open = false"
                             x-cloak
                             class="absolute z-30 mt-1 w-full rounded-md bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700">
                            <div class="p-2 border-b border-gray-200 dark:border-gray-700">
                                <input type="text"
                                       x-ref="menuSearchInput"
                                       wire:model.live.debounce.250ms="menuSearch"
                                       placeholder="@lang('inventory::modules.consumption.searchMenus')"
                                       class="block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:border-purple-500 focus:ring-purple-500" />
                            </div>
                            <div class="max-h-56 overflow-y-auto py-1">
                                @forelse($menuSearchResults as $mi)
                                    @php $isSelected = in_array($mi->id, $selectedMenuItemIds); @endphp
                                    <button type="button"
                                            wire:click="toggleMenuItem({{ $mi->id }})"
                                            class="w-full flex items-center justify-between px-3 py-2 text-sm hover:bg-purple-50 dark:hover:bg-purple-900/30 {{ $isSelected ? 'bg-purple-50 dark:bg-purple-900/20' : '' }}">
                                        <span class="text-gray-900 dark:text-white">{{ $mi->item_name }}</span>
                                        @if($isSelected)
                                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @endif
                                    </button>
                                @empty
                                    <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                        @lang('inventory::modules.consumption.noMenuItems')
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Note --}}
                    <div>
                        <label for="consumption-note"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            @lang('inventory::modules.consumption.note')
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-normal">
                                ({{ strlen($note) }}/255)
                            </span>
                        </label>
                        <textarea id="consumption-note"
                                  wire:model.live="note"
                                  rows="3"
                                  maxlength="255"
                                  placeholder="@lang('inventory::modules.consumption.notePlaceholder')"
                                  class="block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"></textarea>
                        @error('note')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </form>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeModal" wire:loading.attr="disabled">
                @lang('app.cancel')
            </x-secondary-button>

            <button type="button"
                    wire:click="submit"
                    wire:loading.attr="disabled"
                    @disabled(!is_numeric($quantity) || (float) $quantity <= 0 || (float) $quantity > (float) $availableStock || !$branchId)
                    class="ml-2 inline-flex items-center justify-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition">
                <svg wire:loading wire:target="submit" class="animate-spin w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                @lang('app.submit')
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
