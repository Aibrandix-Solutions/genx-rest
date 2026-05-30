<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-start justify-between mb-6">
        <div class="space-y-1">
            <h2 class="text-2xl font-semibold tracking-tight leading-tight text-gray-900 dark:text-white">HRM - Shifts</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Define shifts (global or branch-specific)</p>
        </div>

        @can('Create Shift')
            <x-button type="button" wire:click="create">Add Shift</x-button>
        @endcan
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
            <x-input type="text" class="w-full" wire:model.live.debounce.300ms="search" placeholder="Search shift" />

            <x-select class="w-full" wire:model.live="branchFilterId">
                <option value="">All branches</option>
                <option value="0">Global shifts only</option>
                @foreach($branches as $b)
                    <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                @endforeach
            </x-select>

            <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                Showing {{ $shifts->total() }} shifts
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Branch</th>
                        <th class="py-2 pr-4">Time</th>
                        <th class="py-2 pr-4">Break</th>
                        <th class="py-2 pr-4">Grace</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($shifts as $s)
                        <tr class="text-gray-900 dark:text-gray-100">
                            <td class="py-2 pr-4 font-medium">{{ $s->name }}</td>
                            <td class="py-2 pr-4">{{ $s->branch?->name ?? 'Global' }}</td>
                            <td class="py-2 pr-4">{{ substr($s->getRawOriginal('start_time'),0,5) }} - {{ substr($s->getRawOriginal('end_time'),0,5) }}</td>
                            <td class="py-2 pr-4">{{ $s->break_minutes }} min</td>
                            <td class="py-2 pr-4">{{ $s->grace_minutes }} min</td>
                            <td class="py-2 pr-4">
                                @if($s->is_active)
                                    <span class="text-emerald-600">Active</span>
                                @else
                                    <span class="text-rose-600">Inactive</span>
                                @endif
                            </td>
                            <td class="py-2">
                                <div class="flex items-center gap-2">
                                    @can('Update Shift')
                                        <x-secondary-button type="button" wire:click="edit({{ $s->id }})">Edit</x-secondary-button>
                                    @endcan
                                    @can('Delete Shift')
                                        <x-danger-button type="button" wire:click="confirmDelete({{ $s->id }})">Delete</x-danger-button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-gray-500 dark:text-gray-400">No shifts found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $shifts->links() }}
        </div>
    </div>

    <div class="mt-8 flex items-start justify-between mb-4">
        <div class="space-y-1">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Shift Assignments</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Assign shifts to employees for a date range</p>
        </div>

        @can('Manage Shift Assignments')
            <x-button type="button" wire:click="createAssignment">Assign Shift</x-button>
        @endcan
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-gray-600 dark:text-gray-300">
                    <th class="py-2 pr-4">Employee</th>
                    <th class="py-2 pr-4">Staff Code</th>
                    <th class="py-2 pr-4">Shift</th>
                    <th class="py-2 pr-4">From</th>
                    <th class="py-2 pr-4">To</th>
                    <th class="py-2">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($assignments as $a)
                    <tr class="text-gray-900 dark:text-gray-100">
                        <td class="py-2 pr-4 font-medium">{{ $a->employee?->name ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $a->employee?->staff_code ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $a->shift?->name ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $a->from_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $a->to_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="py-2">
                            <div class="flex items-center gap-2">
                                @can('Manage Shift Assignments')
                                    <x-secondary-button type="button" wire:click="editAssignment({{ $a->id }})">Edit</x-secondary-button>
                                    <x-danger-button type="button" wire:click="confirmDeleteAssignment({{ $a->id }})">Delete</x-danger-button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-gray-500 dark:text-gray-400">No assignments found</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $assignments->links() }}
        </div>
    </div>

    <x-dialog-modal wire:model.live="showAssignModal" maxWidth="xl">
        <x-slot name="title">{{ $assignEditingId ? 'Edit Shift Assignment' : 'Assign Shift' }}</x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <x-label value="Branch" />
                    <x-select class="w-full" wire:model.live="assign_branch_id">
                        <option value="">Select branch</option>
                        @foreach($branches as $b)
                            <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                        @endforeach
                    </x-select>
                    @error('assign_branch_id') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Employee" />
                    <x-select class="w-full" wire:model="assign_employee_id">
                        <option value="">Select employee</option>
                        @foreach($assignEmployees as $e)
                            <option value="{{ $e->id }}">{{ $e->name }}{{ $e->staff_code ? ' (' . $e->staff_code . ')' : '' }}{{ $e->branch_id && $e->branch_id != $assign_branch_id ? ' [' . ($branches[array_search($e->branch_id, array_column($branches, 'id'))]['name'] ?? 'Other') . ']' : '' }}</option>
                        @endforeach
                    </x-select>
                    @error('assign_employee_id') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Shift" />
                    <x-select class="w-full" wire:model="assign_shift_id">
                        <option value="">Select shift</option>
                        @foreach($assignShifts as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}{{ $s->branch_id ? '' : ' (Global)' }}</option>
                        @endforeach
                    </x-select>
                    @error('assign_shift_id') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="From Date" />
                    <x-input type="date" class="w-full" wire:model="assign_from_date" />
                    @error('assign_from_date') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="To Date" />
                    <x-input type="date" class="w-full" wire:model="assign_to_date" />
                    @error('assign_to_date') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-2">
                <x-secondary-button type="button" wire:click="$set('showAssignModal', false)">Cancel</x-secondary-button>
                <x-button type="button" wire:click="saveAssignment">Save</x-button>
            </div>
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model.live="showAssignDeleteModal" maxWidth="md">
        <x-slot name="title">Delete Shift Assignment</x-slot>
        <x-slot name="content">Are you sure you want to delete this shift assignment?</x-slot>
        <x-slot name="footer">
            <div class="flex items-center gap-2">
                <x-secondary-button type="button" wire:click="cancelDeleteAssignment">Cancel</x-secondary-button>
                <x-danger-button type="button" wire:click="deleteAssignment">Delete</x-danger-button>
            </div>
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model.live="showModal" maxWidth="xl">
        <x-slot name="title">{{ $editingId ? 'Edit Shift' : 'Add Shift' }}</x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <x-label value="Branch (optional)" />
                    <x-select class="w-full" wire:model="branch_id">
                        <option value="">Global (All branches)</option>
                        @foreach($branches as $b)
                            <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                        @endforeach
                    </x-select>
                    @error('branch_id') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Name" />
                    <x-input type="text" class="w-full" wire:model="name" />
                    @error('name') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Start Time" />
                    <x-input type="time" class="w-full" wire:model="start_time" />
                    @error('start_time') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="End Time" />
                    <x-input type="time" class="w-full" wire:model="end_time" />
                    @error('end_time') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Break Minutes" />
                    <x-input type="number" class="w-full" wire:model="break_minutes" min="0" />
                    @error('break_minutes') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Grace Minutes" />
                    <x-input type="number" class="w-full" wire:model="grace_minutes" min="0" />
                    @error('grace_minutes') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" class="rounded border-gray-300" wire:model="is_active" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                    </label>
                    @error('is_active') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-2">
                <x-secondary-button type="button" wire:click="$set('showModal', false)">Cancel</x-secondary-button>
                <x-button type="button" wire:click="save">Save</x-button>
            </div>
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model.live="showDeleteModal" maxWidth="md">
        <x-slot name="title">Delete Shift</x-slot>
        <x-slot name="content">Are you sure you want to delete this shift?</x-slot>
        <x-slot name="footer">
            <div class="flex items-center gap-2">
                <x-secondary-button type="button" wire:click="cancelDelete">Cancel</x-secondary-button>
                <x-danger-button type="button" wire:click="delete">Delete</x-danger-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
