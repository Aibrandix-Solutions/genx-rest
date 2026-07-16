@props([
    'branchesWithKitchenOptions' => [],
    'wireModelPrefix' => 'selectedKitchensByBranch',
    'toggleMethod' => 'toggleKitchenForBranch',
])

@if (in_array('Kitchen', restaurant_modules()))
    <div {{ $attributes->merge(['class' => 'space-y-4']) }} wire:key="branch-kitchen-select-root">
        <x-label :value="__('modules.menu.kitchenType')" />
        <p class="text-xs text-gray-500 dark:text-gray-400">@lang('modules.menu.kitchenAssignmentHelp')</p>

        @forelse ($branchesWithKitchenOptions as $branchId => $branchData)
            <div
                wire:key="branch-kitchen-group-{{ $branchId }}"
                class="border border-gray-200 dark:border-gray-600 rounded-md p-3"
            >
                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-2">{{ $branchData['name'] }}</p>

                @if (empty($branchData['kitchens']))
                    <p class="text-xs text-amber-600 dark:text-amber-400">@lang('modules.menu.noKitchensInBranch')</p>
                @else
                    <div class="space-y-2">
                        @foreach ($branchData['kitchens'] as $kitchen)
                            @php
                                $kitchenId = (int) ($kitchen['id'] ?? 0);
                            @endphp
                            <label
                                wire:key="branch-kitchen-{{ $branchId }}-{{ $kitchenId }}"
                                class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 px-2 py-1 rounded"
                            >
                                <input
                                    type="checkbox"
                                    wire:click="{{ $toggleMethod }}({{ (int) $branchId }}, {{ $kitchenId }})"
                                    @checked(!empty($kitchen['selected']))
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                                >
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $kitchen['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <x-input-error for="{{ $wireModelPrefix }}.{{ $branchId }}" class="mt-2" />
            </div>
        @empty
            <p class="text-xs text-gray-500 dark:text-gray-400">@lang('modules.menu.selectBranchesHelp')</p>
        @endforelse

        <x-input-error for="selectedKitchensByBranch" class="mt-2" />
    </div>
@endif
