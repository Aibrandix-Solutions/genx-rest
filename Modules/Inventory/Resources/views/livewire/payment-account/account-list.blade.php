<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="sm:flex sm:items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Payment Accounts</h1>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Manage your cash, bank, and other payment accounts.</p>
            </div>
            <div class="flex gap-3">
                <button wire:click="openTransferModal" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    Fund Transfer
                </button>
                <button wire:click="create" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Account
                </button>
            </div>
        </div>

        <!-- Unlinked Payments Warning -->
        @if($unlinkedCount > 0)
        <div class="rounded-md bg-yellow-50 p-4 mb-6 border border-yellow-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1 md:flex md:justify-between">
                    <p class="text-sm text-yellow-700">
                        There are <strong>{{ $unlinkedCount }}</strong> payments not linked with any account.
                    </p>
                    <p class="mt-3 text-sm md:ml-6 md:mt-0">
                        <a href="{{ route('payment-accounts.report', ['accountId' => 'unlinked']) }}" class="whitespace-nowrap font-medium text-yellow-700 hover:text-yellow-600">
                            View Details
                            <span aria-hidden="true"> &rarr;</span>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        @endif

        <!-- Account Grid -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($accounts as $account)
            <div class="col-span-1 divide-y divide-gray-200 rounded-lg bg-white shadow dark:bg-gray-800 dark:divide-gray-700">
                <div class="flex w-full items-center justify-between space-x-6 p-6">
                    <div class="flex-1 truncate">
                        <div class="flex items-center space-x-3">
                            <h3 class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $account->name }}</h3>
                            @if(!$account->is_active)
                                <span class="inline-block flex-shrink-0 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">Closed</span>
                            @endif
                        </div>
                        <p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400">{{ $account->account_number ?? 'No Account Number' }}</p>
                        <p class="mt-1 truncate text-xs text-gray-400 capitalize">{{ $account->type }}</p>
                    </div>
                    <div class="text-right">
                         <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($account->current_balance, 2) }}</p>
                         <p class="text-xs text-gray-500">Balance</p>
                    </div>
                </div>
                <div>
                    <div class="-mt-px flex divide-x divide-gray-200 dark:divide-gray-700">
                        <div class="flex w-0 flex-1">
                            <a href="{{ route('payment-accounts.report', ['accountId' => $account->id]) }}" class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-bl-lg border border-transparent py-4 text-sm font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-gray-700">
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M1 4a1 1 0 011-1h16a1 1 0 011 1v8a1 1 0 01-1 1H2a1 1 0 01-1-1V4zm12 4a3 3 0 11-6 0 3 3 0 016 0zM4 9a1 1 0 100-2 1 1 0 000 2zm13-1a1 1 0 11-2 0 1 1 0 012 0zM1.75 14.5a.75.75 0 000 1.5c4.417 0 8.693.603 12.749 1.73 1.111.309 2.251-.512 2.251-1.696v-.784a.75.75 0 00-1.5 0v.784a6.658 6.658 0 01-1.658.146C9.189 16.076 5.226 15.465 1.75 14.5z" clip-rule="evenodd" />
                                </svg>
                                Account Book
                            </a>
                        </div>
                        <div class="-ml-px flex w-0 flex-1">
                            <button wire:click="openDepositModal({{ $account->id }})" class="relative inline-flex w-0 flex-1 items-center justify-center gap-x-3 border border-transparent py-4 text-sm font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-gray-700">
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                                </svg>
                                Deposit
                            </button>
                        </div>
                        <div class="-ml-px flex w-0 flex-1">
                            <div class="relative inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-br-lg border border-transparent py-4 text-sm font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-gray-700 cursor-pointer group">
                                <span class="flex items-center gap-2">Actions</span>
                                <div class="absolute bottom-full right-0 hidden group-hover:block w-48 bg-white dark:bg-gray-800 shadow-lg rounded-md ring-1 ring-black ring-opacity-5 z-10 mb-1">
                                    <a href="#" wire:click.prevent="edit({{ $account->id }})" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">Edit</a>
                                    <a href="#" wire:click.prevent="toggleStatus({{ $account->id }})" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">{{ $account->is_active ? 'Close Account' : 'Reactivate Account' }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <div class="mt-4">
            {{ $accounts->links() }}
        </div>
    </div>

    <!-- Add/Edit Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showModal', false)"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $accountId ? 'Edit Account' : 'Add Account' }}</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Account Number</label>
                            <input type="text" wire:model="account_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                            <select wire:model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                                <option value="card">Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        @if(!$accountId)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Initial Balance</label>
                            <input type="number" step="0.01" wire:model="current_balance" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600"></textarea>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3">
                        <button wire:click="save" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700">Save</button>
                        <button wire:click="$set('showModal', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:border-gray-600">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Transfer Modal -->
    @if($showTransferModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showTransferModal', false)"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Fund Transfer</h3>
                    <div class="mt-4 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Account</label>
                                <select wire:model="transferFromId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                                    <option value="">Select Account</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name }} ({{ number_format($acc->current_balance, 2) }})</option>
                                    @endforeach
                                </select>
                                @error('transferFromId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">To Account</label>
                                <select wire:model="transferToId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                                    <option value="">Select Account</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                    @endforeach
                                </select>
                                @error('transferToId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                            <input type="number" step="0.01" wire:model="transferAmount" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                            @error('transferAmount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                            <input type="datetime-local" wire:model="transferDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea wire:model="transferDescription" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600"></textarea>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3">
                        <button wire:click="saveTransfer" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700">Transfer</button>
                        <button wire:click="$set('showTransferModal', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:border-gray-600">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Deposit Modal -->
    @if($showDepositModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showDepositModal', false)"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Deposit Money</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">To Account</label>
                            <select wire:model="depositAccountId" disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-gray-100 dark:bg-gray-700 dark:border-gray-600">
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                            <input type="number" step="0.01" wire:model="depositAmount" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                            @error('depositAmount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                            <input type="datetime-local" wire:model="depositDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Source / Description</label>
                            <textarea wire:model="depositDescription" placeholder="e.g. Initial Capital, Owner Investment" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600"></textarea>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3">
                        <button wire:click="saveDeposit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700">Deposit</button>
                        <button wire:click="$set('showDepositModal', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:border-gray-600">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
