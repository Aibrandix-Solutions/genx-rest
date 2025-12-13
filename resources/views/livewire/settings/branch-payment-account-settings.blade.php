<div>
    <div class="p-6 bg-white border border-gray-200 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 space-y-4 md:space-y-0">
            <div>
                <h3 class="text-xl font-semibold dark:text-white">@lang('modules.settings.paymentAccountSettings')</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.paymentAccountSettingsDescription')</p>
            </div>
        </div>

        @if(empty($paymentAccounts))
        <div class="mb-4">
            <x-alert type="warning" class="flex items-center justify-between gap-4">
                <div class="flex-1">
                    <strong class="font-medium">@lang('modules.settings.noPaymentAccounts')</strong>
                    <p class="mt-1 text-sm">@lang('modules.settings.noPaymentAccountsDescription')</p>
                </div>
            </x-alert>
        </div>
        @endif

        <form wire:submit="saveSettings" class="space-y-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                @lang('modules.settings.paymentMethod')
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                @lang('modules.settings.defaultPaymentAccount')
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($paymentMethods as $method => $label)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $label }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <x-select 
                                    wire:model="settings.{{ $method }}" 
                                    class="block w-full max-w-md"
                                    :disabled="empty($paymentAccounts)">
                                    <option value="">@lang('modules.settings.none')</option>
                                    @foreach($paymentAccounts as $accountId => $accountName)
                                        <option value="{{ $accountId }}">{{ $accountName }}</option>
                                    @endforeach
                                </x-select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end space-x-4">
                <x-button type="submit">@lang('app.save')</x-button>
            </div>
        </form>
    </div>
</div>

