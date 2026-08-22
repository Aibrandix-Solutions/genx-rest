<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-start justify-between mb-6">
        <div class="space-y-1">
            <h2 class="text-2xl font-semibold tracking-tight leading-tight text-gray-900 dark:text-white">HRM - Leave Types</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Define leave types and annual limits</p>
        </div>

        @can('Manage Leave Types')
            <x-button type="button" wire:click="create">Add Leave Type</x-button>
        @endcan
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
            <x-input type="text" class="w-full" wire:model.live.debounce.300ms="search" placeholder="Search by name" />

            <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                Showing {{ $types->total() }} leave types
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Max / year</th>
                        <th class="py-2 pr-4">Paid</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($types as $t)
                        <tr class="text-gray-900 dark:text-gray-100">
                            <td class="py-2 pr-4 font-medium">
                                {{ $t->name }}
                                @if($t->note)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $t->note }}</div>
                                @endif
                            </td>
                            <td class="py-2 pr-4">{{ $t->max_per_year }}</td>
                            <td class="py-2 pr-4">{{ $t->is_paid ? 'Yes' : 'No' }}</td>
                            <td class="py-2 pr-4">
                                @if($t->is_active)
                                    <span class="text-emerald-600">Active</span>
                                @else
                                    <span class="text-rose-600">Inactive</span>
                                @endif
                            </td>
                            <td class="py-2">
                                <div class="flex items-center gap-2">
                                    @can('Manage Leave Types')
                                        <x-secondary-button type="button" wire:click="edit({{ $t->id }})">Edit</x-secondary-button>
                                        <x-danger-button type="button" wire:click="confirmDelete({{ $t->id }})">Delete</x-danger-button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400">No leave types found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $types->links() }}
        </div>
    </div>

    <x-dialog-modal wire:model.live="showModal" maxWidth="lg">
        <x-slot name="title">
            {{ $editingId ? 'Edit Leave Type' : 'Add Leave Type' }}
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <x-label value="Name" />
                    <x-input type="text" class="w-full" wire:model.defer="name" />
                    @error('name') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Max Leaves Per Year" />
                    <x-input type="number" min="0" class="w-full" wire:model.defer="max_per_year" />
                    @error('max_per_year') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center gap-6">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" wire:model.defer="is_paid" />
                        <span class="text-sm text-gray-700 dark:text-gray-200">Paid</span>
                    </label>

                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" wire:model.defer="is_active" />
                        <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
                    </label>
                </div>

                <div>
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
        <x-slot name="title">Delete Leave Type</x-slot>
        <x-slot name="content">Are you sure you want to delete this leave type?</x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="cancelDelete">Cancel</x-secondary-button>
            <x-danger-button type="button" wire:click="delete">Delete</x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>
