<div class="max-w-4xl mx-auto p-4 space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">@lang('hotel::modules.settings.title')</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">@lang('hotel::modules.settings.subtitle')</p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">

        {{-- ═══════════ Hotel Identity ═══════════ --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    @lang('hotel::modules.settings.hotelIdentity')
                </h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <x-label for="hotel_name" value="{{ __('hotel::modules.settings.hotelName') }}" />
                    <x-input id="hotel_name" type="text" class="block w-full mt-1" wire:model="hotel_name" placeholder="e.g. Grand Palace Hotel & Resort" required />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('hotel::modules.settings.hotelNameHint')</p>
                    <x-input-error for="hotel_name" class="mt-2" />
                </div>

                {{-- Hotel Logo Upload --}}
                <div>
                    <x-label for="hotel_logo" value="{{ __('hotel::modules.settings.hotelLogo') }}" />
                    <div class="mt-2 flex items-center gap-4">
                        {{-- Preview --}}
                        @if($hotel_logo)
                            <img src="{{ $hotel_logo->temporaryUrl() }}" alt="Logo preview" class="h-16 w-16 object-contain rounded border border-gray-200 dark:border-gray-600 bg-white">
                        @elseif($existing_logo)
                            <img src="{{ asset_url_local_s3('hotel-logo/' . $existing_logo) }}" alt="Hotel logo" class="h-16 w-16 object-contain rounded border border-gray-200 dark:border-gray-600 bg-white">
                        @else
                            <div class="h-16 w-16 flex items-center justify-center rounded border-2 border-dashed border-gray-300 dark:border-gray-600 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        @endif

                        <div class="flex-1">
                            <input type="file" id="hotel_logo" wire:model="hotel_logo" accept="image/jpeg,image/png,image/svg+xml,image/webp" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-300" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">JPG, PNG, SVG or WebP. Max 2MB. Recommended: 300x300px.</p>
                            <x-input-error for="hotel_logo" class="mt-1" />
                        </div>

                        @if($existing_logo)
                            <button type="button" wire:click="removeLogo" wire:confirm="Remove the hotel logo?" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                Remove
                            </button>
                        @endif
                    </div>

                    {{-- Upload progress --}}
                    <div wire:loading wire:target="hotel_logo" class="mt-2">
                        <div class="flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Uploading...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════ Check-in / Check-out Policies ═══════════ --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @lang('hotel::modules.settings.checkInOutPolicies')
                </h3>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-label for="default_check_in_time" value="{{ __('hotel::modules.settings.defaultCheckInTime') }}" />
                    <x-input id="default_check_in_time" type="time" class="block w-full mt-1" wire:model="default_check_in_time" required />
                    <x-input-error for="default_check_in_time" class="mt-2" />
                </div>
                <div>
                    <x-label for="default_checkout_time" value="{{ __('hotel::modules.settings.defaultCheckoutTime') }}" />
                    <x-input id="default_checkout_time" type="time" class="block w-full mt-1" wire:model="default_checkout_time" required />
                    <x-input-error for="default_checkout_time" class="mt-2" />
                </div>
                <div>
                    <x-label for="early_checkin_charge_per_hour" value="{{ __('hotel::modules.settings.earlyCheckinCharge') }}" />
                    <x-input id="early_checkin_charge_per_hour" type="number" step="0.01" min="0" class="block w-full mt-1" wire:model="early_checkin_charge_per_hour" required />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('hotel::modules.settings.perHour')</p>
                    <x-input-error for="early_checkin_charge_per_hour" class="mt-2" />
                </div>
                <div>
                    <x-label for="late_checkout_charge_per_hour" value="{{ __('hotel::modules.settings.lateCheckoutCharge') }}" />
                    <x-input id="late_checkout_charge_per_hour" type="number" step="0.01" min="0" class="block w-full mt-1" wire:model="late_checkout_charge_per_hour" required />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('hotel::modules.settings.perHour')</p>
                    <x-input-error for="late_checkout_charge_per_hour" class="mt-2" />
                </div>
            </div>
        </div>

        {{-- ═══════════ Payment Policy ═══════════ --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    @lang('hotel::modules.settings.paymentPolicy')
                </h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <x-label for="payment_policy" value="{{ __('hotel::modules.settings.paymentPolicyLabel') }}" />
                    <select id="payment_policy" wire:model.live="payment_policy" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        <option value="pay_at_checkout">@lang('hotel::modules.settings.payAtCheckout')</option>
                        <option value="partial_deposit">@lang('hotel::modules.settings.partialDeposit')</option>
                        <option value="full_advance">@lang('hotel::modules.settings.fullAdvance')</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        @if($payment_policy === 'pay_at_checkout')
                            @lang('hotel::modules.settings.payAtCheckoutHint')
                        @elseif($payment_policy === 'partial_deposit')
                            @lang('hotel::modules.settings.partialDepositHint')
                        @else
                            @lang('hotel::modules.settings.fullAdvanceHint')
                        @endif
                    </p>
                    <x-input-error for="payment_policy" class="mt-2" />
                </div>

                @if($payment_policy === 'partial_deposit')
                <div>
                    <x-label for="deposit_percentage" value="{{ __('hotel::modules.settings.depositPercentage') }}" />
                    <div class="flex items-center gap-2 mt-1">
                        <x-input id="deposit_percentage" type="number" step="0.01" min="0" max="100" class="block w-full" wire:model="deposit_percentage" />
                        <span class="text-gray-500 dark:text-gray-400 font-medium">%</span>
                    </div>
                    <x-input-error for="deposit_percentage" class="mt-2" />
                </div>
                @endif

                <div>
                    <x-label for="cancellation_policy" value="{{ __('hotel::modules.settings.cancellationPolicy') }}" />
                    <textarea id="cancellation_policy" wire:model="cancellation_policy" rows="3" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" placeholder="e.g. Free cancellation up to 24 hours before check-in..."></textarea>
                    <x-input-error for="cancellation_policy" class="mt-2" />
                </div>
            </div>
        </div>

        {{-- ═══════════ Tax & Charges ═══════════ --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    @lang('hotel::modules.settings.taxAndCharges')
                </h3>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-label for="tax_rate" value="{{ __('hotel::modules.settings.taxRate') }}" />
                    <div class="flex items-center gap-2 mt-1">
                        <x-input id="tax_rate" type="number" step="0.01" min="0" max="100" class="block w-full" wire:model="tax_rate" required />
                        <span class="text-gray-500 dark:text-gray-400 font-medium">%</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('hotel::modules.settings.taxRateHint')</p>
                    <x-input-error for="tax_rate" class="mt-2" />
                </div>
                <div>
                    <x-label for="service_charge_rate" value="{{ __('hotel::modules.settings.serviceChargeRate') }}" />
                    <div class="flex items-center gap-2 mt-1">
                        <x-input id="service_charge_rate" type="number" step="0.01" min="0" max="100" class="block w-full" wire:model="service_charge_rate" required />
                        <span class="text-gray-500 dark:text-gray-400 font-medium">%</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('hotel::modules.settings.serviceChargeHint')</p>
                    <x-input-error for="service_charge_rate" class="mt-2" />
                </div>
            </div>
        </div>

        {{-- ═══════════ Feature Toggles ═══════════ --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    @lang('hotel::modules.settings.features')
                </h3>
            </div>
            <div class="p-6 divide-y divide-gray-100 dark:divide-gray-700">
                {{-- Room Service --}}
                <div class="flex items-center justify-between py-4 first:pt-0 last:pb-0">
                    <div class="flex flex-col flex-grow pr-4">
                        <div class="text-base font-semibold text-gray-900 dark:text-white">
                            @lang('hotel::modules.settings.enableRoomService')
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            @lang('hotel::modules.settings.enableRoomServiceHint')
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="enable_room_service" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                {{-- Housekeeping --}}
                <div class="flex items-center justify-between py-4">
                    <div class="flex flex-col flex-grow pr-4">
                        <div class="text-base font-semibold text-gray-900 dark:text-white">
                            @lang('hotel::modules.settings.enableHousekeeping')
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            @lang('hotel::modules.settings.enableHousekeepingHint')
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="enable_housekeeping_module" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                {{-- Dynamic Pricing --}}
                <div class="flex items-center justify-between py-4 first:pt-0 last:pb-0">
                    <div class="flex flex-col flex-grow pr-4">
                        <div class="text-base font-semibold text-gray-900 dark:text-white">
                            @lang('hotel::modules.settings.enableDynamicPricing')
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            @lang('hotel::modules.settings.enableDynamicPricingHint')
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="enable_dynamic_pricing" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        {{-- ═══════════ Save Button ═══════════ --}}
        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 transition disabled:opacity-50">
                <svg wire:loading wire:target="save" class="w-4 h-4 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <svg wire:loading.remove wire:target="save" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                @lang('hotel::modules.settings.saveSettings')
            </button>
        </div>

    </form>
</div>
