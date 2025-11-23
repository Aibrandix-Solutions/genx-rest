<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="sm:flex sm:items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Cash Flow Statement</h1>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Analysis of cash inflows and outflows.</p>
            </div>
            <div class="flex items-center space-x-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Branch</label>
                    <select wire:model.live="branchId" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                    <input type="date" wire:model.live="startDate" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">End Date</label>
                    <input type="date" wire:model.live="endDate" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Group By</label>
                    <select wire:model.live="groupBy" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="day">Day</option>
                        <option value="month">Month</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                <h3 class="text-sm font-medium text-green-800 dark:text-green-300">Total Inflow</h3>
                <p class="text-2xl font-bold text-green-900 dark:text-green-100">+{{ number_format($totalIn, 2) }}</p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                <h3 class="text-sm font-medium text-red-800 dark:text-red-300">Total Outflow</h3>
                <p class="text-2xl font-bold text-red-900 dark:text-red-100">-{{ number_format($totalOut, 2) }}</p>
            </div>
            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg border border-indigo-200 dark:border-indigo-800">
                <h3 class="text-sm font-medium text-indigo-800 dark:text-indigo-300">Net Cash Flow</h3>
                <p class="text-2xl font-bold {{ $netChange >= 0 ? 'text-green-700' : 'text-red-700' }}">
                    {{ $netChange >= 0 ? '+' : '' }}{{ number_format($netChange, 2) }}
                </p>
            </div>
        </div>

        <!-- Chart Placeholder (Optional) -->
        <!-- <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 mb-6 h-64 flex items-center justify-center text-gray-500">
            Chart visualization would go here
        </div> -->

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wider">Inflow (Debit)</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wider">Outflow (Credit)</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Net Change</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($flows as $flow)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $flow->label }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 dark:text-green-400">
                                {{ number_format($flow->total_in, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 dark:text-red-400">
                                {{ number_format($flow->total_out, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold {{ $flow->net >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($flow->net, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                No data found for this period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
