<div class="p-4">
    <!-- Filters -->
    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-1">
                <x-input type="text" wire:model.live.debounce.300ms="search" 
                       class="block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                       placeholder="Search by reference, supplier, PO..." />
            </div>
            <div>
                <x-select wire:model.live="supplierId" 
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-select wire:model.live="purchaseOrderId" 
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Purchase Orders</option>
                    @foreach($purchaseOrders as $po)
                        <option value="{{ $po->id }}">{{ $po->po_number }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-select wire:model.live="status" 
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                </x-select>
            </div>
            <div>
                 <x-input type="date" wire:model.live="startDate" class="block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="Start Date" />
            </div>
            <div>
                 <x-input type="date" wire:model.live="endDate" class="block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="End Date" />
            </div>
            <div>
                 <x-select wire:model.live="perPage" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                     <option value="10">10 per page</option>
                     <option value="20">20 per page</option>
                     <option value="50">50 per page</option>
                     <option value="100">100 per page</option>
                 </x-select>
            </div>
            <div class="flex items-center">
                @if($search || $supplierId || $purchaseOrderId || $status || $startDate || $endDate)
                    <button wire:click="clearFilters" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 underline">
                        Clear Filters
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="mb-6 flex justify-end gap-2">
        <x-secondary-button wire:click="export" wire:loading.attr="disabled">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Export
        </x-secondary-button>

        @if(user_can('Create Purchase Return'))
        <x-button wire:click="$dispatch('showPurchaseReturnModal')">
            Create Purchase Return
        </x-button>
        @endif
    </div>

    <!-- Purchase Returns Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Purchase Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Return Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Amount</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Due Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Payment Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($purchaseReturns as $purchaseReturn)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $purchaseReturn->reference_no }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $purchaseReturn->supplier->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($purchaseReturn->purchaseOrder)
                                    <a href="#" wire:click="$dispatch('viewPurchaseOrder', { purchaseOrder: {{ $purchaseReturn->purchase_order_id }} })" 
                                       class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $purchaseReturn->purchaseOrder->po_number }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $purchaseReturn->return_date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $purchaseReturn->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-300' : '' }}
                                    {{ $purchaseReturn->status === 'completed' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300' : '' }}">
                                    {{ ucfirst($purchaseReturn->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900 dark:text-white">
                                {{ currency_format($purchaseReturn->total_amount, restaurant()->currency_id) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold {{ $purchaseReturn->due_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ currency_format($purchaseReturn->due_amount, restaurant()->currency_id) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $paymentStatus = $purchaseReturn->payment_status;
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $paymentStatus === 'paid' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300' : '' }}
                                    {{ $paymentStatus === 'partial' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-300' : '' }}
                                    {{ $paymentStatus === 'due' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-300' : '' }}">
                                    @if($paymentStatus === 'paid')
                                        {{ trans('inventory::modules.purchaseOrder.payment_status.paid') }}
                                    @elseif($paymentStatus === 'partial')
                                        {{ trans('inventory::modules.purchaseOrder.payment_status.partial') }}
                                    @else
                                        {{ trans('inventory::modules.purchaseOrder.payment_status.due') }}
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="relative inline-block" x-data="{ 
                                    open: false,
                                    positionAbove: false,
                                    checkPosition() {
                                        $nextTick(() => {
                                            const button = this.$refs.actionButton;
                                            const buttonRect = button.getBoundingClientRect();
                                            const dropdownHeight = 250;
                                            const spaceBelow = window.innerHeight - buttonRect.bottom;
                                            const spaceAbove = buttonRect.top;
                                            
                                            this.positionAbove = spaceBelow < dropdownHeight && spaceAbove > spaceBelow;
                                        });
                                    }
                                }">
                                    <button x-ref="actionButton"
                                            @click="open = !open; if (open) checkPosition();"
                                            @click.away="open = false"
                                            class="inline-flex items-center justify-center w-8 h-8 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full focus:outline-none">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                    <div x-show="open"
                                         x-transition
                                         x-cloak
                                         @click.away="open = false"
                                         :class="positionAbove ? 'bottom-full mb-2 origin-bottom-right' : 'top-full mt-2 origin-top-right'"
                                         class="absolute right-0 z-50 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5">
                                        <div class="py-1 flex flex-col gap-1">
                                            @if(user_can('Show Purchase Return'))
                                                <button wire:click="$dispatch('viewPurchaseReturn', { purchaseReturn: {{ $purchaseReturn->id }} })" @click="open = false"
                                                        class="w-full flex items-center px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                                    <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <span>View</span>
                                                </button>
                                            @endif

                                            @if($purchaseReturn->status === 'pending' && user_can('Update Purchase Return'))
                                                <button wire:click="$dispatch('editPurchaseReturn', { purchaseReturn: {{ $purchaseReturn->id }} })" @click="open = false"
                                                        class="w-full flex items-center px-4 py-2 text-sm text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/50">
                                                    <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    <span>Edit</span>
                                                </button>
                                            @endif

                                            @if($purchaseReturn->status === 'pending' && user_can('Delete Purchase Return'))
                                                <button wire:click="confirmDelete({{ $purchaseReturn->id }})" @click="open = false"
                                                        class="w-full flex items-center px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/50">
                                                    <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    <span>Delete</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                No purchase returns found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $purchaseReturns->links() }}
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <x-confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">
            Delete Purchase Return
        </x-slot>

        <x-slot name="content">
            Are you sure you want to delete this purchase return? This action cannot be undone.
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('confirmingDeletion', false)" wire:loading.attr="disabled">
                Cancel
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="delete" wire:loading.attr="disabled">
                Delete
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>

    <livewire:inventory::purchase-return.manage-purchase-return />
    <livewire:inventory::purchase-return.view-purchase-return />
    <livewire:inventory::purchase-return.purchase-return-payment />
</div>

