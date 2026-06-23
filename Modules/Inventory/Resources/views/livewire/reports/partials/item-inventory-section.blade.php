@php
    $formatCell = function ($row, string $key) {
        $value = data_get($row, $key);

        if ($value === null || $value === '') {
            return '--';
        }

        if (in_array($key, ['date'], true) && $value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d');
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d H:i');
        }

        if (in_array($key, ['quantity', 'received_quantity', 'quantity_out', 'quantity_in'], true)) {
            return number_format((float) $value, 2);
        }

        if (in_array($key, ['unit_price', 'subtotal'], true)) {
            return number_format((float) $value, 2);
        }

        if (in_array($key, ['purchase_status', 'payment_status'], true)) {
            $statusKey = str_replace('_', '.', $key);
            if ($key === 'purchase_status') {
                return trans('inventory::modules.purchaseOrder.status.' . $value, [], null, $value);
            }
            if ($key === 'payment_status') {
                return trans('inventory::modules.purchaseOrder.payment_status.' . $value, [], null, $value);
            }
        }

        return $value;
    };
@endphp

<section class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
    <div class="flex flex-col gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
        </div>

        <a href="{{ route('inventory.reports.item-inventory.pdf', $this->printQuery($sectionKey)) }}"
           class="inline-flex items-center gap-2 self-start rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            {{ __('inventory::modules.reports.item_inventory.download_section') }}
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/40">
                <tr>
                    @foreach ($columns as $columnKey => $columnLabel)
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ $columnLabel }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse ($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        @foreach ($columns as $columnKey => $columnLabel)
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                {{ $formatCell($row, $columnKey) }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('inventory::modules.reports.common.no_data') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-gray-200 px-6 py-3 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
        {{ __('inventory::modules.reports.item_inventory.total_rows') }}: {{ $rows->count() }}
    </div>
</section>
