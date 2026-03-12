<div class="py-6 px-4 dark:bg-gray-900">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">@lang('inventory::modules.admin.purchaseOverview')</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">@lang('inventory::modules.admin.purchaseOverviewSubtitle')</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- Total Purchases -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-lg shadow-sm">
            <div class="px-4 py-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang('inventory::modules.admin.totalPurchases')</p>
                <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($stats['total']) }}</h3>
            </div>
        </div>

        <!-- Pending -->
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/30 dark:to-yellow-800/30 rounded-lg shadow-sm">
            <div class="px-4 py-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang('inventory::modules.admin.pending')</p>
                <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($stats['pending']) }}</h3>
            </div>
        </div>

        <!-- Ordered -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 rounded-lg shadow-sm">
            <div class="px-4 py-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang('inventory::modules.admin.ordered')</p>
                <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($stats['ordered']) }}</h3>
            </div>
        </div>

        <!-- Received -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 rounded-lg shadow-sm">
            <div class="px-4 py-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang('inventory::modules.admin.received')</p>
                <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($stats['received']) }}</h3>
            </div>
        </div>

        <!-- Total Value -->
        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 rounded-lg shadow-sm">
            <div class="px-4 py-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">@lang('inventory::modules.admin.totalValue')</p>
                <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ restaurant()->currency_symbol }}{{ number_format($stats['total_value'], 2) }}</h3>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Search -->
            <div>
                <x-label value="@lang('app.search')" />
                <x-input type="text" placeholder="@lang('app.referenceOrSupplier')" wire:model.live="search" class="mt-1" />
            </div>

            <!-- Branch Filter -->
            <div>
                <x-label value="@lang('app.branch')" />
                <select wire:model.live="branchFilter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="">@lang('app.all')</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Location Filter -->
            <div>
                <x-label value="@lang('inventory::modules.stock.location')" />
                <select wire:model.live="locationFilter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="">@lang('app.all')</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }} ({{ ucfirst($location->type) }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <x-label value="@lang('app.status')" />
                <select wire:model.live="statusFilter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="">@lang('app.all')</option>
                    <option value="pending">@lang('inventory::modules.admin.pending')</option>
                    <option value="ordered">@lang('inventory::modules.admin.ordered')</option>
                    <option value="received">@lang('inventory::modules.admin.received')</option>
                </select>
            </div>

            <!-- Clear Filters -->
            <div class="flex items-end">
                <x-secondary-button wire:click="$reset" class="w-full">
                    @lang('inventory::modules.stock.clearFilters')
                </x-secondary-button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('app.reference')</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('inventory::modules.admin.supplier')</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('app.branch')</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('inventory::modules.stock.location')</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('inventory::modules.admin.amount')</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('app.status')</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('app.date')</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($purchases as $purchase)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('purchases.show', $purchase->id) }}" class="text-sm font-medium text-skin-base hover:underline">
                                {{ $purchase->reference_number ?? '#' . $purchase->id }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $purchase->supplier?->name ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $purchase->branch?->name ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($purchase->location)
                                <span class="inline-block px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $purchase->location->name }}
                                </span>
                            @else
                                <span class="text-sm text-gray-500">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ restaurant()->currency_symbol }}{{ number_format($purchase->total_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ 
                                $purchase->status === 'received' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                ($purchase->status === 'ordered' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' :
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200')
                            }}">
                                {{ ucfirst($purchase->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                            {{ $purchase->order_date ? $purchase->order_date->format('M d, Y') : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('inventory::modules.admin.noPurchasesFound')</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $purchases->links() }}
    </div>
</div>
