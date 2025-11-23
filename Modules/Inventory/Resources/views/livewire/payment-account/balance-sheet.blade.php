<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="sm:flex sm:items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Balance Sheet</h1>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Financial position of your payment accounts.</p>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Branch Filter -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Branch</label>
                    <select wire:model.live="branchId" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Filter -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">As of Date</label>
                    <input type="date" wire:model.live="date" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Assets Section -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        Assets (Liquid Funds)
                    </h3>
                </div>
                
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Account</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Details</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($accounts as $account)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $account->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $account->account_number ?? '-' }} <br>
                                <span class="text-xs">{{ $account->description }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $account->computed_balance >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-red-600' }}">
                                {{ number_format($account->computed_balance, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                No accounts found.
                            </td>
                        </tr>
                        @endforelse
                        
                        <!-- Total Assets Row -->
                        <tr class="bg-green-50 dark:bg-green-900/20 font-bold border-t border-green-200">
                            <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                Total Assets:
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-700 dark:text-green-400">
                                {{ number_format($totalAssets, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Liabilities Section -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        Liabilities (Accounts Payable)
                    </h3>
                </div>
                
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                Supplier Dues (Outstanding)
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                Total pending payments to suppliers
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-red-600 dark:text-red-400">
                                {{ number_format($totalLiabilities, 2) }}
                            </td>
                        </tr>
                        
                        <!-- Total Liabilities Row -->
                        <tr class="bg-red-50 dark:bg-red-900/20 font-bold border-t border-red-200">
                            <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                Total Liabilities:
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-700 dark:text-red-400">
                                {{ number_format($totalLiabilities, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Equity Section -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        Equity
                    </h3>
                </div>
                
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                         <tr class="font-bold">
                            <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                Net Equity (Assets - Liabilities):
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-indigo-600 dark:text-indigo-400">
                                {{ number_format($totalEquity, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
