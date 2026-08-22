<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-start justify-between mb-6">
        <div class="space-y-1">
            <h2 class="text-2xl font-semibold tracking-tight leading-tight text-gray-900 dark:text-white">HRM - Designations</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage designations and map to departments</p>
        </div>

        @can('Create Designation')
            <x-button type="button" wire:click="create">Add Designation</x-button>
        @endcan
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex items-center gap-2">
            <x-input type="text" class="w-full" wire:model.live.debounce.300ms="search" placeholder="Search designation" />
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Department</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($designations as $d)
                        <tr class="text-gray-900 dark:text-gray-100">
                            <td class="py-2 pr-4 font-medium">{{ $d->name }}</td>
                            <td class="py-2 pr-4">{{ $d->department?->name ?? '—' }}</td>
                            <td class="py-2 pr-4">
                                @if($d->is_active)
                                    <span class="text-emerald-600">Active</span>
                                @else
                                    <span class="text-rose-600">Inactive</span>
                                @endif
                            </td>
                            <td class="py-2">
                                <div class="flex items-center gap-2">
                                    @can('Update Designation')
                                        <x-secondary-button type="button" wire:click="edit({{ $d->id }})">Edit</x-secondary-button>
                                    @endcan
                                    @can('Delete Designation')
                                        <x-danger-button type="button" wire:click="confirmDelete({{ $d->id }})">Delete</x-danger-button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500 dark:text-gray-400">No designations found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $designations->links() }}
        </div>
    </div>

    <x-dialog-modal wire:model.live="showModal" maxWidth="xl">
        <x-slot name="title">
            {{ $editingId ? 'Edit Designation' : 'Add Designation' }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
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
                    <x-label value="Name" />
                    <x-input type="text" class="w-full" wire:model="name" />
                    @error('name') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Description" />
                    <x-textarea class="w-full" rows="3" wire:model="description"></x-textarea>
                    @error('description') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
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
        <x-slot name="title">Delete Designation</x-slot>
        <x-slot name="content">Are you sure you want to delete this designation?</x-slot>
        <x-slot name="footer">
            <div class="flex items-center gap-2">
                <x-secondary-button type="button" wire:click="cancelDelete">Cancel</x-secondary-button>
                <x-danger-button type="button" wire:click="delete">Delete</x-danger-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
