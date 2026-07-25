<div class="space-y-6 py-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('inventory::modules.reports.item_purchases.title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('inventory::modules.reports.item_purchases.description') }}
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
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 p-5 shadow-sm dark:from-blue-900/30 dark:to-blue-800/30">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                {{ __('inventory::modules.reports.item_purchases.summary.total_purchase_amount') }}
            </p>
            <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ currency_format($summary['total_purchase_amount'], restaurant()->currency_id) }}
            </h3>
        </div>

        <div class="rounded-lg bg-gradient-to-br from-emerald-50 to-emerald-100 p-5 shadow-sm dark:from-emerald-900/30 dark:to-emerald-800/30">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                {{ __('inventory::modules.reports.item_purchases.summary.total_purchased_quantity') }}
            </p>
            <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ $reportService->formatQuantity($summary['total_purchased_quantity']) }}
            </h3>
        </div>

        <div class="rounded-lg bg-gradient-to-br from-purple-50 to-purple-100 p-5 shadow-sm dark:from-purple-900/30 dark:to-purple-800/30">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                {{ __('inventory::modules.reports.item_purchases.summary.total_current_stock') }}
            </p>
            <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ $reportService->formatQuantity($summary['total_current_stock']) }}
            </h3>
        </div>
    </div>

    <!-- Filters -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center gap-2 text-blue-600 dark:text-blue-400">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
            </svg>
            <span class="text-lg font-semibold">{{ __('inventory::modules.reports.item_purchases.filters.title') }}</span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('inventory::modules.reports.item_purchases.filters.start_date') }}
                </label>
                <input type="date" wire:model.live="startDate"
                       class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('inventory::modules.reports.item_purchases.filters.end_date') }}
                </label>
                <input type="date" wire:model.live="endDate"
                       class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('inventory::modules.reports.item_purchases.filters.branch') }}
                </label>
                <select wire:model.live="branchFilter"
                        class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">{{ __('app.all') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('inventory::modules.reports.item_purchases.filters.location') }}
                </label>
                <select wire:model.live="locationFilter"
                        class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">{{ __('app.all') }}</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->display_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('inventory::modules.reports.item_purchases.filters.category') }}
                </label>
                <select wire:model.live="categoryFilter"
                        class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">{{ __('app.all') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="button" wire:click="clearFilters"
                        class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('inventory::modules.reports.item_purchases.filters.clear') }}
                </button>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            @php
                $itemOptions = $inventoryItems->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                ])->values();
                $codeOptions = $codedItems->map(fn ($item) => [
                    'id' => $item->id,
                    'item_code' => $item->item_code,
                ])->values();
            @endphp

            <!-- Item multi-select -->
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
                    {{ __('inventory::modules.reports.item_purchases.filters.items') }}
                </label>
                <button type="button" @click="open = !open"
                        class="flex min-h-[42px] w-full items-center justify-between rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-left text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <span class="truncate text-gray-500 dark:text-gray-400" x-show="selectedLabels().length === 0">
                        {{ __('inventory::modules.reports.item_purchases.filters.all_items') }}
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

            <!-- Item code multi-select -->
            <div x-data="{
                    open: false,
                    search: '',
                    items: @js($codeOptions),
                    selectedCodes: @entangle('selectedItemCodes').live,
                    get filtered() {
                        const term = this.search.trim().toLowerCase();
                        if (!term) return this.items;
                        return this.items.filter(i => String(i.item_code ?? '').toLowerCase().includes(term));
                    },
                    isSelected(code) {
                        return this.selectedCodes.includes(code);
                    },
                    toggle(code) {
                        $wire.toggleSelectedItemCode(code);
                    },
                    selectedLabels() {
                        return this.selectedCodes;
                    }
                }" @click.away="open = false" class="relative">
                <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('inventory::modules.reports.item_purchases.filters.item_codes') }}
                </label>
                <button type="button" @click="open = !open"
                        class="flex min-h-[42px] w-full items-center justify-between rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-left text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <span class="truncate font-mono text-gray-500 dark:text-gray-400" x-show="selectedLabels().length === 0">
                        {{ __('inventory::modules.reports.item_purchases.filters.all_item_codes') }}
                    </span>
                    <span class="truncate font-mono" x-show="selectedLabels().length > 0" x-text="selectedLabels().join(', ')"></span>
                    <svg class="ml-2 h-4 w-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-cloak class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700">
                    <div class="border-b border-gray-100 p-2 dark:border-gray-600">
                        <input x-model="search" type="text" placeholder="{{ __('inventory::modules.stock.searchByCode') }}"
                               class="w-full rounded border border-gray-300 px-3 py-1.5 font-mono text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                               @click.stop>
                    </div>
                    <ul class="max-h-48 overflow-y-auto py-1">
                        <template x-for="item in filtered" :key="item.id">
                            <li @click.stop="toggle(item.item_code)"
                                class="flex cursor-pointer items-center gap-2 px-4 py-2 text-sm hover:bg-blue-50 dark:hover:bg-gray-600">
                                <input type="checkbox" class="rounded border-gray-300" :checked="isSelected(item.item_code)" @click.stop="toggle(item.item_code)">
                                <span x-text="item.item_code" class="font-mono text-gray-900 dark:text-gray-100"></span>
                            </li>
                        </template>
                        <li x-show="filtered.length === 0" class="px-4 py-2 text-sm text-gray-400">{{ __('app.noResultFound') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span>{{ __('inventory::modules.reports.item_purchases.filters.active_branch') }}: <strong>{{ $branchLabel }}</strong></span>
            <span>{{ __('inventory::modules.reports.item_purchases.filters.active_location') }}: <strong>{{ $locationLabel }}</strong></span>
            <span>{{ __('inventory::modules.reports.item_purchases.filters.active_category') }}: <strong>{{ $categoryLabel }}</strong></span>
            <span>{{ __('inventory::modules.reports.item_purchases.filters.date_hint') }}</span>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ __('inventory::modules.reports.item_purchases.table.title') }}
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('inventory::modules.reports.item_purchases.table.item') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('inventory::modules.reports.item_purchases.table.item_code') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('inventory::modules.reports.item_purchases.table.unit') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('inventory::modules.reports.item_purchases.table.location') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('inventory::modules.reports.item_purchases.table.branch') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('inventory::modules.reports.item_purchases.table.category') }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('inventory::modules.reports.item_purchases.table.purchased_quantity') }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('inventory::modules.reports.item_purchases.table.total_purchase_price') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $row->item_name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-mono text-gray-600 dark:text-gray-300">
                                {{ $row->item_code ?: '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $row->unit }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $row->location }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $row->branch }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $row->category }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100">
                                {{ $reportService->formatQuantity($row->purchased_quantity) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100">
                                {{ currency_format($row->total_purchase_price, restaurant()->currency_id) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.item_purchases.table.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
