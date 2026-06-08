<div>
    <x-dialog-modal wire:model.live="show" maxWidth="lg">
        <x-slot name="title">
            @if($itemId)
                @lang('inventory::modules.disposal.modalTitle', ['name' => $itemName, 'code' => $itemCode])
            @else
                @lang('inventory::modules.disposal.recordDisposal')
            @endif
        </x-slot>

        <x-slot name="content">
            @if($itemId)
                <form wire:submit.prevent="submit" class="space-y-5">

                    {{-- Available stock info banner --}}
                    <div class="rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 px-4 py-3 flex items-center gap-3">
                        <svg class="w-5 h-5 text-red-500 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <div class="text-sm text-red-700 dark:text-red-300">
                            <span class="font-semibold">@lang('inventory::modules.disposal.wastageWarning')</span>
                            — @lang('inventory::modules.disposal.available'):
                            <span class="font-medium">{{ number_format($availableStock, 2) }} {{ $itemUnitSymbol }}</span>
                        </div>
                    </div>

                    {{-- Quantity --}}
                    <div>
                        <label for="disposal-quantity"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            @lang('inventory::modules.disposal.quantity')
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number"
                                   id="disposal-quantity"
                                   wire:model.live="quantity"
                                   step="0.01"
                                   min="0.01"
                                   max="{{ $availableStock }}"
                                   placeholder="0.00"
                                   class="block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" />
                            @if($itemUnitSymbol)
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $itemUnitSymbol }}</span>
                            @endif
                        </div>

                        @if(is_numeric($quantity) && (float) $quantity > (float) $availableStock)
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                @lang('inventory::modules.disposal.quantityExceedsStock', [
                                    'available' => number_format((float) $availableStock, 2),
                                    'unit' => (string) $itemUnitSymbol,
                                ])
                            </p>
                        @endif
                        @error('quantity')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Branch & Date --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="disposal-branch"
                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                @lang('inventory::modules.disposal.branch')
                                <span class="text-red-500">*</span>
                            </label>
                            <select id="disposal-branch"
                                    wire:model.live="branchId"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                                <option value="">@lang('inventory::modules.disposal.selectBranch')</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branchId')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="disposal-date"
                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                @lang('inventory::modules.disposal.date')
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   id="disposal-date"
                                   wire:model.live="disposalDate"
                                   max="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                   class="block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" />
                            @error('disposalDate')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Reason --}}
                    <div>
                        <label for="disposal-reason"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            @lang('inventory::modules.disposal.reason')
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-normal">
                                ({{ strlen($reason) }}/255)
                            </span>
                        </label>
                        <textarea id="disposal-reason"
                                  wire:model.live="reason"
                                  rows="3"
                                  maxlength="255"
                                  placeholder="@lang('inventory::modules.disposal.reasonPlaceholder')"
                                  class="block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"></textarea>
                        @error('reason')
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
                    class="ml-2 inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition">
                <svg wire:loading wire:target="submit" class="animate-spin w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <svg wire:loading.remove wire:target="submit" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                @lang('inventory::modules.disposal.confirmDispose')
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
