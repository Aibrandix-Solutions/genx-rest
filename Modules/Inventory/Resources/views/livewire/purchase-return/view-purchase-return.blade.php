<div>
    <x-modal wire:model="showModal" maxWidth="4xl">
        @if($purchaseReturn)
        <div class="bg-white dark:bg-gray-800">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Purchase Return Details
                    </h3>
                    <span class="px-3 py-1 text-xs font-medium rounded-full
                        {{ $purchaseReturn->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-300' : '' }}
                        {{ $purchaseReturn->status === 'completed' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300' : '' }}">
                        {{ ucfirst($purchaseReturn->status) }}
                    </span>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button wire:click="setTab('details')" 
                            class="{{ $activeTab === 'details' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Details
                    </button>
                    <button wire:click="setTab('payments')" 
                            class="{{ $activeTab === 'payments' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Payments
                        @if($purchaseReturn->payments->count() > 0)
                            <span class="ml-2 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-0.5 px-2 rounded-full text-xs">
                                {{ $purchaseReturn->payments->count() }}
                            </span>
                        @endif
                    </button>
                </nav>
            </div>

            <!-- Content -->
            <div class="px-6 py-4">
                @if($activeTab === 'details')
                <!-- Return Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Reference Number</h4>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $purchaseReturn->reference_no }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Supplier</h4>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $purchaseReturn->supplier->name }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Purchase Order</h4>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            @if($purchaseReturn->purchaseOrder)
                                <a href="#" 
                                   wire:click.stop="$dispatch('viewPurchaseOrder', { purchaseOrder: {{ $purchaseReturn->purchase_order_id }} })" 
                                   class="text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer">
                                    {{ $purchaseReturn->purchaseOrder->po_number }}
                                </a>
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Return Date</h4>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $purchaseReturn->return_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Location</h4>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            @if($purchaseReturn->purchaseOrder && $purchaseReturn->purchaseOrder->location)
                                {{ $purchaseReturn->purchaseOrder->location->display_name }}
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Created By</h4>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            @if($purchaseReturn->addedBy)
                                {{ $purchaseReturn->addedBy->name }}
                            @elseif($purchaseReturn->added_by)
                                User ID: {{ $purchaseReturn->added_by }}
                            @else
                                System
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">Return Items</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($purchaseReturn->items as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $item->inventoryItem->name ?? 'Item Deleted' }}
                                            @if($item->inventoryItem?->unit)
                                                <span class="text-gray-500 dark:text-gray-400">
                                                    ({{ $item->inventoryItem->unit->symbol }})
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ number_format($item->quantity, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ currency_format($item->unit_price, restaurant()->currency_id) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ currency_format($item->subtotal, restaurant()->currency_id) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 whitespace-nowrap text-lg text-gray-900 dark:text-white font-bold text-right">
                                        Total:
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-lg text-gray-900 dark:text-white font-bold">
                                        {{ currency_format($purchaseReturn->total_amount, restaurant()->currency_id) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Notes -->
                @if($purchaseReturn->note)
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Notes</h4>
                        <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $purchaseReturn->note }}</p>
                    </div>
                @endif

                <!-- Status Information -->
                @if($purchaseReturn->status === 'completed')
                    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-green-800 dark:text-green-300">
                                This return has been processed. Stock has been reduced from inventory.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="text-sm text-yellow-800 dark:text-yellow-300">
                                This return is pending. Process it to reduce stock from inventory.
                            </p>
                        </div>
                    </div>
                @endif
                @elseif($activeTab === 'payments')
                <!-- Payment Summary -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Total Amount:</span>
                            <span class="ml-2 font-semibold text-gray-900 dark:text-white">{{ currency_format($purchaseReturn->total_amount, restaurant()->currency_id) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Refund Received:</span>
                            <span class="ml-2 font-semibold text-green-600 dark:text-green-400">{{ currency_format($purchaseReturn->paid_amount, restaurant()->currency_id) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Due Amount:</span>
                            @php
                                $dueAmount = $purchaseReturn->total_amount - $purchaseReturn->paid_amount;
                            @endphp
                            <span class="ml-2 font-semibold {{ $dueAmount > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ currency_format($dueAmount, restaurant()->currency_id) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Payment Status:</span>
                            <span class="ml-2 font-semibold text-gray-900 dark:text-white">
                                @if($dueAmount <= 0)
                                    <span class="text-green-600">Fully Refunded</span>
                                @elseif($purchaseReturn->paid_amount > 0)
                                    <span class="text-yellow-600">Partial Refund</span>
                                @else
                                    <span class="text-red-600">Pending Refund</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Record Payment Button -->
                @if($dueAmount > 0)
                <div class="mb-6">
                    <x-button wire:click="$dispatch('showPurchaseReturnPaymentModal', { purchaseReturn: {{ $purchaseReturn->id }} })">
                        Record Refund Payment
                    </x-button>
                </div>
                @endif

                <!-- Payment History -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">Payment History</h4>
                    @if($purchaseReturn->payments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Method</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Account</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Transaction ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Note</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Recorded By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Document</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($purchaseReturn->payments as $payment)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $payment->paid_on->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ currency_format($payment->amount, restaurant()->currency_id) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $payment->account ? $payment->account->name : '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $payment->transaction_id ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                {{ $payment->note ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $payment->addedBy ? $payment->addedBy->name : 'System' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if($payment->document_path && \Storage::disk('public')->exists($payment->document_path))
                                                    <a href="{{ asset('storage/' . $payment->document_path) }}" target="_blank" 
                                                       class="inline-flex items-center text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                                       title="View Document">
                                                        <svg class="w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        <span class="text-sm">View</span>
                                                    </a>
                                                @elseif($payment->document_path)
                                                    <span class="text-sm text-yellow-600 dark:text-yellow-400" title="Document not found">Missing</span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No payments recorded yet.</p>
                    @endif
                </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end">
                <x-secondary-button wire:click="$set('showModal', false)">
                    {{ trans('app.close') }}
                </x-secondary-button>
            </div>
        </div>
        @endif
    </x-modal>
    
    @livewire('inventory::purchase-return.purchase-return-payment')
</div>

