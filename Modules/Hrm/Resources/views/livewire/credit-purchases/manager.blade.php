<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Employee Credit Purchases</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Manage employee credit purchases and track payments</p>
        </div>
        <button 
            wire:click="openForm()" 
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition self-start sm:self-auto"
        >
            + New Credit Purchase
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input 
                    type="text" 
                    wire:model.live="searchTerm" 
                    placeholder="Employee name or description..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select 
                    wire:model.live="status"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Items per page</label>
                <select 
                    wire:model.live="perPage"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- POS Due Summary (Read-only) -->
    @php
        $posDueByEmployee = $posDueByEmployee ?? [];
        $posDueRows = collect($employees ?? [])->map(function ($e) use ($posDueByEmployee) {
            $due = (float) ($posDueByEmployee[$e->id] ?? 0);
            return [
                'id' => $e->id,
                'name' => $e->name,
                'due' => $due,
            ];
        })->filter(fn($row) => $row['due'] > 0)->sortByDesc('due')->values();
    @endphp
    @if($posDueRows->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">POS Due (Employee Customers)</h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Read-only: calculated from POS orders with payment due</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Employee</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">Due</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($posDueRows as $r)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $r['name'] }}</td>
                                <td class="px-4 py-2 text-right font-medium text-gray-900 dark:text-white">{{ number_format($r['due'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Credit Purchases Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-4 sm:px-6 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Employee</th>
                        <th class="px-4 sm:px-6 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Description</th>
                        <th class="px-4 sm:px-6 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Amount</th>
                        <th class="px-4 sm:px-6 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Paid</th>
                        <th class="px-4 sm:px-6 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Balance</th>
                        <th class="px-4 sm:px-6 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Status</th>
                        <th class="px-4 sm:px-6 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Auto-Deduct</th>
                        <th class="px-4 sm:px-6 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($creditPurchases as $purchase)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 sm:px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $purchase->employee->name }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                <div class="truncate">{{ $purchase->description }}</div>
                                @if($purchase->category)
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded">
                                        {{ $purchase->category }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">
                                {{ number_format($purchase->amount, 2) }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-600 dark:text-gray-400 text-right">
                                {{ number_format($purchase->paid_amount, 2) }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">
                                {{ number_format($purchase->remaining_balance, 2) }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200',
                                        'partial' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200',
                                        'paid' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200',
                                    ];
                                @endphp
                                <div>
                                    <span class="inline-block px-3 py-1 text-xs font-medium rounded-full {{ $statusColors[$purchase->status] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                                        {{ ucfirst($purchase->status) }}
                                    </span>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($purchase->payment_percentage, 1) }}%</div>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm">
                                @if($purchase->auto_deduct_from_salary)
                                    <span class="inline-block px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded">
                                        ✓ {{ number_format($purchase->auto_deduct_amount, 2) }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-600 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm">
                                <div class="flex items-center justify-end gap-1 flex-wrap">
                                    @if($purchase->status !== 'paid')
                                        <button 
                                            wire:click="openPaymentForm({{ $purchase->id }})"
                                            class="px-2.5 py-1.5 text-xs bg-green-500 hover:bg-green-600 text-white rounded transition whitespace-nowrap"
                                            title="Record Payment"
                                        >
                                            Pay
                                        </button>
                                    @endif
                                    <button 
                                        wire:click="openForm({{ $purchase->id }})"
                                        class="px-2.5 py-1.5 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition whitespace-nowrap"
                                        title="Edit"
                                    >
                                        Edit
                                    </button>
                                    <button 
                                        wire:click="delete({{ $purchase->id }})" 
                                        wire:confirm="Are you sure?"
                                        class="px-2.5 py-1.5 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition whitespace-nowrap"
                                        title="Delete"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 sm:px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                No credit purchases found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $creditPurchases->links() }}
    </div>

    <!-- New/Edit Credit Purchase Modal -->
    @if($showForm)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4 sm:p-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 px-4 sm:px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $editingId ? 'Edit Credit Purchase' : 'New Credit Purchase' }}
                    </h3>
                    <button wire:click="closeForm()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">✕</button>
                </div>

                <div class="p-4 sm:p-6 space-y-4">
                    <!-- Employee Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Employee *</label>
                        <select 
                            wire:model="employee_id"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Select Employee</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->staff_code }})</option>
                            @endforeach
                        </select>
                        @error('employee_id') <span class="text-sm text-red-500 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <!-- Purchase Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Purchase Date *</label>
                        <input 
                            type="date" 
                            wire:model="purchase_date"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        @error('purchase_date') <span class="text-sm text-red-500 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description *</label>
                        <input 
                            type="text" 
                            wire:model="description"
                            placeholder="What was purchased?"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        @error('description') <span class="text-sm text-red-500 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <!-- Amount & Category -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Amount *</label>
                            <input 
                                type="number" 
                                wire:model="amount"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            @error('amount') <span class="text-sm text-red-500 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category</label>
                            <select 
                                wire:model="category"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">Select Category</option>
                                <option value="food">Food & Meals</option>
                                <option value="uniform">Uniform</option>
                                <option value="tools">Tools & Equipment</option>
                                <option value="advance">Advance</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Auto-Deduction -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input 
                                type="checkbox" 
                                wire:model="auto_deduct_from_salary"
                                class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                            />
                            <span class="font-medium text-gray-900 dark:text-white">Deduct from Monthly Salary</span>
                        </label>
                        @if($auto_deduct_from_salary)
                            <div class="mt-3 ml-7">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Monthly Deduction Amount</label>
                                <input 
                                    type="number" 
                                    wire:model="auto_deduct_amount"
                                    step="0.01"
                                    min="0"
                                    placeholder="Leave empty for full balance"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">If empty, employee will be required to pay full balance</p>
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end pt-4 border-t border-gray-200 dark:border-gray-600">
                        <button 
                            wire:click="closeForm()"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 dark:text-white rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="save"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                        >
                            {{ $editingId ? 'Update' : 'Create' }} Credit Purchase
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Payment Recording Modal -->
    @if($showPaymentForm)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4 sm:p-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
                <div class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 px-4 sm:px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Record Payment</h3>
                    <button wire:click="closePaymentForm()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">✕</button>
                </div>

                <div class="p-4 sm:p-6 space-y-4">
                    <!-- Payment Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Amount *</label>
                        <input 
                            type="number" 
                            wire:model="paymentAmount"
                            step="0.01"
                            min="0.01"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        @error('paymentAmount') <span class="text-sm text-red-500 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method *</label>
                        <select 
                            wire:model="paymentMethod"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="cash">Cash</option>
                            <option value="salary_deduction">Salary Deduction</option>
                            <option value="check">Cheque</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <!-- Reference -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reference Number</label>
                        <input 
                            type="text" 
                            wire:model="paymentReference"
                            placeholder="Cheque no, receipt no..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                        <textarea 
                            wire:model="paymentNotes"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        ></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end pt-4 border-t border-gray-200 dark:border-gray-600">
                        <button 
                            wire:click="closePaymentForm()"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 dark:text-white rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="recordPayment"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                        >
                            Record Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
