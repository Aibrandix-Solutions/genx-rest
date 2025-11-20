<div class="space-y-6">
    <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
        <div class="grid gap-4 md:grid-cols-5">
            <div>
                <x-label :value="__('app.fromDate')" />
                <x-input type="date" class="mt-1 w-full" wire:model.live="fromDate" />
            </div>
            <div>
                <x-label :value="__('app.toDate')" />
                <x-input type="date" class="mt-1 w-full" wire:model.live="toDate" />
            </div>
            <div>
                <x-label :value="__('app.action')" />
                <x-select class="mt-1 w-full" wire:model.live="actionType">
                    <option value="all">@lang('app.all')</option>
                    <option value="quantity_updated">@lang('modules.order.qty') @lang('app.updated')</option>
                    <option value="deleted">@lang('app.deleted')</option>
                    <option value="deleted_from_order">@lang('modules.order.deletedFromOrder')</option>
                </x-select>
            </div>
            <div>
                <x-label :value="__('app.search')" />
                <x-input type="text" class="mt-1 w-full" placeholder="{{ __('app.search') }}..."
                    wire:model.live.debounce.500ms="search" />
            </div>
            <div>
                <x-label :value="__('app.perPage')" />
                <x-select class="mt-1 w-full" wire:model.live="perPage">
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </x-select>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                            @lang('app.date')
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                            @lang('modules.order.orderNumber')
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                            @lang('modules.menu.itemName')
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                            @lang('modules.order.qty')
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                            @lang('app.user')
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                            @lang('app.note')
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    @forelse ($adjustments as $adjustment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition duration-150 ease-in-out">
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                {{ optional($adjustment->created_at)->timezone(timezone())->format('d M Y, h:i A') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                <div class="font-semibold">
                                    @if($adjustment->order_id)
                                        <a href="{{ route('pos.kot', $adjustment->order_id) }}?show-order-detail=true" class="text-indigo-600 hover:text-indigo-900 hover:underline dark:text-indigo-400" wire:navigate>
                                            {{ $adjustment->formatted_order_number ?? ('#' . ($adjustment->order_number ?? '—')) }}
                                        </a>
                                    @else
                                        {{ $adjustment->formatted_order_number ?? ('#' . ($adjustment->order_number ?? '—')) }}
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">
                                    @lang('modules.table.tableCode'): {{ $adjustment->table_code ?? '—' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                <div class="font-semibold">{{ $adjustment->menu_item_name ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $adjustment->menu_item_variation_name }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                @if($adjustment->action === 'deleted')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200 border border-red-200 dark:border-red-800">
                                        @lang('app.deleted')
                                    </span>
                                @elseif($adjustment->action === 'quantity_updated')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-800">
                                        @lang('app.updated')
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                        {{ __('app.' . $adjustment->action) }}
                                    </span>
                                @endif

                                @if($adjustment->action === 'quantity_updated')
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $adjustment->quantity_before }} &rarr; {{ $adjustment->quantity_after }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                <div class="font-semibold">
                                    {{ $adjustment->performed_by_name ?? $adjustment->performedBy?->name ?? __('app.system') }}
                                </div>
                                @if ($adjustment->performedBy?->email)
                                    <div class="text-xs text-gray-500">{{ $adjustment->performedBy->email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                {{ $adjustment->note }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-sm text-center text-gray-500 dark:text-gray-300">
                                @lang('messages.noDataFound')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $adjustments->onEachSide(1)->links() }}
        </div>
    </div>
</div>

