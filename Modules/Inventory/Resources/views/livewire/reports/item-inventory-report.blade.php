<div class="space-y-6 py-4">

    <x-inventory::reports.tabs />

    <!-- Header -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('inventory::modules.reports.item_inventory.title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('inventory::modules.reports.item_inventory.description') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-2">
                <label for="activityType" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('inventory::modules.reports.item_inventory.filters.activity_type') }}:
                </label>
                <select id="activityType"
                        wire:model.live="activityType"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    @foreach ($activityTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('inventory.reports.item-inventory.pdf', $this->printQuery()) }}"
               class="inline-flex items-center gap-2 rounded-md border border-purple-500 px-4 py-2 text-sm font-medium text-purple-600 hover:bg-purple-50 dark:border-purple-400 dark:text-purple-400 dark:hover:bg-purple-900/20">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ __('inventory::modules.reports.item_inventory.download_full') }}
            </a>
        </div>
    </div>

    @if ($showFilters)
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex items-center gap-2 text-blue-600 dark:text-blue-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                </svg>
                <span class="text-lg font-semibold">{{ __('inventory::modules.reports.item_inventory.filters.title') }}</span>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.item_inventory.filters.branch') }}:
                    </label>
                    <select wire:model="branchFilter"
                            class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="all">{{ __('app.all') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('inventory::modules.reports.item_inventory.filters.branch_hint') }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.item_inventory.filters.start_date') }}:
                    </label>
                    <input type="date"
                           wire:model="startDate"
                           class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('inventory::modules.reports.item_inventory.filters.date_hint') }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.item_inventory.filters.end_date') }}:
                    </label>
                    <input type="date"
                           wire:model="endDate"
                           class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.item_inventory.filters.supplier') }}:
                    </label>
                    <select wire:model="supplierId"
                            class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('app.all') }}</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('inventory::modules.reports.item_inventory.filters.purchases_only') }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.item_inventory.filters.purchase_status') }}:
                    </label>
                    <select wire:model="purchaseStatus"
                            class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('app.all') }}</option>
                        @foreach ($purchaseStatuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('inventory::modules.reports.item_inventory.filters.purchases_only') }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.item_inventory.filters.payment_status') }}:
                    </label>
                    <select wire:model="paymentStatus"
                            class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('app.all') }}</option>
                        @foreach ($paymentStatuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('inventory::modules.reports.item_inventory.filters.purchases_only') }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.item_inventory.filters.search_item') }}:
                    </label>
                    <input type="text"
                           wire:model="search"
                           wire:keydown.enter="applySearch"
                           placeholder="{{ __('inventory::modules.reports.item_inventory.filters.search_item_placeholder') }}"
                           class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('inventory::modules.reports.item_inventory.filters.item_sections_hint') }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.item_inventory.filters.inventory_item') }}:
                    </label>
                    <select wire:model="itemFilter"
                            class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('inventory::modules.reports.filters.all_items') }}</option>
                        @foreach ($inventoryItems as $inventoryItem)
                            <option value="{{ $inventoryItem->id }}">
                                {{ $inventoryItem->name }}@if($inventoryItem->item_code) ({{ $inventoryItem->item_code }})@endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('inventory::modules.reports.item_inventory.filters.item_sections_hint') }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.item_inventory.filters.location') }}:
                    </label>
                    <select wire:model="locationId"
                            class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('app.all') }}</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->display_name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('inventory::modules.reports.item_inventory.filters.location_hint') }}
                    </p>
                </div>
            </div>

            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ __('inventory::modules.reports.item_inventory.filters.purchases_only_panel_note') }}
            </p>

            <div class="mt-4 flex justify-end gap-2">
                <button type="button"
                        wire:click="applySearch"
                        wire:loading.attr="disabled"
                        wire:target="applySearch"
                        class="inline-flex items-center gap-2 rounded-md border border-blue-600 bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60 dark:border-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600">
                    <svg wire:loading wire:target="applySearch" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('inventory::modules.reports.item_inventory.filters.search_button') }}
                </button>
                <button type="button"
                        wire:click="clearFilters"
                        wire:loading.attr="disabled"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('inventory::modules.reports.common.reset') }}
                </button>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('inventory::modules.reports.item_inventory.total_rows') }}: {{ $summaryRows->count() }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('inventory::modules.reports.item_inventory.summary.inventory_item') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('inventory::modules.reports.item_inventory.summary.purchases_column') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('inventory::modules.reports.item_inventory.summary.usage_column') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('inventory::modules.reports.item_inventory.summary.movements_column') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('inventory::modules.reports.item_inventory.summary.wastages_column') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('inventory::modules.reports.item_inventory.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    @forelse ($summaryRows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $row->item_name }}@if($row->item_code) <span class="text-gray-500 dark:text-gray-400">({{ $row->item_code }})</span>@endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $row->purchases }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $row->usage }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $row->movements }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $row->wastages }}</td>
                            <td class="px-6 py-4 text-right">
                                <button type="button"
                                        wire:click="viewItemDetails({{ $row->item_id }})"
                                        class="rounded-md border border-blue-500 px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-900/20">
                                    {{ __('inventory::modules.reports.item_inventory.view') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('inventory::modules.reports.common.no_data') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-dialog-modal wire:model="showItemDetails" maxWidth="4xl">
        <x-slot name="title">
            <div class="flex w-full items-center justify-between gap-4 pe-8">
                <span>{{ __('inventory::modules.reports.item_inventory.details_title') }}: {{ $detailItemLabel }}</span>
                @if ($ledgerCurrentBalance !== null)
                    <span class="shrink-0 text-sm font-medium text-gray-600 dark:text-gray-300">
                        {{ __('inventory::modules.reports.item_inventory.ledger.current_balance') }}:
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ $ledgerCurrentBalance }}@if($detailItemUnit) {{ $detailItemUnit }}@endif
                        </span>
                    </span>
                @endif
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <label for="ledgerBranchFilter" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.item_inventory.ledger.filter_branch') }}
                    </label>
                    <select id="ledgerBranchFilter"
                            wire:model.live="ledgerBranchFilter"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="all">{{ __('app.all') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="ledgerStartDate" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.item_inventory.ledger.filter_start_date') }}
                    </label>
                    <input type="date"
                           id="ledgerStartDate"
                           wire:model.live="ledgerStartDate"
                           class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label for="ledgerEndDate" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.item_inventory.ledger.filter_end_date') }}
                    </label>
                    <input type="date"
                           id="ledgerEndDate"
                           wire:model.live="ledgerEndDate"
                           class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                </div>
            </div>

            @if ($ledgerRows->isNotEmpty())
                @php
                    $formatLedgerQty = fn (float $qty) => app(\Modules\Inventory\Services\ItemInventoryReportService::class)->formatQuantity($qty);
                @endphp
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-3 py-2 text-left">{{ __('inventory::modules.reports.item_inventory.ledger.date') }}</th>
                                <th class="px-3 py-2 text-left">{{ __('inventory::modules.reports.item_inventory.ledger.ref_no') }}</th>
                                <th class="px-3 py-2 text-left">{{ __('inventory::modules.reports.item_inventory.ledger.transaction_type') }}</th>
                                <th class="px-3 py-2 text-left">{{ __('inventory::modules.reports.item_inventory.ledger.location') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('inventory::modules.reports.item_inventory.ledger.qty_in') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('inventory::modules.reports.item_inventory.ledger.qty_out') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('inventory::modules.reports.item_inventory.ledger.running_balance') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('inventory::modules.reports.item_inventory.ledger.unit_cost') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('inventory::modules.reports.item_inventory.ledger.value') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($ledgerRows as $row)
                                <tr>
                                    <td class="px-3 py-2">{{ optional($row->date)->format('Y-m-d') ?? '--' }}</td>
                                    <td class="px-3 py-2">{{ $row->ref_no }}</td>
                                    <td class="px-3 py-2">{{ $row->transaction_type }}</td>
                                    <td class="px-3 py-2">{{ $row->location }}</td>
                                    <td class="px-3 py-2 text-right">{{ $row->qty_in > 0 ? $formatLedgerQty((float) $row->qty_in) : '-' }}</td>
                                    <td class="px-3 py-2 text-right">{{ $row->qty_out > 0 ? $formatLedgerQty((float) $row->qty_out) : '-' }}</td>
                                    <td class="px-3 py-2 text-right">{{ $formatLedgerQty((float) $row->running_balance) }}</td>
                                    <td class="px-3 py-2 text-right">
                                        {{ $row->unit_cost !== null ? currency_format((float) $row->unit_cost, restaurant()->currency_id) : '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        {{ $row->value !== null ? currency_format((float) $row->value, restaurant()->currency_id) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('inventory::modules.reports.common.no_data') }}
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <button type="button"
                    wire:click="closeItemDetails"
                    class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                {{ __('inventory::modules.reports.item_inventory.close_details') }}
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
