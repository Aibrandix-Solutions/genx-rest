@if ($showBranchFilter ?? false)
    <select wire:model.live="branchFilter"
        aria-label="{{ __('app.branch') }}"
        class="px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-4 focus:ring-primary-300 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
        <option value="current">{{ branch()->name ?? __('app.current') }}</option>
        <option value="all">@lang('app.all_branches')</option>
        @foreach ($reportBranches ?? [] as $reportBranch)
            @if ((int) $reportBranch->id !== (int) branch()?->id)
                <option value="{{ $reportBranch->id }}">{{ $reportBranch->name }}</option>
            @endif
        @endforeach
    </select>
@endif
