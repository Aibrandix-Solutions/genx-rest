<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-start justify-between mb-6">
        <div class="space-y-1">
            <h2 class="text-2xl font-semibold tracking-tight leading-tight text-gray-900 dark:text-white">HRM - Employees</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage employees (employees can exist without login)</p>
        </div>

        @can('Create Employee')
            <x-button type="button" wire:click="create">Add Employee</x-button>
        @endcan
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
            <x-input type="text" class="w-full" wire:model.live.debounce.300ms="search" placeholder="Search name/staff code/email/phone" />

            <x-select class="w-full" wire:model.live="branchId">
                <option value="">All branches</option>
                <option value="0">— Company Level —</option>
                @foreach($branches as $b)
                    <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                @endforeach
            </x-select>

            <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                Showing {{ $employees->total() }} employees
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Staff Code</th>
                        <th class="py-2 pr-4">Branch</th>
                        <th class="py-2 pr-4">Department</th>
                        <th class="py-2 pr-4">Designation</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($employees as $e)
                        <tr class="text-gray-900 dark:text-gray-100">
                            <td class="py-2 pr-4 font-medium">
                                {{ $e->name }}
                                @if($e->user)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Login: {{ $e->user->email ?? $e->user->name }}</div>
                                @endif
                            </td>
                            <td class="py-2 pr-4">{{ $e->staff_code ?? '—' }}</td>
                            <td class="py-2 pr-4">
                                @if($e->branch)
                                    {{ $e->branch->name }}
                                    @if($e->extraBranches->isNotEmpty())
                                        <span class="ml-1 text-xs font-medium px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300">Shared</span>
                                    @endif
                                @else
                                    <span class="text-xs font-medium px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300">Company Level</span>
                                @endif
                            </td>
                            <td class="py-2 pr-4">{{ $e->department?->name ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $e->designation?->name ?? '—' }}</td>
                            <td class="py-2 pr-4">
                                @if($e->status === 'active')
                                    <span class="text-emerald-600">Active</span>
                                @elseif($e->status === 'inactive')
                                    <span class="text-amber-600">Inactive</span>
                                @else
                                    <span class="text-rose-600">{{ ucfirst($e->status) }}</span>
                                @endif
                            </td>
                            <td class="py-2">
                                <div class="flex items-center gap-2">
                                    @can('Update Employee')
                                        <x-secondary-button type="button" wire:click="edit({{ $e->id }})">Edit</x-secondary-button>
                                    @endcan
                                    @can('Delete Employee')
                                        <x-danger-button type="button" wire:click="confirmDelete({{ $e->id }})">Delete</x-danger-button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-gray-500 dark:text-gray-400">No employees found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $employees->links() }}
        </div>
    </div>

    <x-dialog-modal wire:model.live="showModal" maxWidth="xl">
        <x-slot name="title">
            {{ $editingId ? 'Edit Employee' : 'Add Employee' }}
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <x-label value="Branch (leave blank for Company Level staff)" />
                    <x-select class="w-full" wire:model="branch_id">
                        <option value="">Company Level (no branch)</option>
                        @foreach($branches as $b)
                            <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                        @endforeach
                    </x-select>
                    @error('branch_id') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Linked User (optional)" />
                    <x-select class="w-full" wire:model="user_id">
                        <option value="">—</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}{{ $u->email ? ' - '.$u->email : '' }}</option>
                        @endforeach
                    </x-select>
                    @error('user_id') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Name" />
                    <x-input type="text" class="w-full" wire:model="name" />
                    @error('name') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Staff Code (auto-generated if empty)" />
                    <x-input type="text" class="w-full" wire:model="staff_code" />
                    @error('staff_code') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Email (optional)" />
                    <x-input type="email" class="w-full" wire:model="email" />
                    @error('email') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Phone (optional)" />
                    <x-input type="text" class="w-full" wire:model="phone" />
                    @error('phone') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Department (optional)" />
                    <x-select class="w-full" wire:model="department_id">
                        <option value="">—</option>
                        @foreach($departments as $dep)
                            <option value="{{ $dep->id }}">{{ $dep->name }}</option>
                        @endforeach
                    </x-select>
                    @error('department_id') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Designation (optional)" />
                    <x-select class="w-full" wire:model="designation_id">
                        <option value="">—</option>
                        @foreach($designations as $des)
                            <option value="{{ $des->id }}">{{ $des->name }}</option>
                        @endforeach
                    </x-select>
                    @error('designation_id') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Hire Date (optional)" />
                    <x-input type="date" class="w-full" wire:model="hire_date" />
                    @error('hire_date') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Employment Type" />
                    <x-select class="w-full" wire:model="employment_type">
                        <option value="full_time">Full time</option>
                        <option value="part_time">Part time</option>
                        <option value="contract">Contract</option>
                        <option value="temporary">Temporary</option>
                    </x-select>
                    @error('employment_type') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Status" />
                    <x-select class="w-full" wire:model="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="terminated">Terminated</option>
                    </x-select>
                    @error('status') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div class="lg:col-span-2 border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/30 rounded-lg p-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <x-label value="Daily Basic Salary" />
                            <x-input type="number" step="0.01" min="0" class="w-full" wire:model.blur="basic_salary_per_day" />
                            @error('basic_salary_per_day') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Used to calculate monthly salary based on worked days</p>
                        </div>
                        <div>
                            <x-label value="Monthly Basic Salary (Informational)" />
                            <x-input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                class="w-full bg-gray-100 dark:bg-gray-700" 
                                wire:model.blur="basic_salary_per_month" 
                            />
                            @error('basic_salary_per_month') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Reference only - payroll uses daily rate × worked days</p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 rounded-lg p-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input 
                            type="checkbox" 
                            wire:model="is_epf_eligible"
                            class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                        />
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Employee is EPF Eligible</span>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">When enabled, EPF (8%) will be deducted from this employee's salary</p>
                        </div>
                    </label>
                </div>

                @if($branch_id)
                <div class="lg:col-span-2 border border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-950/30 rounded-lg p-4">
                    <x-label value="Also Works At (other branches — optional)" />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Select additional branches where this employee works. Their home branch above is always included.</p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($branches as $b)
                            @if((int)$b['id'] !== (int)$branch_id)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model="extraBranchIds"
                                        value="{{ $b['id'] }}"
                                        class="w-4 h-4 text-purple-600 rounded focus:ring-2 focus:ring-purple-500"
                                    />
                                    <span class="text-sm text-gray-800 dark:text-gray-200">{{ $b['name'] }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="lg:col-span-2">
                    <x-label value="Note (optional)" />
                    <x-textarea class="w-full" rows="3" wire:model="note"></x-textarea>
                    @error('note') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
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
        <x-slot name="title">Delete Employee</x-slot>
        <x-slot name="content">Are you sure you want to delete this employee?</x-slot>
        <x-slot name="footer">
            <div class="flex items-center gap-2">
                <x-secondary-button type="button" wire:click="cancelDelete">Cancel</x-secondary-button>
                <x-danger-button type="button" wire:click="delete">Delete</x-danger-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
