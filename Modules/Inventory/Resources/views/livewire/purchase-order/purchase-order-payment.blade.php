<div>
    <x-modal wire:model="showModal" maxWidth="2xl">
        @if($purchaseOrder)
        <div class="p-6 bg-white dark:bg-gray-800">
            <div class="text-lg font-medium text-gray-900 dark:text-white mb-6">
                Record Payment for {{ $purchaseOrder->po_number }}
            </div>

            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Total Amount:</span>
                        <span class="ml-2 font-semibold text-gray-900 dark:text-white">{{ currency_format($purchaseOrder->total_amount, restaurant()->currency_id) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Paid Amount:</span>
                        <span class="ml-2 font-semibold text-green-600 dark:text-green-400">{{ currency_format($purchaseOrder->paid_amount, restaurant()->currency_id) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Due Amount:</span>
                        <span class="ml-2 font-semibold text-red-600 dark:text-red-400">{{ currency_format($purchaseOrder->due_amount, restaurant()->currency_id) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Payment Status:</span>
                        <span class="ml-2 font-semibold text-gray-900 dark:text-white">
                            @if($purchaseOrder->payment_status === 'paid')
                                <span class="text-green-600">Paid</span>
                            @elseif($purchaseOrder->payment_status === 'partial')
                                <span class="text-yellow-600">Partial</span>
                            @else
                                <span class="text-red-600">Due</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <form wire:submit.prevent="savePayment">
                <!-- Amount -->
                <div class="mb-4">
                    <x-label for="paymentAmount" value="Payment Amount *" class="text-gray-700 dark:text-gray-300" />
                    <x-input type="number" step="0.01" id="paymentAmount" wire:model.live="paymentAmount" 
                            class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" />
                    <x-input-error for="paymentAmount" class="mt-1" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Maximum: {{ currency_format($purchaseOrder->due_amount, restaurant()->currency_id) }}
                    </p>
                </div>

                <!-- Paid On -->
                <div class="mb-4">
                    <x-label for="paymentDate" value="Paid On *" class="text-gray-700 dark:text-gray-300" />
                    <x-input type="datetime-local" id="paymentDate" wire:model="paymentDate" 
                            class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" />
                    <x-input-error for="paymentDate" class="mt-1" />
                </div>

                <!-- Payment Method -->
                <div class="mb-4">
                    <x-label for="paymentMethod" value="Payment Method *" class="text-gray-700 dark:text-gray-300" />
                    <x-select id="paymentMethod" wire:model="paymentMethod" 
                            class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method }}">{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error for="paymentMethod" class="mt-1" />
                </div>

                <!-- Transaction ID -->
                @if(in_array($paymentMethod, ['card', 'bank_transfer', 'upi', 'cheque']))
                <div class="mb-4">
                    <x-label for="transactionId" value="Transaction ID / Reference" class="text-gray-700 dark:text-gray-300" />
                    <x-input type="text" id="transactionId" wire:model="transactionId" 
                            class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" />
                    <x-input-error for="transactionId" class="mt-1" />
                </div>
                @endif

                <!-- Payment Account -->
                <div class="mb-4">
                    <x-label for="paymentAccount" value="Payment Account (Optional)" class="text-gray-700 dark:text-gray-300" />
                    <x-select id="paymentAccount" wire:model="paymentAccount" 
                            class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        <option value="">Select Account...</option>
                        @foreach($paymentAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }} ({{ currency_format($account->current_balance, restaurant()->currency_id) }})</option>
                        @endforeach
                    </x-select>
                    <x-input-error for="paymentAccount" class="mt-1" />
                </div>

                <!-- Document -->
                <div class="mb-4">
                    <x-label for="paymentDocument" value="Attach Payment Proof" class="text-gray-700 dark:text-gray-300" />
                    <input type="file" id="paymentDocument" wire:model="paymentDocument" 
                           class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900 dark:file:text-indigo-300">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Allowed: PDF, Images, CSV, DOC (Max 10MB)</p>
                    <x-input-error for="paymentDocument" class="mt-1" />
                    @if($paymentDocument)
                        <p class="mt-1 text-sm text-green-600 dark:text-green-400">{{ $paymentDocument->getClientOriginalName() }}</p>
                    @endif
                </div>

                <!-- Note -->
                <div class="mb-6">
                    <x-label for="paymentNote" value="Note" class="text-gray-700 dark:text-gray-300" />
                    <textarea id="paymentNote" wire:model="paymentNote" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    <x-input-error for="paymentNote" class="mt-1" />
                </div>

                <div class="flex justify-end space-x-3 pt-5 border-t border-gray-200 dark:border-gray-700">
                    <x-secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled">
                        {{ trans('app.cancel') }}
                    </x-secondary-button>

                    <x-button type="submit" wire:loading.attr="disabled">
                        Record Payment
                    </x-button>
                </div>
            </form>
        </div>
        @endif
    </x-modal>
</div>


