<div class="space-y-6">
    <div class="p-4 bg-white dark:bg-gray-800">
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">@lang('menu.kotAdjustmentLog')</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">KOT item deletion and quantity adjustment audit trail.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
            <div class="p-4 bg-blue-50 rounded-xl shadow-sm dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Adjustments</h3>
                    <div class="p-2 bg-blue-100 rounded-lg dark:bg-blue-900/50">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6M7 9h10M7 13h6m-6 4h10M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl break-words font-bold text-gray-800 dark:text-gray-100">{{ $summary['total'] ?? 0 }}</p>
            </div>
            <div class="p-4 bg-red-50 rounded-xl shadow-sm dark:bg-red-900/10 border border-red-100 dark:border-red-800">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-red-600 dark:text-red-400">@lang('app.deleted')</h3>
                    <div class="p-2 bg-red-100 rounded-lg dark:bg-red-900/50">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 12a1 1 0 001 1h6a1 1 0 001-1l1-12M10 11v6m4-6v6"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl break-words font-bold text-gray-800 dark:text-gray-100">{{ $summary['deleted'] ?? 0 }}</p>
            </div>
            <div class="p-4 bg-amber-50 rounded-xl shadow-sm dark:bg-amber-900/10 border border-amber-100 dark:border-amber-800">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-amber-600 dark:text-amber-400">Qty Updated</h3>
                    <div class="p-2 bg-amber-100 rounded-lg dark:bg-amber-900/50">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 17h10M9 9l-2-2-2 2m12 6l2 2 2-2M5 7v10m14-10v10"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl break-words font-bold text-gray-800 dark:text-gray-100">{{ $summary['quantity_updated'] ?? 0 }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl shadow-sm dark:bg-gray-700/30 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-600 dark:text-gray-300">@lang('modules.order.deletedFromOrder')</h3>
                    <div class="p-2 bg-gray-200 rounded-lg dark:bg-gray-700">
                        <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 8h8M6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2zm4 7h4"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl break-words font-bold text-gray-800 dark:text-gray-100">{{ $summary['deleted_from_order'] ?? 0 }}</p>
            </div>
            <div class="p-4 bg-indigo-50 rounded-xl shadow-sm dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-800">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Unique Orders</h3>
                    <div class="p-2 bg-indigo-100 rounded-lg dark:bg-indigo-900/50">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M7 14h10M7 18h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl break-words font-bold text-gray-800 dark:text-gray-100">{{ $summary['unique_orders'] ?? 0 }}</p>
            </div>
            <div class="p-4 bg-emerald-50 rounded-xl shadow-sm dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Unique Users</h3>
                    <div class="p-2 bg-emerald-100 rounded-lg dark:bg-emerald-900/50">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 10-8 0 4 4 0 008 0zM12 15c-4.418 0-8 2.015-8 4.5V21h16v-1.5c0-2.485-3.582-4.5-8-4.5z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl break-words font-bold text-gray-800 dark:text-gray-100">{{ $summary['unique_users'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
        <div class="grid gap-4 md:grid-cols-6">
            <div>
                <x-label :value="__('app.dateRange')" />
                <x-select class="mt-1 w-full" wire:model.live="dateRangeType">
                    <option value="today">@lang('app.today')</option>
                    <option value="yesterday">@lang('app.yesterday')</option>
                    <option value="currentWeek">@lang('app.currentWeek')</option>
                    <option value="lastWeek">@lang('app.lastWeek')</option>
                    <option value="last7Days">@lang('app.last7Days')</option>
                    <option value="currentMonth">@lang('app.currentMonth')</option>
                    <option value="lastMonth">@lang('app.lastMonth')</option>
                    <option value="currentYear">@lang('app.currentYear')</option>
                    <option value="lastYear">@lang('app.lastYear')</option>
                    <option value="custom">@lang('app.custom')</option>
                </x-select>
            </div>
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
                <x-label :value="__('app.user')" />
                <x-select class="mt-1 w-full" wire:model.live="performedBy">
                    <option value="all">@lang('app.all')</option>
                    @foreach($performedByOptions as $userOption)
                        <option value="{{ $userOption->performed_by }}">{{ $userOption->performed_by_name }}</option>
                    @endforeach
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
        <div class="flex flex-wrap items-center justify-between mt-4 gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                <input type="checkbox" wire:model.live="comboOnly" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span>Combo-related only</span>
            </label>
            <div class="flex items-center gap-2">
                <x-secondary-button wire:click="resetFilters" type="button">Reset</x-secondary-button>
                <x-button wire:click="exportCsv" type="button">Export CSV</x-button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow dark:bg-gray-800">
        <div class="overflow-x-auto p-4 space-y-3">
            @forelse ($groupedAdjustments as $group)
                @php
                    $header = $group['header'];
                    $items = $group['items'];
                    $isComboGroup = count($items) > 1 && $header->action === 'deleted';
                @endphp
                <div x-data="{ open: false }" class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <button type="button"
                            @click="open = !open"
                            class="w-full px-4 py-3 text-left bg-gray-50 dark:bg-gray-700/40 hover:bg-gray-100 dark:hover:bg-gray-700/60 transition">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    @if($header->order_id)
                                        <a href="{{ route('pos.kot', $header->order_id) }}?show-order-detail=true"
                                           class="text-indigo-600 hover:text-indigo-900 hover:underline dark:text-indigo-400"
                                           wire:navigate>
                                            {{ $header->formatted_order_number ?? ('#' . ($header->order_number ?? '—')) }}
                                        </a>
                                    @else
                                        {{ $header->formatted_order_number ?? ('#' . ($header->order_number ?? '—')) }}
                                    @endif
                                    <span class="text-xs text-gray-500">|</span>
                                    <span class="text-xs text-gray-500">{{ optional($header->created_at)->timezone(timezone())->format('d M Y, h:i:s A') }}</span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    @lang('modules.table.tableCode'): {{ $header->table_code ?? '—' }} ·
                                    {{ $header->performed_by_name ?? $header->performedBy?->name ?? __('app.system') }} ·
                                    {{ count($items) }} item(s)
                                    @if($isComboGroup)
                                        · <span class="font-semibold text-indigo-600">Combo group deletion</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @if($header->action === 'deleted')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200 border border-red-200 dark:border-red-800">
                                        @lang('app.deleted')
                                    </span>
                                @elseif($header->action === 'quantity_updated')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-800">
                                        Qty Updated
                                    </span>
                                @elseif($header->action === 'deleted_from_order')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                        Deleted From Order
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                        {{ ucfirst(str_replace('_', ' ', (string) $header->action)) }}
                                    </span>
                                @endif
                                <svg class="w-4 h-4 text-gray-500 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </button>

                    <div x-show="open" class="bg-white dark:bg-gray-800">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-2 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                                        @lang('modules.menu.itemName')
                                    </th>
                                    <th class="px-4 py-2 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                                        @lang('modules.order.qty')
                                    </th>
                                    <th class="px-4 py-2 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">
                                        @lang('app.note')
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($items as $adjustment)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                            <div class="font-semibold">{{ $adjustment->menu_item_name ?? '—' }}</div>
                                            <div class="text-xs text-gray-500">{{ $adjustment->menu_item_variation_name }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                            @if($adjustment->action === 'quantity_updated')
                                                <span class="text-xs text-gray-500">{{ $adjustment->quantity_before }} &rarr; {{ $adjustment->quantity_after }}</span>
                                            @else
                                                <span class="text-xs text-gray-500">{{ $adjustment->quantity_before }} &rarr; {{ $adjustment->quantity_after }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200" title="{{ $adjustment->note }}">
                                            {{ \Illuminate\Support\Str::limit((string) $adjustment->note, 110) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-sm text-center text-gray-500 dark:text-gray-300">
                    @lang('kitchen::messages.noDataFound')
                </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $adjustments->onEachSide(1)->links() }}
        </div>
    </div>
</div>

