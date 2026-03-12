<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-start justify-between mb-6">
        <div class="space-y-1">
            <h2 class="text-2xl font-semibold tracking-tight leading-tight text-gray-900 dark:text-white">HRM - Holidays</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage holidays (global or branch-specific)</p>
        </div>

        @can('Manage Holidays')
            <x-button type="button" wire:click="create">Add Holiday</x-button>
        @endcan
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-3">
            <x-input type="text" class="w-full" wire:model.live.debounce.300ms="search" placeholder="Search holiday name" />

            <x-select class="w-full" wire:model.live="branchFilter">
                <option value="">All scopes</option>
                <option value="0">Global (All branches)</option>
                @foreach($branches as $b)
                    <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                @endforeach
            </x-select>

            <x-input type="date" class="w-full" wire:model.live="from" />
            <x-input type="date" class="w-full" wire:model.live="to" />

            <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                Showing {{ $rows->total() }} holidays
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="py-2 pr-4">Date</th>
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Scope</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rows as $h)
                        <tr class="text-gray-900 dark:text-gray-100">
                            <td class="py-2 pr-4">{{ $h->date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-2 pr-4 font-medium">
                                {{ $h->name }}
                                @if($h->note)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $h->note }}</div>
                                @endif
                            </td>
                            <td class="py-2 pr-4">
                                @if($h->branch_id)
                                    {{ data_get($branchMap->get($h->branch_id), 'name', 'Branch') }}
                                @else
                                    Global
                                @endif
                            </td>
                            <td class="py-2">
                                <div class="flex items-center gap-2">
                                    @can('Manage Holidays')
                                        <x-secondary-button type="button" wire:click="edit({{ $h->id }})">Edit</x-secondary-button>
                                        <x-danger-button type="button" wire:click="confirmDelete({{ $h->id }})">Delete</x-danger-button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500 dark:text-gray-400">No holidays found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $rows->links() }}
        </div>
    </div>

    <x-dialog-modal wire:model.live="showModal" maxWidth="lg">
        <x-slot name="title">{{ $editingId ? 'Edit Holiday' : 'Add Holiday' }}</x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <x-label value="Date" />
                    <x-input type="date" class="w-full" wire:model="date" />
                    @error('date') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Name" />
                    <x-input type="text" class="w-full" wire:model.defer="name" />
                    @error('name') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-label value="Scope" />
                    <x-select class="w-full" wire:model="branch_id">
                        <option value="">Global (All branches)</option>
                        @foreach($branches as $b)
                            <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                        @endforeach
                    </x-select>
                    @error('branch_id') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
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
        <x-slot name="title">Delete Holiday</x-slot>
        <x-slot name="content">Are you sure you want to delete this holiday?</x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="cancelDelete">Cancel</x-secondary-button>
            <x-danger-button type="button" wire:click="delete">Delete</x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>
