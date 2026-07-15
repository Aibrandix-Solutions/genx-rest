<div class="space-y-6 py-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('inventory::modules.reports.transfers.title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('inventory::modules.reports.transfers.description') }}
            </p>
        </div>

        <x-secondary-button wire:click="export" wire:loading.attr="disabled" wire:target="export" class="shrink-0">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            {{ __('app.export') }}
        </x-secondary-button>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 p-5 shadow-sm dark:from-blue-900/30 dark:to-blue-800/30">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                {{ __('inventory::modules.reports.transfers.summary.total_transfers') }}
            </p>
            <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ $summary['total_transfers'] }}
            </h3>
        </div>
        <div class="rounded-lg bg-gradient-to-br from-indigo-50 to-indigo-100 p-5 shadow-sm dark:from-indigo-900/30 dark:to-indigo-800/30">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                {{ __('inventory::modules.reports.transfers.summary.total_lines') }}
            </p>
            <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ $summary['total_lines'] }}
            </h3>
        </div>
        <div class="rounded-lg bg-gradient-to-br from-emerald-50 to-emerald-100 p-5 shadow-sm dark:from-emerald-900/30 dark:to-emerald-800/30">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                {{ __('inventory::modules.reports.transfers.summary.total_quantity') }}
            </p>
            <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ $reportService->formatQuantity($summary['total_quantity']) }}
            </h3>
        </div>
        <div class="rounded-lg bg-gradient-to-br from-amber-50 to-amber-100 p-5 shadow-sm dark:from-amber-900/30 dark:to-amber-800/30">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                {{ __('inventory::modules.reports.transfers.summary.total_cost') }}
            </p>
            <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ currency_format($summary['total_cost'], restaurant()->currency_id) }}
            </h3>
        </div>
    </div>

    <!-- Filters -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center gap-2 text-blue-600 dark:text-blue-400">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
            </svg>
            <span class="text-lg font-semibold">{{ __('inventory::modules.reports.transfers.filters.title') }}</span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('inventory::modules.reports.transfers.filters.report_type') }}
                </label>
                <select wire:model.live="reportType"
                        class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="item">{{ __('inventory::modules.reports.transfers.types.item') }}</option>
                    <option value="location">{{ __('inventory::modules.reports.transfers.types.location') }}</option>
                    <option value="daily">{{ __('inventory::modules.reports.transfers.types.daily') }}</option>
                    <option value="monthly">{{ __('inventory::modules.reports.transfers.types.monthly') }}</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('inventory::modules.reports.transfers.filters.start_date') }}
                </label>
                <input type="date" wire:model.live="startDate"
                       class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('inventory::modules.reports.transfers.filters.end_date') }}
                </label>
                <input type="date" wire:model.live="endDate"
                       class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('inventory::modules.reports.transfers.filters.branch') }}
                </label>
                <select wire:model.live="branchFilter"
                        class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">{{ __('app.all') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            @if ($reportType === 'location')
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.transfers.filters.from_location') }}
                    </label>
                    <select wire:model.live="fromLocationFilter"
                            class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="all">{{ __('app.all') }}</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->display_name ?? $location->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.transfers.filters.to_location') }}
                    </label>
                    <select wire:model.live="toLocationFilter"
                            class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="all">{{ __('app.all') }}</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->display_name ?? $location->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (in_array($reportType, ['item', 'location', 'daily'], true))
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.transfers.filters.search') }}
                    </label>
                    <input type="search" wire:model.live.debounce.300ms="search"
                           placeholder="{{ __('inventory::modules.reports.transfers.filters.search_placeholder') }}"
                           class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            @endif

            <div class="flex items-end">
                <button type="button" wire:click="clearFilters"
                        class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('inventory::modules.reports.transfers.filters.clear') }}
                </button>
            </div>
        </div>

        @if (in_array($reportType, ['item', 'location'], true))
            @php
                $itemOptions = $inventoryItems->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                ])->values();
            @endphp

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div x-data="{
                        open: false,
                        search: '',
                        items: @js($itemOptions),
                        selectedIds: @entangle('selectedItemIds').live,
                        get filtered() {
                            const term = this.search.trim().toLowerCase();
                            if (!term) return this.items;
                            return this.items.filter(i => String(i.name ?? '').toLowerCase().includes(term));
                        },
                        isSelected(id) {
                            return this.selectedIds.map(Number).includes(Number(id));
                        },
                        toggle(id) {
                            $wire.toggleSelectedItem(Number(id));
                        },
                        selectedLabels() {
                            return this.items
                                .filter(i => this.isSelected(i.id))
                                .map(i => i.name);
                        }
                    }" @click.away="open = false" class="relative">
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.transfers.filters.items') }}
                    </label>
                    <button type="button" @click="open = !open"
                            class="flex min-h-[42px] w-full items-center justify-between rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-left text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <span class="truncate text-gray-500 dark:text-gray-400" x-show="selectedLabels().length === 0">
                            {{ __('inventory::modules.reports.transfers.filters.all_items') }}
                        </span>
                        <span class="truncate" x-show="selectedLabels().length > 0" x-text="selectedLabels().join(', ')"></span>
                        <svg class="ml-2 h-4 w-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700">
                        <div class="border-b border-gray-100 p-2 dark:border-gray-600">
                            <input x-model="search" type="text" placeholder="{{ __('app.search') }}..."
                                   class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                   @click.stop>
                        </div>
                        <ul class="max-h-48 overflow-y-auto py-1">
                            <template x-for="item in filtered" :key="item.id">
                                <li @click.stop="toggle(item.id)"
                                    class="flex cursor-pointer items-center gap-2 px-4 py-2 text-sm hover:bg-blue-50 dark:hover:bg-gray-600">
                                    <input type="checkbox" class="rounded border-gray-300" :checked="isSelected(item.id)" @click.stop="toggle(item.id)">
                                    <span x-text="item.name" class="text-gray-900 dark:text-gray-100"></span>
                                </li>
                            </template>
                            <li x-show="filtered.length === 0" class="px-4 py-2 text-sm text-gray-400">{{ __('app.noResultFound') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-4 flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span>{{ __('inventory::modules.reports.transfers.filters.active_branch') }}: <strong>{{ $branchLabel }}</strong></span>
            @if ($reportType === 'location')
                <span>{{ __('inventory::modules.reports.transfers.filters.from_location') }}: <strong>{{ $fromLocationLabel }}</strong></span>
                <span>{{ __('inventory::modules.reports.transfers.filters.to_location') }}: <strong>{{ $toLocationLabel }}</strong></span>
            @endif
            <span>{{ __('inventory::modules.reports.transfers.filters.completed_hint') }}</span>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                @if ($reportType === 'item')
                    {{ __('inventory::modules.reports.transfers.table.item_title') }}
                @elseif ($reportType === 'location')
                    {{ __('inventory::modules.reports.transfers.table.location_title') }}
                @elseif ($reportType === 'daily')
                    {{ __('inventory::modules.reports.transfers.table.daily_title') }}
                @else
                    {{ __('inventory::modules.reports.transfers.table.monthly_title') }}
                @endif
            </h3>
        </div>

        <div class="overflow-x-auto">
            @if (in_array($reportType, ['item', 'location'], true))
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <span class="sr-only">{{ __('inventory::modules.reports.transfers.table.select') }}</span>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.product_code') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.product_name') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.category') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.from_location') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.to_location') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.unit') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.quantity') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.unit_cost') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.total_cost') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.transfer_number') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.date') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @forelse ($rows as $index => $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <input type="checkbox"
                                           class="rounded border-gray-300 text-skin-base focus:ring-skin-base"
                                           wire:click.prevent="toggleSelectedItem({{ (int) $row->item_id }})"
                                           @checked(in_array((int) $row->item_id, $selectedItemIds, true))>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $index + 1 }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-gray-600 dark:text-gray-300">{{ $row->product_code }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row->product_name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->category }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->from_location }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->to_location }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->unit }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100">{{ $reportService->formatQuantity($row->quantity) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100">{{ currency_format($row->unit_cost, restaurant()->currency_id) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100">{{ currency_format($row->total_cost, restaurant()->currency_id) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row->transfer_number }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->date_label }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    @if ($row->transfer_id)
                                        <button type="button" wire:click="viewTransfer({{ (int) $row->transfer_id }})"
                                                class="inline-flex items-center text-skin-base hover:opacity-80"
                                                title="{{ __('inventory::modules.reports.transfers.table.view') }}">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('inventory::modules.reports.transfers.table.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif ($reportType === 'daily')
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.date') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.transfer_number') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.from_location') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.to_location') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.items_count') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.quantity') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.total_cost') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @forelse ($rows as $index => $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $index + 1 }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $row->date_label }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row->transfer_number }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $row->from_location }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $row->to_location }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100">{{ $row->items_count }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100">{{ $reportService->formatQuantity($row->total_quantity) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100">{{ currency_format($row->total_cost, restaurant()->currency_id) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <button type="button" wire:click="viewTransfer({{ (int) $row->transfer_id }})"
                                            class="inline-flex items-center text-skin-base hover:opacity-80"
                                            title="{{ __('inventory::modules.reports.transfers.table.view') }}">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('inventory::modules.reports.transfers.table.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.month') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.transfers_count') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.lines_count') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.quantity') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.transfers.table.total_cost') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @forelse ($rows as $index => $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $index + 1 }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row->month_label }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100">{{ $row->transfers_count }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100">{{ $row->lines_count }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100">{{ $reportService->formatQuantity($row->total_quantity) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100">{{ currency_format($row->total_cost, restaurant()->currency_id) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('inventory::modules.reports.transfers.table.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <x-right-modal wire:model.live="showViewModal">
        <x-slot name="title">
            {{ __('inventory::modules.transfers.view_transfer') }}
        </x-slot>
        <x-slot name="content">
            @if ($selectedTransfer)
                @include('inventory::livewire.stock-transfer.partials.view-transfer-details')
            @endif
        </x-slot>
    </x-right-modal>
</div>
