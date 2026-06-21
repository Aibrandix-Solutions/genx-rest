<div>
    <!-- Table -->
    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">@lang('inventory::modules.supplier.name')</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">@lang('inventory::modules.supplier.email')</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">@lang('inventory::modules.supplier.phone')</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">@lang('inventory::modules.supplier.address')</th>
                                <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">@lang('inventory::modules.supplier.outstanding_balance')</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">@lang('app.actions')</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($suppliers as $item)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700" wire:key="supplier-row-{{ $item->id }}">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                        <a href="{{ route('suppliers.show', $item->id) }}" class="underline underline-offset-2" wire:navigate>
                                            {{ $item->name }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $item->email }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $item->phone }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $item->address }}
                                    </td>
                                    @php $balance = $balances[$item->id] ?? 0; @endphp
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-right font-semibold {{ $balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                        {{ currency_format($balance, restaurant()->currency_id) }}
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-2">
                                        <a href="{{ route('suppliers.show', $item->id) }}" wire:navigate class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            @lang('app.view')
                                        </a>
                                        <x-secondary-button wire:click="editSupplier({{ $item->id }})" wire:key="edit-supplier-{{ $item->id }}" class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                                <path d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 012 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                            </svg>
                                            @lang('app.update')
                                        </x-secondary-button>

                                        @if($item->orders_count == 0)
                                        <x-danger-button-table wire:click="deleteSupplier({{ $item->id }})" wire:key="delete-supplier-{{ $item->id }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </x-danger-button-table>
                                        @else
                                        <div class="flex flex-col items-end gap-2">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                @if(user_can('Update Purchase Order') || user_can('Edit Purchase Order'))
                                                    <x-secondary-button type="button" @click="$wire.set('showPurchasePickerModal', true); $wire.set('purchasePickerLoading', true); $wire.openPurchasePicker({{ $item->id }}, 'edit')" wire:loading.attr="disabled" wire:target="openPurchasePicker" class="flex items-center gap-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                                            <path d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 012 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                                        </svg>
                                                        @lang('inventory::modules.supplier.edit_purchase')
                                                    </x-secondary-button>
                                                @endif
                                                @if(user_can('Delete Purchase Order'))
                                                    <x-danger-button-table type="button" @click="$wire.set('showPurchasePickerModal', true); $wire.set('purchasePickerLoading', true); $wire.openPurchasePicker({{ $item->id }}, 'delete')" wire:loading.attr="disabled" wire:target="openPurchasePicker" class="flex items-center gap-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                        @lang('inventory::modules.supplier.delete_purchase')
                                                    </x-danger-button-table>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        @lang('inventory::modules.supplier.noSuppliersFound')
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $suppliers->links() }}
    </div>

    <x-right-modal wire:model="showEditSupplierModal">
        <x-slot name="title">
            @lang('inventory::modules.supplier.editSupplier')
        </x-slot>

        <x-slot name="content">
            @if ($supplier)
            <livewire:inventory::supplier.edit-supplier :supplier="$supplier" wire:key="edit-supplier-form-{{ $supplier->id }}" />
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditSupplierModal', false)" wire:loading.attr="disabled">
                @lang('app.close')
            </x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-confirmation-modal wire:model="confirmDeleteSupplierModal">
        <x-slot name="title">
            @lang('inventory::modules.supplier.deleteSupplier')
        </x-slot>

        <x-slot name="content">
            @lang('inventory::modules.supplier.deleteSupplierMessage')
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteSupplierModal')" wire:loading.attr="disabled">
                @lang('app.cancel')
            </x-secondary-button>

            @if ($supplier)
            <x-danger-button class="ml-3" wire:click='deleteSupplier({{ $supplier->id }})' wire:loading.attr="disabled" wire:key="confirm-delete-supplier-{{ $supplier->id }}">
                @lang('app.delete')
            </x-danger-button>
            @endif
        </x-slot>
    </x-confirmation-modal>

    <x-dialog-modal wire:model="showPurchasePickerModal">
        <x-slot name="title">
            @if($purchasePickerMode === 'delete')
                @lang('inventory::modules.supplier.delete_purchase')
            @else
                @lang('inventory::modules.supplier.edit_purchase')
            @endif
        </x-slot>

        <x-slot name="content">
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                @lang('inventory::modules.supplier.select_purchase_prompt')
            </p>

            @if($purchasePickerLoading)
                <div class="flex items-center justify-center py-10 text-sm text-gray-500">
                    <svg class="animate-spin h-5 w-5 mr-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    @lang('app.loading')
                </div>
            @elseif(count($supplierPurchaseOrders) > 0)
                <div class="mb-4">
                    <input type="text"
                           wire:model.live.debounce.200ms="purchasePickerSearch"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                           placeholder="{{ __('inventory::modules.supplier.search_purchase_placeholder') }}">
                </div>
            @endif

            @if(!$purchasePickerLoading && count($supplierPurchaseOrders) === 0)
                <p class="text-sm text-gray-500">@lang('inventory::modules.purchaseOrder.no_records')</p>
            @elseif(!$purchasePickerLoading && $this->filteredPurchaseOrders->isEmpty())
                <p class="text-sm text-gray-500">@lang('inventory::modules.purchaseOrder.no_records')</p>
            @else
                <div class="max-h-80 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase"></th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">@lang('inventory::modules.purchaseOrder.po_number')</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">@lang('app.date')</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">@lang('inventory::modules.purchaseOrder.total_amount')</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">@lang('app.status')</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                            @foreach($this->filteredPurchaseOrders as $purchaseOrder)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer" wire:click="$set('selectedPurchaseOrderId', {{ $purchaseOrder['id'] }})" wire:key="picker-po-{{ $purchaseOrder['id'] }}">
                                    <td class="px-3 py-2">
                                        <input type="radio" wire:model="selectedPurchaseOrderId" value="{{ $purchaseOrder['id'] }}" class="text-skin-base focus:ring-skin-base">
                                    </td>
                                    <td class="px-3 py-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $purchaseOrder['po_number'] }}
                                        @if(!empty($purchaseOrder['invoice_no']))
                                            <div class="text-xs text-gray-500">{{ $purchaseOrder['invoice_no'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $purchaseOrder['order_date'] }}
                                    </td>
                                    <td class="px-3 py-2 text-sm text-right text-gray-900 dark:text-gray-100">
                                        {{ $purchaseOrder['total_display'] }}
                                    </td>
                                    <td class="px-3 py-2 text-sm capitalize text-gray-600 dark:text-gray-300">
                                        {{ $purchaseOrder['status_label'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button type="button" @click="$wire.set('showPurchasePickerModal', false); $wire.closePurchasePicker()" wire:target="closePurchasePicker,resetPurchaseDeleteState">
                @lang('app.cancel')
            </x-secondary-button>
            @if(count($supplierPurchaseOrders) > 0)
                <x-button class="ml-3" wire:click="proceedPurchasePicker" wire:loading.attr="disabled" wire:target="proceedPurchasePicker">
                    {{ $purchasePickerMode === 'delete' ? __('app.continue') : __('inventory::modules.supplier.edit_selected_purchase') }}
                </x-button>
            @endif
        </x-slot>
    </x-dialog-modal>

    <x-confirmation-modal wire:model="confirmDeletePurchaseModal">
        <x-slot name="title">
            @lang('inventory::modules.supplier.delete_purchase')
        </x-slot>

        <x-slot name="content">
            @lang('inventory::modules.supplier.delete_purchase_message')
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button type="button" @click="$wire.set('confirmDeletePurchaseModal', false); $wire.resetPurchaseDeleteState()" wire:target="resetPurchaseDeleteState,closePurchasePicker">
                @lang('app.cancel')
            </x-secondary-button>
            <x-danger-button class="ml-3" wire:click="deleteSelectedPurchase" wire:loading.attr="disabled" wire:target="deleteSelectedPurchase">
                @lang('app.delete')
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>
