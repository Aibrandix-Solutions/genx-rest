<div>
    {{-- Header --}}
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white mb-2">Hotel Expenses</h1>
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button wire:click="$set('activeTab', 'expenses')"
                            class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors duration-150 {{ $activeTab === 'expenses' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            Expenses
                        </button>
                        <button wire:click="$set('activeTab', 'departments')"
                            class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors duration-150 {{ $activeTab === 'departments' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            Departments
                        </button>
                    </nav>
                </div>
            </div>
            <div class="items-center justify-between block sm:flex">
                @if($activeTab === 'expenses')
                    <div class="lg:flex items-center mb-4 sm:mb-0 gap-3 flex-wrap">
                        {{-- Search --}}
                        <div class="relative w-48 mt-1 sm:w-56">
                            <x-input id="search" class="block mt-1 w-full" type="text"
                                placeholder="Search title, vendor..."
                                wire:model.live.debounce.400ms="search" />
                        </div>

                        {{-- Status Filter --}}
                        <select wire:model.live="statusFilter"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                            <option value="all">All Status</option>
                            <option value="outstanding">Still owed</option>
                            <option value="paid">Paid</option>
                            <option value="pending">Unpaid</option>
                            <option value="partial">Partially paid</option>
                            <option value="cancelled">Cancelled</option>
                        </select>

                        {{-- Department Filter --}}
                        <select wire:model.live="departmentFilter"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                            <option value="all">All Departments</option>
                            @foreach($departments as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        {{-- Date Range --}}
                        <div class="flex items-center gap-2">
                            <x-input type="date" class="text-sm" wire:model.live="dateFrom" />
                            <span class="text-gray-400 text-xs">to</span>
                            <x-input type="date" class="text-sm" wire:model.live="dateTo" />
                        </div>
                    </div>
                    <div class="lg:inline-flex items-center gap-4">
                        @if(user_can('create_hotel_expense'))
                        <x-button type='button' wire:click="openCreate">Add Expense</x-button>
                        @endif
                    </div>
                @else
                    <div class="lg:flex items-center mb-4 sm:mb-0 gap-3 flex-wrap">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Manage expense categories/departments for your property</div>
                    </div>
                    <div class="lg:inline-flex items-center gap-4">
                        @if(user_can('create_hotel_expense'))
                        <x-button type='button' wire:click="openCreateDept">Add Department</x-button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($activeTab === 'expenses')
        {{-- Summary Cards --}}
        @php $summary = $this->summary; @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 px-4 pb-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Expenses</div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ currency_format($summary['total'], restaurant()->currency_id) }}</div>
                <div class="text-xs text-gray-400">{{ $summary['count'] }} records</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-green-200 dark:border-green-800 p-4">
                <div class="text-xs text-green-600 dark:text-green-400 mb-1">Paid</div>
                <div class="text-xl font-bold text-green-700 dark:text-green-300">{{ currency_format($summary['paid'], restaurant()->currency_id) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-yellow-200 dark:border-yellow-800 p-4 cursor-pointer hover:bg-yellow-50 dark:hover:bg-gray-700"
                wire:click="$set('statusFilter', 'outstanding')" title="Show expenses with balance still owed">
                <div class="text-xs text-yellow-600 dark:text-yellow-400 mb-1">Still owed</div>
                <div class="text-xl font-bold text-yellow-700 dark:text-yellow-300">{{ currency_format($summary['pending'], restaurant()->currency_id) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-800 p-4">
                <div class="text-xs text-blue-600 dark:text-blue-400 mb-1">Date Range</div>
                <div class="text-sm font-semibold text-blue-700 dark:text-blue-300">
                    {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('M d') : '—' }}
                    →
                    {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('M d') : '—' }}
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="flex flex-col px-4">
            <div class="overflow-x-auto">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden shadow rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Date</th>
                                    <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Title</th>
                                    <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Department</th>
                                    <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Vendor</th>
                                    <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Amount</th>
                                    <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Payment</th>
                                    <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Status</th>
                                    <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                @forelse($expenses as $expense)
                                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <td class="p-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400">
                                            {{ $expense->expense_date->format('M d, Y') }}
                                        </td>
                                        <td class="p-4 text-sm font-medium text-gray-900 dark:text-white">
                                            <div>{{ $expense->title }}</div>
                                            @if($expense->description)
                                                <div class="text-xs text-gray-400 truncate max-w-[200px]">{{ $expense->description }}</div>
                                            @endif
                                            @if($expense->receipt_number)
                                                <div class="text-xs text-gray-400">Receipt #{{ $expense->receipt_number }}</div>
                                            @endif
                                        </td>
                                        <td class="p-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                                {{ $expense->department }}
                                            </span>
                                        </td>
                                        <td class="p-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400">
                                            {{ $expense->vendor ?? '—' }}
                                        </td>
                                        <td class="p-4 text-sm font-semibold text-gray-900 whitespace-nowrap dark:text-white">
                                            <div>{{ currency_format($expense->total_amount ?? $expense->amount, restaurant()->currency_id) }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Due: {{ currency_format($expense->balance_due ?? 0, restaurant()->currency_id) }}
                                            </div>
                                            @if($expense->due_date)
                                                <div class="text-xs text-gray-400">Due date: {{ $expense->due_date->format('M d, Y') }}</div>
                                            @endif
                                        </td>
                                        <td class="p-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400 capitalize">
                                            <div>{{ \Modules\Hotel\Entities\HotelExpense::PAYMENT_METHODS[$expense->payment_method] ?? ucfirst($expense->payment_method) }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Paid: {{ currency_format($expense->amount_paid ?? 0, restaurant()->currency_id) }}
                                            </div>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <span @class([
                                                'px-2 py-1 text-xs font-medium rounded',
                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $expense->status === 'paid',
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $expense->status === 'partial',
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $expense->status === 'pending',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => $expense->status === 'cancelled',
                                            ])>
                                                {{ \Modules\Hotel\Entities\HotelExpense::statusLabel($expense->status) }}
                                            </span>
                                        </td>
                                        <td class="p-4 space-x-2 whitespace-nowrap">
                                            @if(user_can('edit_hotel_expense') && $expense->status !== 'cancelled')
                                                <button wire:click="openPaymentModal({{ $expense->id }})"
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-700 dark:hover:bg-emerald-900/50">
                                                    Record Payment
                                                </button>
                                            @endif
                                            @if(user_can('edit_hotel_expense'))
                                                <button wire:click="openEdit({{ $expense->id }})"
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700 dark:hover:bg-blue-900/50">
                                                    Edit
                                                </button>
                                            @endif
                                            @if(user_can('delete_hotel_expense'))
                                                <button wire:click="confirmDelete({{ $expense->id }})"
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700 dark:hover:bg-red-900/50">
                                                    Delete
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-12 text-center">
                                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                            </svg>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">No expenses found for the selected criteria.</p>
                                            @if(user_can('create_hotel_expense'))
                                                <button wire:click="openCreate" class="mt-3 text-blue-600 hover:underline dark:text-blue-400 text-sm">Add your first expense</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="p-4 bg-white dark:bg-gray-800 mt-2">
            {{ $expenses->links() }}
        </div>
    @else
        {{-- Table of Departments --}}
        <div class="flex flex-col px-4">
            <div class="overflow-x-auto">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden shadow rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Name</th>
                                    <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Description</th>
                                    <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Type</th>
                                    <th class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                @forelse($allDepartments as $dept)
                                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <td class="p-4 text-sm font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ $dept->name }}
                                        </td>
                                        <td class="p-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $dept->description ?: '—' }}
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            @if($dept->is_system)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                    System Default
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                                    Custom
                                                </span>
                                            @endif
                                        </td>
                                        <td class="p-4 space-x-2 whitespace-nowrap">
                                            @if(!$dept->is_system)
                                                @if(user_can('edit_hotel_expense'))
                                                    <button wire:click="openEditDept({{ $dept->id }})"
                                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700 dark:hover:bg-blue-900/50">
                                                        Edit
                                                    </button>
                                                @endif
                                                @if(user_can('delete_hotel_expense'))
                                                    <button wire:click="confirmDeleteDept({{ $dept->id }})"
                                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700 dark:hover:bg-red-900/50">
                                                        Delete
                                                    </button>
                                                @endif
                                            @else
                                                <span class="text-xs text-gray-400 dark:text-gray-500 italic flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                                    Locked
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-12 text-center">
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">No departments found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Add / Edit Modal --}}
    <x-right-modal wire:model.live="showModal">
        <x-slot name="title">{{ $editingId ? 'Edit Expense' : 'Add Hotel Expense' }}</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="save">
                <div class="space-y-4">

                    {{-- Title --}}
                    <div>
                        <x-label for="exp_title" value="Expense Title *" />
                        <x-input id="exp_title" type="text" class="block w-full mt-1"
                            wire:model="title" placeholder="e.g. Monthly linen purchase" />
                        <x-input-error for="title" class="mt-1" />
                    </div>

                    {{-- Department + Amount --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="exp_department" value="Department *" />
                            <select id="exp_department" wire:model="department_id"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                <option value="">Select Department</option>
                                @foreach($departments as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="department_id" class="mt-1" />
                        </div>
                        <div>
                            <x-label for="exp_amount" value="Amount *" />
                            <x-input id="exp_amount" type="number" step="0.01" min="0.01"
                                class="block w-full mt-1" wire:model="amount" placeholder="0.00" />
                            <x-input-error for="amount" class="mt-1" />
                        </div>
                    </div>

                    {{-- Date + Due Date --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="exp_date" value="Expense Date *" />
                            <x-input id="exp_date" type="date" class="block w-full mt-1" wire:model="expense_date" />
                            <x-input-error for="expense_date" class="mt-1" />
                        </div>
                        <div>
                            <x-label for="exp_due_date" value="Due Date" />
                            <x-input id="exp_due_date" type="date" class="block w-full mt-1" wire:model="due_date" />
                            <x-input-error for="due_date" class="mt-1" />
                        </div>
                    </div>

                    {{-- Status + Receipt File --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="exp_status" value="Status *" />
                            <select id="exp_status" wire:model="status"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                <option value="paid">Paid in full</option>
                                <option value="partial">Partially paid</option>
                                <option value="pending">Unpaid</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <x-input-error for="status" class="mt-1" />
                        </div>
                        <div>
                            <x-label for="exp_receipt_file" value="Receipt File (max 5MB)" />
                            <x-input id="exp_receipt_file" type="file" class="block w-full mt-1" wire:model="receipt_file" />
                            <x-input-error for="receipt_file" class="mt-1" />
                        </div>
                    </div>

                    {{-- Vendor + Payment Method --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="exp_vendor" value="Vendor / Supplier" />
                            <x-input id="exp_vendor" type="text" class="block w-full mt-1"
                                wire:model="vendor" placeholder="e.g. ABC Supplies" />
                            <x-input-error for="vendor" class="mt-1" />
                        </div>
                        <div>
                            <x-label for="exp_payment_method" value="Payment Method *" />
                            <select id="exp_payment_method" wire:model="payment_method"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                @foreach($methods as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="payment_method" class="mt-1" />
                        </div>
                    </div>

                    {{-- Receipt Number --}}
                    <div>
                        <x-label for="exp_receipt" value="Receipt / Invoice Number" />
                        <x-input id="exp_receipt" type="text" class="block w-full mt-1"
                            wire:model="receipt_number" placeholder="Optional reference number" />
                        <x-input-error for="receipt_number" class="mt-1" />
                    </div>

                    {{-- Description --}}
                    <div>
                        <x-label for="exp_description" value="Notes / Description" />
                        <textarea id="exp_description" wire:model="description" rows="2"
                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                            placeholder="Optional notes..."></textarea>
                        <x-input-error for="description" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showModal', false)"
                        class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled">
                        {{ $editingId ? 'Update Expense' : 'Save Expense' }}
                    </x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>

    {{-- Record Expense Payment Modal --}}
    <x-right-modal wire:model.live="showPaymentModal">
        <x-slot name="title">Record Expense Payment</x-slot>
        <x-slot name="content">
            @if($selectedExpense)
                <div class="mb-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600">
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $selectedExpense->title }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Total: {{ currency_format($selectedExpense->total_amount ?? $selectedExpense->amount, restaurant()->currency_id) }} |
                        Paid: {{ currency_format($selectedExpense->amount_paid ?? 0, restaurant()->currency_id) }} |
                        Due: {{ currency_format($selectedExpense->balance_due ?? 0, restaurant()->currency_id) }}
                    </div>
                </div>
            @endif

            <form wire:submit.prevent="savePayment">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="payment_amount" value="Payment Amount *" />
                            <x-input id="payment_amount" type="number" step="0.01" min="0.01" class="block w-full mt-1" wire:model="payment_amount" />
                            <x-input-error for="payment_amount" class="mt-1" />
                        </div>
                        <div>
                            <x-label for="payment_paid_at" value="Paid At *" />
                            <x-input id="payment_paid_at" type="datetime-local" class="block w-full mt-1" wire:model="payment_paid_at" />
                            <x-input-error for="payment_paid_at" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="payment_method_entry" value="Method *" />
                            <select id="payment_method_entry" wire:model="payment_method_entry"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                @foreach($methods as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="payment_method_entry" class="mt-1" />
                        </div>
                        <div>
                            <x-label for="payment_reference_number" value="Reference Number" />
                            <x-input id="payment_reference_number" type="text" class="block w-full mt-1" wire:model="payment_reference_number" />
                            <x-input-error for="payment_reference_number" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <x-label for="payment_receipt_file" value="Payment Receipt (optional)" />
                        <x-input id="payment_receipt_file" type="file" class="block w-full mt-1" wire:model="payment_receipt_file" />
                        <x-input-error for="payment_receipt_file" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="payment_notes" value="Notes" />
                        <textarea id="payment_notes" wire:model="payment_notes" rows="2"
                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                        <x-input-error for="payment_notes" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showPaymentModal', false)"
                        class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Close
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled">Save Payment</x-button>
                </div>
            </form>

            @if($selectedExpense && $selectedExpense->payments->count())
                <div class="mt-8">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Payment History</h3>
                    <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                        @foreach($selectedExpense->payments as $payment)
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-white dark:bg-gray-800">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ currency_format($payment->amount, restaurant()->currency_id) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($payment->paid_at)->format('M d, Y h:i A') }}
                                    </div>
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                    {{ \Modules\Hotel\Entities\HotelExpense::PAYMENT_METHODS[$payment->payment_method] ?? ucfirst($payment->payment_method) }}
                                    @if($payment->reference_number)
                                        | Ref: {{ $payment->reference_number }}
                                    @endif
                                    @if($payment->paidBy)
                                        | By: {{ $payment->paidBy->name }}
                                    @endif
                                </div>
                                @if($payment->notes)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $payment->notes }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </x-slot>
    </x-right-modal>

    {{-- Add / Edit Department Modal --}}
    <x-right-modal wire:model.live="showDeptModal">
        <x-slot name="title">{{ $editingDeptId ? 'Edit Department' : 'Add Department' }}</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveDept">
                <div class="space-y-4">
                    {{-- Name --}}
                    <div>
                        <x-label for="dept_name" value="Department Name *" />
                        <x-input id="dept_name" type="text" class="block w-full mt-1"
                            wire:model="deptName" placeholder="e.g. Front Desk, Housekeeping" />
                        <x-input-error for="deptName" class="mt-1" />
                    </div>

                    {{-- Description --}}
                    <div>
                        <x-label for="dept_desc" value="Description" />
                        <textarea id="dept_desc" wire:model="deptDescription" rows="3"
                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                            placeholder="Optional description of this department..."></textarea>
                        <x-input-error for="deptDescription" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showDeptModal', false)"
                        class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled">
                        {{ $editingDeptId ? 'Update Department' : 'Save Department' }}
                    </x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>
</div>
