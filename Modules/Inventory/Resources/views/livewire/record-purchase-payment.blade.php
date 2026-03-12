<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                {{ trans('inventory::modules.payments.record_payment') }} - {{ $purchase->po_number }}
            </h2>
            <div class="mt-2 flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-gray-400">{{ trans('inventory::modules.payments.supplier_name') }}: {{ $purchase->supplier->name }}</span>
                <span class="text-gray-600 dark:text-gray-400">
                    {{ trans('inventory::modules.payments.due_amount') }}: <span class="font-semibold">{{ currency_format($purchase->due_amount, restaurant()->currency_id) }}</span>
                </span>
            </div>
        </div>

        <form wire:submit.prevent="recordPayment" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-label value="{{ trans('inventory::modules.payments.amount') }}" />
                    <x-input 
                        type="number" 
                        step="0.01" 
                        min="0.01"
                        wire:model.live="paymentAmount" 
                        class="w-full"
                        required 
                    />
                    @error('paymentAmount') 
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p> 
                    @enderror
                </div>

                <div>
                    <x-label value="{{ trans('inventory::modules.payments.payment_date') }}" />
                    <x-input 
                        type="datetime-local" 
                        wire:model.live="paymentDate" 
                        class="w-full"
                        required 
                    />
                    @error('paymentDate') 
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p> 
                    @enderror
                </div>

                <div>
                    <x-label value="{{ trans('inventory::modules.payments.payment_method') }}" />
                    <x-select wire:model.live="paymentMethod" class="w-full" required>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method }}">{{ trans('inventory::modules.payments.payment_methods.' . $method) }}</option>
                        @endforeach
                    </x-select>
                    @error('paymentMethod') 
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p> 
                    @enderror
                </div>

                @if(!empty($paymentAccounts) && count($paymentAccounts) > 0)
                    <div>
                        <x-label value="{{ trans('inventory::modules.payments.payment_account_optional') }}" />
                        <x-select wire:model.live="paymentAccountId" class="w-full">
                            <option value="">{{ trans('inventory::modules.payments.select_payment_account') }}</option>
                            @foreach($paymentAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </x-select>
                        @error('paymentAccountId') 
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p> 
                        @enderror
                    </div>
                @endif

                <div class="{{ empty($paymentAccounts) || count($paymentAccounts) == 0 ? '' : 'md:col-span-2' }}">
                    <x-label value="{{ trans('inventory::modules.payments.notes') }} {{ trans('inventory::modules.payments.optional') }}" />
                    <textarea 
                        wire:model.live="paymentNote" 
                        rows="2" 
                        class="w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                        placeholder="{{ trans('inventory::modules.payments.note') }}..."
                    ></textarea>
                    @error('paymentNote') 
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p> 
                    @enderror
                </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <strong>Amount to be paid:</strong> {{ currency_format($paymentAmount, restaurant()->currency_id) }}
                </p>
            </div>

            <div class="flex items-center justify-end gap-2">
                <x-secondary-button type="button" wire:click="resetForm">{{ trans('inventory::modules.payments.clear') }}</x-secondary-button>
                <x-button type="submit" wire:loading.attr="disabled">{{ trans('inventory::modules.payments.record') }}</x-button>
            </div>
        </form>
    </div>
</div>
