<div x-show="showSurchargeFields" x-cloak x-transition.opacity.duration.150ms>
    <label for="{{ $rateInputId }}" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
        @lang('hotel::modules.folio.processingChargeRate')
    </label>
    <div class="flex items-center gap-2 mt-1">
        <input
            id="{{ $rateInputId }}"
            type="number"
            step="0.01"
            min="0"
            max="100"
            x-model="rate"
            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
        />
        <span class="text-gray-500 dark:text-gray-400 font-medium">%</span>
    </div>
</div>

<div
    x-show="showSurchargeFields && surchargeAmount > 0"
    x-cloak
    x-transition.opacity.duration.150ms
    class="rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 p-3 space-y-2"
>
    <div class="flex justify-between text-sm text-amber-900 dark:text-amber-200">
        <span>@lang('hotel::modules.folio.processingFeeLabel') (<span x-text="(parseFloat(rate) || 0).toFixed(2)"></span>%)</span>
        <span class="font-medium"><span x-text="currencySymbol"></span><span x-text="surchargeAmount.toFixed(2)"></span></span>
    </div>
    <div class="flex justify-between text-sm font-semibold text-amber-950 dark:text-amber-100 pt-1 border-t border-amber-200 dark:border-amber-800">
        <span>@lang('hotel::modules.folio.totalToCollect')</span>
        <span><span x-text="currencySymbol"></span><span x-text="totalCollected.toFixed(2)"></span></span>
    </div>
</div>
