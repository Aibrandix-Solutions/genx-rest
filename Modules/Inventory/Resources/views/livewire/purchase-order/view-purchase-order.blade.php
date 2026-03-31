<div>
    <x-modal wire:model="showModal" maxWidth="4xl">
        @if($purchaseOrder)
        <div class="bg-white dark:bg-gray-800">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ trans('inventory::modules.purchaseOrder.view_title') }}
                    </h3>
                    <span class="px-3 py-1 text-xs font-medium rounded-full
                        {{ $purchaseOrder->status === 'draft' ? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' : '' }}
                        {{ $purchaseOrder->status === 'sent' ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300' : '' }}
                        {{ $purchaseOrder->status === 'received' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300' : '' }}
                        {{ $purchaseOrder->status === 'partially_received' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-300' : '' }}
                        {{ $purchaseOrder->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-300' : '' }}">
                        {{ trans('inventory::modules.purchaseOrder.status.' . $purchaseOrder->status) }}
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
                        @if($purchaseOrder->payments->count() > 0)
                            <span class="ml-2 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-0.5 px-2 rounded-full text-xs">
                                {{ $purchaseOrder->payments->count() }}
                            </span>
                        @endif
                    </button>
                    <button wire:click="setTab('attachments')" 
                            class="{{ $activeTab === 'attachments' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Attachments
                        @if(isset($purchaseOrder->attachments) && $purchaseOrder->attachments->count() > 0)
                            <span class="ml-2 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-0.5 px-2 rounded-full text-xs">
                                {{ $purchaseOrder->attachments->count() }}
                            </span>
                        @endif
                    </button>
                </nav>
            </div>

            <!-- Content -->
            <div class="px-6 py-4">
                @if($activeTab === 'details')
                <!-- Order Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ trans('inventory::modules.purchaseOrder.po_number') }}</h4>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $purchaseOrder->po_number }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ trans('inventory::modules.purchaseOrder.supplier') }}</h4>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $purchaseOrder->supplier->name }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Location</h4>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $purchaseOrder->location?->display_name ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ trans('inventory::modules.purchaseOrder.order_date') }}</h4>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $purchaseOrder->order_date->translatedFormat('M d, Y') }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ trans('inventory::modules.purchaseOrder.expected_delivery_date') }}</h4>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $purchaseOrder->expected_delivery_date?->translatedFormat('M d, Y') ?? '-' }}
                        </p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">{{ trans('inventory::modules.purchaseOrder.items') }}</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('inventory::modules.inventoryItem.name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('inventory::modules.purchaseOrder.unit_price') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('inventory::modules.purchaseOrder.ordered_quantity') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($purchaseOrder->items as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $item->inventoryItem->name ?? 'Item Deleted' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ currency_format($item->unit_price, restaurant()->currency_id) }}

                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ number_format($item->quantity, 2) }}
                                            <span class="text-gray-500 dark:text-gray-400">
                                                ({{ optional($item->inventoryItem?->unit)->symbol ?? '-' }})
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="px-6 py-4 whitespace-nowrap text-lg text-gray-900 dark:text-white font-bold text-right">
                                        {{ trans('modules.billing.total') }}
                                    </td>
                                    <td colspan="1" class="px-6 py-4 whitespace-nowrap text-lg text-gray-900 dark:text-white font-bold">
                                        {{ currency_format($purchaseOrder->total_amount, restaurant()->currency_id) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Notes -->
                @if($purchaseOrder->notes)
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ trans('inventory::modules.purchaseOrder.notes') }}</h4>
                        <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $purchaseOrder->notes }}</p>
                    </div>
                @endif
                @endif

                @if($activeTab === 'payments')
                <!-- Payment Summary -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Total Amount:</span>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ currency_format($purchaseOrder->total_amount, restaurant()->currency_id) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Paid Amount:</span>
                            <p class="text-lg font-semibold text-green-600 dark:text-green-400">{{ currency_format($purchaseOrder->paid_amount, restaurant()->currency_id) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Due Amount:</span>
                            <p class="text-lg font-semibold text-red-600 dark:text-red-400">{{ currency_format($purchaseOrder->due_amount, restaurant()->currency_id) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Status:</span>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                @if($purchaseOrder->payment_status === 'paid')
                                    <span class="text-green-600">Paid</span>
                                @elseif($purchaseOrder->payment_status === 'partial')
                                    <span class="text-yellow-600">Partial</span>
                                @else
                                    <span class="text-red-600">Due</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Add Payment Button -->
                @if($purchaseOrder->due_amount > 0 && in_array($purchaseOrder->status, ['sent', 'partially_received', 'received']))
                <div class="mb-4 flex justify-end">
                    <x-button wire:click="$dispatch('showPurchaseOrderPaymentModal', { purchaseOrder: {{ $purchaseOrder->id }} })">
                        <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Record Payment
                    </x-button>
                </div>
                @endif

                <!-- Payment History -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">Payment History</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Account</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Transaction ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Note</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Document</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($purchaseOrder->payments as $payment)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $payment->paid_on->format('M d, Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white capitalize">
                                            {{ str_replace('_', ' ', $payment->payment_method) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $payment->account->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $payment->transaction_id ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                            {{ $payment->note ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900 dark:text-white">
                                            {{ currency_format($payment->amount, restaurant()->currency_id) }}
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
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No payments recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if($activeTab === 'attachments')
                <div class="py-2">
                    @if(isset($purchaseOrder->attachments) && $purchaseOrder->attachments->count() > 0)
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            @foreach($purchaseOrder->attachments as $att)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-800 flex flex-col items-center justify-center p-2 text-center">
                                    @if($att->is_image)
                                        <a href="{{ $att->url }}" target="_blank"
                                           class="block w-full"
                                           title="{{ $att->original_name }}">
                                            <img src="{{ $att->url }}"
                                                 alt="{{ $att->original_name }}"
                                                 class="w-full h-28 object-cover rounded hover:opacity-90 transition cursor-zoom-in">
                                        </a>
                                    @else
                                        <a href="{{ $att->url }}" target="_blank"
                                           class="flex flex-col items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition"
                                           title="{{ $att->original_name }}">
                                            <svg class="w-12 h-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <span class="text-xs font-medium">Open</span>
                                        </a>
                                    @endif
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 w-full truncate">{{ $att->original_name }}</p>
                                    <a href="{{ $att->url }}" download="{{ $att->original_name }}"
                                       class="mt-1 text-xs text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition inline-flex items-center gap-1">
                                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Download
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center gap-3 py-10 text-gray-400 dark:text-gray-500">
                            <svg class="w-12 h-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                            <p class="text-sm">No attachments for this purchase.</p>
                        </div>
                    @endif
                </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end">
                <div class="flex space-x-3">
                    <x-button wire:click="downloadPdf" wire:loading.attr="disabled" class="mr-3 inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ trans('inventory::modules.purchaseOrder.download_pdf') }}
                    </x-button>

                    <x-secondary-button wire:click="$set('showModal', false)">
                        {{ trans('app.close') }}
                    </x-secondary-button>
                </div>
            </div>
        </div>
        @endif
    </x-modal>
</div> 