<div>
    <div class="flex flex-col">
        <!-- Item Header -->
        <div class="flex gap-4 mb-4">
            @if (restaurant() && !restaurant()->hide_menu_item_image_on_customer_site)
                <img class="w-16 h-16 rounded-md object-cover" src="{{ $selectedModifierItem->item_photo_url }}" alt="{{ $selectedModifierItem->item_name }}">
            @endif
            <div class="text-sm font-normal text-gray-500 dark:text-gray-400 space-y-1">
                <div class="text-base font-semibold text-gray-900 dark:text-white inline-flex items-center">
                    <img src="{{ asset('img/'.$selectedModifierItem->type.'.svg') }}" class="h-4 mr-2"
                        title="@lang('modules.menu.' . $selectedModifierItem->type)" alt="" />
                    {{ $selectedModifierItem->item_name }}
                    @if ($selectedVariationName) <span class="text-sm font-normal text-gray-500 dark:text-gray-400 ms-1">({{ $selectedVariationName }})</span> @endif
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedModifierItem->description }}</div>
                <div class="text-sm text-gray-800 dark:text-gray-400 mt-1 sm:mt-0 font-medium">
                    {{ $selectedModifierItem->price ? currency_format($selectedModifierItem->price, $selectedModifierItem->branch->restaurant->currency_id) : __('--') }}
                </div>
            </div>
        </div>

        <!-- Modifiers List -->
        @foreach ($modifiers as $modifier)
        <div x-data="{ open: true }" class="flex flex-col items-start mb-4 border p-2 rounded-md bg-white dark:bg-gray-800 dark:border-gray-700">

            <!-- Modifier Header (Clickable) -->
            <div class="flex justify-between items-center w-full cursor-pointer select-none p-3 rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"
                 @click="open = !open">
                <div class="flex flex-col justify-between w-full">
                    <div class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $modifier->name }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1 sm:mt-0">
                        {{ $modifier->description }}
                    </div>
                </div>

            <!-- Expand/Collapse Icon -->
            <svg x-bind:class="{ 'rotate-180': open }" class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-300 transform"
                fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>

            <!-- Options Table (Collapsible) -->
            <div x-show="open" x-collapse class="overflow-x-auto w-full transition-all duration-300 ease-in-out">
                <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600 mt-2">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                @lang('modules.modifier.optionName')
                            </th>
                            <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                @lang('modules.menu.setPrice')
                            </th>
                            <th class="py-2.5 px-4 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">
                                @lang('modules.order.qty')
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                        @foreach ($modifier->options as $option)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">
                                    {{ $option->name }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">
                                    {{ $option->contextual_price ? currency_format($option->contextual_price, $selectedModifierItem->branch->restaurant->currency_id) : __('--') }}
                                </td>
                                <td class="py-2.5 px-4 text-right">
                                    @if ($option->is_available)
                                        @php $qty = (int) ($selectedModifiers[$option->id] ?? 0); @endphp
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <button type="button"
                                                wire:click="decrementOption({{ $modifier->id }}, {{ $option->id }})"
                                                class="h-8 w-8 inline-flex items-center justify-center border border-gray-300 bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 rounded-md"
                                                @disabled($qty <= 0)
                                            >
                                                <svg class="w-3 h-3 text-gray-900 dark:text-white" aria-hidden="true" fill="none" viewBox="0 0 18 2">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16"/>
                                                </svg>
                                            </button>

                                            <span class="min-w-8 text-center text-sm text-gray-900 dark:text-white">{{ $qty }}</span>

                                            <button type="button"
                                                wire:click="incrementOption({{ $modifier->id }}, {{ $option->id }})"
                                                class="h-8 w-8 inline-flex items-center justify-center border border-gray-300 bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 rounded-md"
                                            >
                                                <svg class="w-3 h-3 text-gray-900 dark:text-white" aria-hidden="true" fill="none" viewBox="0 0 18 18">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @else
                                    <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        @lang('modules.menu.notAvailable')
                                    </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <x-input-error for="requiredModifiers.{{ $modifier->id }}" class="mt-2" />
            </div>

        </div>
        @endforeach
    </div>

    <!-- Save Button -->
    <div class="mt-4 text-right">
        <x-button
            wire:click="saveModifiers"
            class="bg-blue-500 hover:bg-blue-600 text-white">
            @lang('app.save')
        </x-button>
        <x-secondary-button wire:click="$dispatch('closeModifiersModal')" class="ml-3">
            @lang('app.cancel')
        </x-secondary-button>
    </div>
</div>
