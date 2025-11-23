<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="sm:flex sm:items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Payment Account Report</h1>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Track all money movements across your accounts.</p>
            </div>
            <div>
                <button wire:click="export" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <!-- Account Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Account</label>
                    <select wire:model.live="accountId" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">All Accounts</option>
                        <option value="unlinked" class="text-red-600 font-medium">Unlinked (No Account)</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <select wire:model.live="type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="all">All Types</option>
                        <option value="credit">Credit (Money Out)</option>
                        <option value="debit">Debit (Money In)</option>
                        <option value="unlinked">Unlinked Payments</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                    <input type="date" wire:model.live="startDate" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                    <input type="date" wire:model.live="endDate" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search descriptions..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
        </div>

        @if($type !== 'unlinked')
        <!-- Summary Cards (Hide only if strictly filtering for unlinked) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                <h3 class="text-sm font-medium text-green-800 dark:text-green-300">Total Income (Debit)</h3>
                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ number_format($totalDebit, 2) }}</p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                <h3 class="text-sm font-medium text-red-800 dark:text-red-300">Total Expense (Credit)</h3>
                <p class="text-2xl font-bold text-red-900 dark:text-red-100">{{ number_format($totalCredit, 2) }}</p>
            </div>
            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg border border-indigo-200 dark:border-indigo-800">
                <h3 class="text-sm font-medium text-indigo-800 dark:text-indigo-300">Net Change</h3>
                <p class="text-2xl font-bold {{ ($totalDebit - $totalCredit) >= 0 ? 'text-green-700' : 'text-red-700' }}">
                    {{ number_format($totalDebit - $totalCredit, 2) }}
                </p>
            </div>
        </div>
        @endif

        <!-- Table -->
        <div class="flex flex-col">
            <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-6">Date</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Account</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Description</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Type</th>
                                    <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white">Amount</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white sm:pl-6">
                                            {{ optional($transaction->transaction_date)->format('M d, Y') ?? '-' }}
                                            <div class="text-xs text-gray-500">{{ optional($transaction->transaction_date)->format('H:i') }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                                            @if($transaction->is_linked)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $transaction->account_name }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    Unlinked
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                                            {{ $transaction->description }}
                                            @if(isset($transaction->reference_type) && $transaction->reference_type === 'Modules\Inventory\Entities\SupplierPayment')
                                                 <br><span class="text-xs text-indigo-500">Supplier Payment</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $transaction->type === 'credit' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-right font-semibold {{ $transaction->type === 'credit' ? 'text-red-600' : 'text-green-600' }}">
                                            {{ number_format($transaction->amount, 2) }}
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            @if(!$transaction->is_linked)
                                                <button wire:click="openLinkModal('{{ $transaction->link_type }}', {{ $transaction->link_id }})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Link Account</button>
                                            @elseif($transaction->can_relink)
                                                <button wire:click="openLinkModal('{{ $transaction->link_type }}', {{ $transaction->link_id }})" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-xs">Change Account</button>
                                            @endif

                                            @if(isset($transaction->reference_type) && $transaction->reference_type === 'Modules\Inventory\Entities\SupplierPayment' && isset($transaction->reference_id))
                                                <!-- If we can get supplier ID we could link to it, but reference_id is the payment ID -->
                                                <!-- We would need to load the supplier payment relationship to link to supplier -->
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No transactions found matching your criteria.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    </div>

    <!-- Link Payment Modal -->
    @if($showLinkModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showLinkModal', false)"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ $linkAccountId ? 'Update Linked Account' : 'Link Payment to Account' }}
                    </h3>
                    <div class="mt-4 space-y-4">
                        <div class="bg-gray-50 p-3 rounded-md dark:bg-gray-700">
                            <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Item:</strong> {{ $linkDescription }}</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Amount:</strong> {{ number_format($linkAmount, 2) }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Account</label>
                            <select wire:model="linkAccountId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                                <option value="">Select...</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ number_format($acc->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                            @error('linkAccountId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3">
                        <button wire:click="saveLink" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700">
                            {{ $linkAccountId ? 'Update Link' : 'Link Account' }}
                        </button>
                        <button wire:click="$set('showLinkModal', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:border-gray-600">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
