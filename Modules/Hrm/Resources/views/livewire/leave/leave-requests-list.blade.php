<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-start justify-between mb-6">
        <div class="space-y-1">
            <h2 class="text-2xl font-semibold tracking-tight leading-tight text-gray-900 dark:text-white">HRM - Leave Requests</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Create and manage employee leaves (staff-created)</p>
        </div>

        @can('Manage Leave Requests')
            <x-button type="button" wire:click="create">Add Leave</x-button>
        @endcan
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-3">
            <x-input type="text" class="w-full" wire:model.live.debounce.300ms="search" placeholder="Search employee name/staff code" />

            <x-select class="w-full" wire:model.live="branchId">
                <option value="">All branches</option>
                <option value="0">— Company Level —</option>
                @foreach($branches as $b)
                    <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                @endforeach
            </x-select>

            <x-select class="w-full" wire:model.live="status">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </x-select>

            <x-input type="date" class="w-full" wire:model.live="from" />
            <x-input type="date" class="w-full" wire:model.live="to" />
        </div>
        <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
            Showing {{ $rows->total() }} leave requests
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="py-2 pr-4">Employee</th>
                        <th class="py-2 pr-4">Type</th>
                        <th class="py-2 pr-4">From</th>
                        <th class="py-2 pr-4">To</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rows as $r)
                        <tr class="text-gray-900 dark:text-gray-100">
                            <td class="py-2 pr-4 font-medium">
                                {{ $r->employee?->name ?? '—' }}
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $r->employee?->staff_code ?? '—' }}</div>
                            </td>
                            <td class="py-2 pr-4">{{ $r->leaveType?->name ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $r->from_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $r->to_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-2 pr-4">
                                @if($r->status === 'approved')
                                    <span class="text-emerald-600">Approved</span>
                                @elseif($r->status === 'pending')
                                    <span class="text-amber-600">Pending</span>
                                @elseif($r->status === 'rejected')
                                    <span class="text-rose-600">Rejected</span>
                                @else
                                    <span class="text-gray-600 dark:text-gray-300">{{ ucfirst($r->status) }}</span>
                                @endif
                            </td>
                            <td class="py-2">
                                <div class="flex items-center gap-2">
                                    @can('Manage Leave Requests')
                                        <x-secondary-button type="button" wire:click="edit({{ $r->id }})">Edit</x-secondary-button>
                                        <x-danger-button type="button" wire:click="confirmDelete({{ $r->id }})">Delete</x-danger-button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-gray-500 dark:text-gray-400">No leave requests found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $rows->links() }}
        </div>
    </div>

    <x-dialog-modal wire:model.live="showModal" maxWidth="xl">
        <x-slot name="title">
            {{ $editingId ? 'Edit Leave' : 'Add Leave' }}
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <x-label value="Branch (leave blank for company-level staff)" />
                    <x-select class="w-full" wire:model.live="branch_id">
                        <option value="">Company Level (no branch)</option>
                        @foreach($branches as $b)
                            <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                        @endforeach
                    </x-select>
                    @error('branch_id') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Leave Type" />
                    <x-select class="w-full" wire:model="leave_type_id">
                        <option value="">Select type</option>
                        @foreach($leaveTypes as $t)
                            <option value="{{ $t['id'] }}">{{ $t['name'] }}</option>
                        @endforeach
                    </x-select>
                    @error('leave_type_id') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div class="lg:col-span-2">
                    <x-label value="Employee" />
                    <x-select class="w-full" wire:model="employee_id">
                        <option value="">Select employee</option>
                        @foreach($employees as $e)
                            <option value="{{ $e['id'] }}">{{ $e['name'] }} ({{ $e['staff_code'] ?? '—' }})</option>
                        @endforeach
                    </x-select>
                    @error('employee_id') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="From" />
                    <x-input type="date" class="w-full" wire:model="from_date" />
                    @error('from_date') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="To" />
                    <x-input type="date" class="w-full" wire:model="to_date" />
                    @error('to_date') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Status" />
                    <x-select class="w-full" wire:model="request_status">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </x-select>
                    @error('request_status') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Reason (optional)" />
                    <x-input type="text" class="w-full" wire:model.defer="reason" />
                    @error('reason') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div class="lg:col-span-2">
                    <x-label value="Note (optional)" />
                    <textarea class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" rows="2" wire:model.defer="note"></textarea>
                    @error('note') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="$set('showModal', false)">Cancel</x-secondary-button>
            <x-button type="button" wire:click="save">Save</x-button>
        </x-slot>
    </x-dialog-modal>

    <x-confirmation-modal wire:model.live="showDeleteModal">
        <x-slot name="title">Delete Leave</x-slot>
        <x-slot name="content">Are you sure you want to delete this leave request?</x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="cancelDelete">Cancel</x-secondary-button>
            <x-danger-button type="button" wire:click="delete">Delete</x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>
