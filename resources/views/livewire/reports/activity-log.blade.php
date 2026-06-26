<div class="space-y-6">
    <div class="p-4 bg-white dark:bg-gray-800">
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">@lang('menu.activityLog')</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">@lang('app.activityLog.description')</p>
        </div>

        <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-3">
            <div class="p-4 bg-blue-50 rounded-xl shadow-sm dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800">
                <h3 class="text-sm font-medium text-blue-600 dark:text-blue-400">@lang('app.activityLog.totalEvents')</h3>
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ $summary['total'] ?? 0 }}</p>
            </div>
            <div class="p-4 bg-emerald-50 rounded-xl shadow-sm dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800">
                <h3 class="text-sm font-medium text-emerald-600 dark:text-emerald-400">@lang('app.activityLog.uniqueUsers')</h3>
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ $summary['unique_users'] ?? 0 }}</p>
            </div>
            <div class="p-4 bg-indigo-50 rounded-xl shadow-sm dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-800">
                <h3 class="text-sm font-medium text-indigo-600 dark:text-indigo-400">@lang('app.activityLog.modulesActive')</h3>
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ $summary['modules'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
        <div class="grid gap-4 md:grid-cols-4 lg:grid-cols-8">
            <div class="lg:col-span-2">
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
                <x-label :value="__('app.activityLog.module')" />
                <x-select class="mt-1 w-full" wire:model.live="moduleFilter">
                    <option value="all">@lang('app.all')</option>
                    @foreach($moduleOptions as $module)
                        <option value="{{ $module }}">{{ ucfirst(str_replace('_', ' ', $module)) }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-label :value="__('app.activityLog.category')" />
                <x-select class="mt-1 w-full" wire:model.live="categoryFilter">
                    <option value="all">@lang('app.all')</option>
                    @foreach($categoryOptions as $category)
                        <option value="{{ $category }}">{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-label :value="__('app.activityLog.event')" />
                <x-select class="mt-1 w-full" wire:model.live="eventFilter">
                    <option value="all">@lang('app.all')</option>
                    @foreach($eventOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-label :value="__('app.user')" />
                <x-select class="mt-1 w-full" wire:model.live="causerFilter">
                    <option value="all">@lang('app.all')</option>
                    @foreach($causerOptions as $userOption)
                        <option value="{{ $userOption->causer_id }}">{{ $userOption->causer_name }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-label :value="__('app.search')" />
                <x-input type="text" class="mt-1 w-full" placeholder="{{ __('app.search') }}..."
                    wire:model.live.debounce.500ms="search" />
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-between mt-4 gap-3">
            <div>
                <x-label :value="__('app.perPage')" />
                <x-select class="mt-1 w-full" wire:model.live="perPage">
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </x-select>
            </div>
            <div class="flex items-center gap-2">
                <x-secondary-button wire:click="resetFilters" type="button">@lang('app.clearFilter')</x-secondary-button>
                <x-button wire:click="exportCsv" type="button">Export CSV</x-button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">@lang('app.dateTime')</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">@lang('app.user')</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">@lang('app.activityLog.module')</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">@lang('app.description')</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase dark:text-gray-300">@lang('app.details')</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($activities as $activity)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40" x-data="{ open: false }">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ optional($activity->created_at)->timezone(timezone())->format('d M Y, h:i:s A') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                {{ $activity->causer_name ?? $activity->causer?->name ?? __('app.system') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    {{ $activity->moduleLabel() }}
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-200 ml-1">
                                    {{ $activity->categoryLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                <div>{{ $activity->description }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $activity->event }}</div>
                                @php
                                    $orderViewUrl = $activity->orderViewUrl();
                                @endphp
                                @if($orderViewUrl && $activity->canViewLinkedOrder())
                                    <a href="{{ $orderViewUrl }}"
                                       @unless(str_contains($orderViewUrl, '/pos/')) wire:navigate @endunless
                                       class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                                        @lang('app.view') @lang('modules.order.order')
                                    </a>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                @if(!empty($activity->properties))
                                    <button type="button" @click="open = !open" class="text-indigo-600 hover:underline dark:text-indigo-400 text-xs">
                                        <span x-show="!open">@lang('app.activityLog.showDetails')</span>
                                        <span x-show="open" x-cloak>@lang('app.activityLog.hideDetails')</span>
                                    </button>
                                    <div x-show="open" x-cloak class="mt-2 text-xs bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-3 rounded max-w-md">
                                        <dl class="space-y-1.5">
                                            @foreach($activity->formattedPropertyRows() as $row)
                                                <div class="grid grid-cols-[minmax(7rem,auto)_1fr] gap-x-3 gap-y-0.5">
                                                    <dt class="font-medium text-gray-600 dark:text-gray-400">{{ $row['label'] }}</dt>
                                                    <dd class="text-gray-900 dark:text-gray-100 break-words">{{ $row['value'] }}</dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    </div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-sm text-center text-gray-500 dark:text-gray-300">
                                @lang('app.activityLog.noEvents')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $activities->onEachSide(1)->links() }}
        </div>
    </div>
</div>
