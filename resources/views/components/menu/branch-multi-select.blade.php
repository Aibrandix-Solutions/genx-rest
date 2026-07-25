@props([
    'branches' => [],
    'selectedIds' => [],
    'lockedIds' => [],
    'wireModel' => 'selectedBranchIds',
    'help' => null,
    'label' => null,
    'toggleMethod' => 'toggleBranchSelection',
])

@php
    $helpText = $help ?? __('modules.menu.selectBranchesHelp');
    $labelText = $label ?? __('modules.menu.availableBranches');
    $selected = array_map('intval', (array) $selectedIds);
    $locked = array_map('intval', (array) $lockedIds);
@endphp

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    <x-label :value="$labelText" />
    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $helpText }}</p>

    @if (count($branches) <= 1)
        <p class="text-sm text-gray-600 dark:text-gray-300">
            {{ $branches[0]['name'] ?? branch()?->name }}
        </p>
    @else
        <div class="mt-1 space-y-2 max-h-40 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-2 bg-white dark:bg-gray-800">
            @foreach ($branches as $branch)
                @php
                    $branchId = (int) $branch['id'];
                @endphp
                <label
                    wire:key="menu-branch-select-{{ $branchId }}"
                    class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 px-2 py-1 rounded"
                >
                    <input
                        type="checkbox"
                        @if (! in_array($branchId, $locked, true))
                            wire:click="{{ $toggleMethod }}({{ $branchId }})"
                        @endif
                        @checked(in_array($branchId, $selected, true))
                        @disabled(in_array($branchId, $locked, true))
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 disabled:opacity-70"
                    >
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $branch['name'] }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error for="{{ $wireModel }}" class="mt-2" />
    @endif
</div>
