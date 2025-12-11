<div>
    <div class="p-4 mx-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
        <h3 class="mb-4 text-xl font-semibold dark:text-white">@lang('modules.settings.rewardPointsSettings')</h3>
        <x-help-text class="mb-6">@lang('modules.settings.rewardPointsHelp')</x-help-text>

        <form wire:submit.prevent="submitForm" class="space-y-6">
            <!-- General Settings -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h4 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">@lang('modules.reward.generalSettings')</h4>
                
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="flex items-center">
                        <x-checkbox id="enable_reward_point" wire:model="enable_reward_point" />
                        <x-label for="enable_reward_point" :value="__('modules.reward.enableRewardPoint')" class="ml-2" />
                    </div>

                    <div>
                        <x-label for="reward_point_display_name" :value="__('modules.reward.rewardPointDisplayName')" />
                        <x-input id="reward_point_display_name" 
                            type="text" 
                            class="block mt-1 w-full" 
                            wire:model="reward_point_display_name" />
                        <x-help-text>@lang('modules.reward.rewardPointDisplayNameHelp')</x-help-text>
                    </div>
                </div>
            </div>

            <!-- Earning Points Settings -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h4 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">@lang('modules.reward.earningPointsSettings')</h4>
                
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <x-label for="amount_spend_for_unit_point" :value="__('modules.reward.amountSpendForUnitPoint')" />
                        <x-input id="amount_spend_for_unit_point" 
                            type="number" 
                            step="0.01" 
                            min="0.01"
                            class="block mt-1 w-full" 
                            wire:model="amount_spend_for_unit_point" />
                        <x-help-text>@lang('modules.reward.amountSpendForUnitPointHelp')</x-help-text>
                    </div>

                    <div>
                        <x-label for="minimum_order_total_to_earn" :value="__('modules.reward.minimumOrderTotalToEarn')" />
                        <x-input id="minimum_order_total_to_earn" 
                            type="number" 
                            step="0.01" 
                            min="0"
                            class="block mt-1 w-full" 
                            wire:model="minimum_order_total_to_earn" />
                        <x-help-text>@lang('modules.reward.minimumOrderTotalToEarnHelp')</x-help-text>
                    </div>

                    <div>
                        <x-label for="maximum_points_per_order" :value="__('modules.reward.maximumPointsPerOrder')" />
                        <x-input id="maximum_points_per_order" 
                            type="number" 
                            min="1"
                            class="block mt-1 w-full" 
                            wire:model="maximum_points_per_order" 
                            placeholder="{{ __('modules.reward.unlimited') }}" />
                        <x-help-text>@lang('modules.reward.maximumPointsPerOrderHelp')</x-help-text>
                    </div>
                </div>
            </div>

            <!-- Redeem Points Settings -->
            <div class="pb-6">
                <h4 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">@lang('modules.reward.redeemPointsSettings')</h4>
                
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <x-label for="redeem_amount_per_unit_point" :value="__('modules.reward.redeemAmountPerUnitPoint')" />
                        <x-input id="redeem_amount_per_unit_point" 
                            type="number" 
                            step="0.01" 
                            min="0.01"
                            class="block mt-1 w-full" 
                            wire:model="redeem_amount_per_unit_point" />
                        <x-help-text>@lang('modules.reward.redeemAmountPerUnitPointHelp')</x-help-text>
                    </div>

                    <div>
                        <x-label for="minimum_order_total_to_redeem" :value="__('modules.reward.minimumOrderTotalToRedeem')" />
                        <x-input id="minimum_order_total_to_redeem" 
                            type="number" 
                            step="0.01" 
                            min="0"
                            class="block mt-1 w-full" 
                            wire:model="minimum_order_total_to_redeem" />
                        <x-help-text>@lang('modules.reward.minimumOrderTotalToRedeemHelp')</x-help-text>
                    </div>

                    <div>
                        <x-label for="minimum_redeem_point" :value="__('modules.reward.minimumRedeemPoint')" />
                        <x-input id="minimum_redeem_point" 
                            type="number" 
                            min="1"
                            class="block mt-1 w-full" 
                            wire:model="minimum_redeem_point" 
                            placeholder="{{ __('modules.reward.unlimited') }}" />
                        <x-help-text>@lang('modules.reward.minimumRedeemPointHelp')</x-help-text>
                    </div>

                    <div>
                        <x-label for="maximum_redeem_point_per_order" :value="__('modules.reward.maximumRedeemPointPerOrder')" />
                        <x-input id="maximum_redeem_point_per_order" 
                            type="number" 
                            min="1"
                            class="block mt-1 w-full" 
                            wire:model="maximum_redeem_point_per_order" 
                            placeholder="{{ __('modules.reward.unlimited') }}" />
                        <x-help-text>@lang('modules.reward.maximumRedeemPointPerOrderHelp')</x-help-text>
                    </div>

                    <div>
                        <x-label for="reward_point_expiry_period" :value="__('modules.reward.rewardPointExpiryPeriod')" />
                        <div class="flex gap-2 mt-1">
                            <x-input id="reward_point_expiry_period" 
                                type="number" 
                                min="0"
                                class="block w-full" 
                                wire:model="reward_point_expiry_period" />
                            <x-select id="reward_point_expiry_period_unit" 
                                class="block w-32" 
                                wire:model="reward_point_expiry_period_unit">
                                <option value="month">@lang('modules.reward.month')</option>
                                <option value="year">@lang('modules.reward.year')</option>
                            </x-select>
                        </div>
                        <x-help-text>@lang('modules.reward.rewardPointExpiryPeriodHelp')</x-help-text>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <x-button type="submit">@lang('app.save')</x-button>
            </div>
        </form>
    </div>
</div>

