<div class="min-h-screen py-8">
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-3">
                    {{ $supplier->name }}
                    @if(!$supplier->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Inactive
                        </span>
                    @endif
                </h1>
                <p class="mt-1 text-sm text-gray-500">{{ $supplier->email }} • {{ $supplier->phone }}</p>
            </div>
            <div class="flex gap-3">
                <x-button class="bg-green-600 hover:bg-green-700 text-white" wire:click="openPaymentModal">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Pay Supplier
                </x-button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                @foreach(['overview', 'ledger', 'payments', 'purchases', 'stock', 'documents', 'settings'] as $tab)
                    <button 
                        wire:click="setTab('{{ $tab }}')"
                        class="{{ $activeTab === $tab ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} 
                               whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm capitalize">
                        {{ ucfirst($tab) }}
                    </button>
                @endforeach
            </nav>
        </div>

        <!-- Global Filter for Ledger and Stock -->
        @if(in_array($activeTab, ['ledger', 'stock']))
        <div class="mb-6 flex justify-end">
            <div class="w-64">
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Filter by Branch</label>
                <select wire:model.live="branchId" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @endif

        <!-- Tab Content -->
        <div class="space-y-6">
            
            <!-- Overview Tab -->
            @if($activeTab === 'overview')
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Balance Card -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Outstanding Balance</dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                            {{ number_format($supplier->balance, 2) }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Purchased -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Purchased</dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                            {{ number_format($supplier->total_purchased, 2) }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                     <!-- Supplier Info -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 col-span-1 md:col-span-3">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">Contact Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Address</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-200 whitespace-pre-line">{{ $supplier->address }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Notes</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-200 whitespace-pre-line">{{ $supplier->note ?? 'No notes added.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Ledger Tab -->
            @if($activeTab === 'ledger')
                <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Debit (+)</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Credit (-)</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($ledgerEntries as $entry)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                        {{ \Carbon\Carbon::parse($entry['date'])->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
                                        {{ $entry['description'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                                        {{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                        {{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-gray-100">
                                        {{ number_format($entry['balance'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No transactions found for this criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Payments Tab -->
            @if($activeTab === 'payments')
                <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 flex justify-between items-center border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Payment History</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Method</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Account</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Note</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Document</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($supplier->payments()->latest('paid_on')->get() as $payment)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                        {{ $payment->paid_on->format('M d, Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200 capitalize">
                                        {{ $payment->payment_method }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                        {{ $payment->account->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $payment->note ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($payment->document_path)
                                            <a href="{{ Storage::url($payment->document_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900">View</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-gray-100">
                                        {{ number_format($payment->amount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No payments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Purchases Tab (Existing Logic) -->
            @if($activeTab === 'purchases')
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                     <div class="flex justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Purchase Orders</h3>
                         <x-button class="items-center inline-flex gap-1" wire:click="$dispatch('showPurchaseOrderModal')">
                            <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            {{ trans('inventory::modules.purchaseOrder.create_title') }}
                        </x-button>
                     </div>
                     
                     <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PO Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($supplier->orders as $order)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $order->po_number }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $order->order_date->format('M d, Y') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $order->status === 'received' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ number_format($order->total_amount, 2) }}</td>
                                         <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button wire:click="$dispatch('viewPurchaseOrder', { purchaseOrder: {{ $order->id }} })" class="text-indigo-600 hover:text-indigo-900">View</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-4 text-gray-500">No purchase orders yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            
             <!-- Stock Tab -->
            @if($activeTab === 'stock')
                 <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Items Supplied</h3>
                    <p class="text-sm text-gray-500 mb-4">Items purchased from this supplier (Received POs).</p>
                    
                    <div class="overflow-x-auto">
                         <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Cost</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Qty Purchased</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($stockItems as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $item['name'] }}
                                        <span class="text-xs text-gray-500">({{ $item['unit'] }})</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                        {{ number_format($item['last_cost'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                        {{ number_format($item['total_qty'], 2) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">No stock history found for this criteria.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                 </div>
            @endif

            <!-- Documents Tab -->
            @if($activeTab === 'documents')
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Upload Document</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Document Name</label>
                                <input type="text" wire:model="newDocumentName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600">
                            </div>
                            <div class="md:col-span-1">
                                <input type="file" wire:model="newDocument" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                            <div>
                                <button wire:click="uploadDocument" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Upload</button>
                            </div>
                        </div>
                        @error('newDocument') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Files</h3>
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($supplier->documents as $doc)
                            <li class="py-4 flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $doc->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $doc->created_at->format('M d, Y') }} by {{ $doc->uploadedBy->name ?? 'Unknown' }}</p>
                                    </div>
                                </div>
                                <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Download</a>
                            </li>
                        @empty
                            <li class="py-4 text-center text-gray-500">No documents uploaded.</li>
                        @endforelse
                    </ul>
                </div>
            @endif

            <!-- Settings Tab -->
            @if($activeTab === 'settings')
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Supplier Settings</h3>
                    
                    <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Supplier Status</h4>
                            <p class="text-sm text-gray-500">Inactive suppliers cannot be selected for new purchase orders.</p>
                        </div>
                        <button wire:click="toggleStatus" 
                            class="{{ $supplier->is_active ? 'bg-green-600' : 'bg-gray-200' }} relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <span class="{{ $supplier->is_active ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                        </button>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <!-- Payment Modal -->
    @if($showPaymentModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showPaymentModal', false)"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">Record Payment</h3>
                        <div class="mt-4 space-y-4">
                            
                            <!-- Amount -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount *</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input type="number" step="0.01" wire:model="paymentAmount" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600" placeholder="0.00">
                                </div>
                                @error('paymentAmount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Paid On -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Paid On *</label>
                                <input type="datetime-local" wire:model="paymentDate" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600">
                                @error('paymentDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Method -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Method *</label>
                                <select wire:model="paymentMethod" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="check">Check</option>
                                </select>
                            </div>

                            <!-- Payment Account -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Account (Optional)</label>
                                <select wire:model="paymentAccount" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600">
                                    <option value="">Select Account...</option>
                                    @foreach($paymentAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Document -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Attach Proof</label>
                                <input type="file" wire:model="paymentDocument" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="mt-1 text-xs text-gray-500">Allowed: .pdf, .jpg, .png, .csv</p>
                                @error('paymentDocument') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Note -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Note</label>
                                <textarea wire:model="paymentNote" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600"></textarea>
                            </div>

                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="button" wire:click="savePayment" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                            Confirm Payment
                        </button>
                        <button type="button" wire:click="$set('showPaymentModal', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <livewire:inventory::purchase-order.manage-purchase-order :supplierId="$supplier->id" />
    <livewire:inventory::purchase-order.view-purchase-order />
</div>
