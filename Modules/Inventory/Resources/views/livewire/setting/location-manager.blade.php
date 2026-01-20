<div class="py-6 px-4 dark:bg-gray-900">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">@lang('inventory::modules.locations.title')</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">@lang('inventory::modules.locations.subtitle')</p>
        </div>

        <x-button wire:click="startCreate">
            @lang('inventory::modules.locations.addLocation')
        </x-button>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('inventory::modules.locations.name')</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('inventory::modules.locations.type')</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('inventory::modules.locations.branch')</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('inventory::modules.locations.status')</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($locations as $location)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $location->name }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $location->type === 'warehouse' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                                {{ ucfirst($location->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $location->branch?->name ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $location->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                {{ $location->is_active ? __('inventory::modules.locations.active') : __('inventory::modules.locations.inactive') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium flex gap-3 justify-end">
                            <x-secondary-button wire:click="startEdit({{ $location->id }})">
                                @lang('app.edit')
                            </x-secondary-button>
                            <x-secondary-button wire:click="toggleStatus({{ $location->id }})">
                                {{ $location->is_active ? __('app.disable') : __('app.enable') }}
                            </x-secondary-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            @lang('inventory::modules.locations.empty')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal wire:model="showModal" maxWidth="2xl">
        <div class="p-6 bg-white dark:bg-gray-800">
            <div class="text-lg font-medium text-gray-900 dark:text-white mb-6">
                {{ $editingId ? __('inventory::modules.locations.editLocation') : __('inventory::modules.locations.addLocation') }}
            </div>

            <div class="space-y-4">
                <div>
                    <x-label for="name" :value="__('inventory::modules.locations.name')" />
                    <x-input id="name" type="text" class="mt-1 block w-full" wire:model.defer="name" />
                    <x-input-error for="name" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-label for="type" :value="__('inventory::modules.locations.type')" />
                        <select id="type" wire:model.live="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
                            <option value="warehouse">@lang('inventory::modules.locations.warehouse')</option>
                            <option value="branch">@lang('inventory::modules.locations.branch')</option>
                        </select>
                        <x-input-error for="type" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="branch_id" :value="__('inventory::modules.locations.branch')" />
                        <select id="branch_id" wire:model="branch_id" {{ $type === 'warehouse' ? 'disabled' : '' }} class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
                            <option value="">@lang('inventory::modules.locations.selectBranch')</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="branch_id" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-label for="address" :value="__('inventory::modules.locations.address')" />
                    <textarea id="address" rows="3" wire:model.defer="address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"></textarea>
                    <x-input-error for="address" class="mt-2" />
                </div>

                <div class="flex items-center gap-2">
                    <x-checkbox id="is_active" wire:model="is_active" />
                    <x-label for="is_active" :value="__('inventory::modules.locations.activeLabel')" />
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-5 mt-6 border-t border-gray-200 dark:border-gray-700">
                <x-secondary-button wire:click="$set('showModal', false)">
                    @lang('app.cancel')
                </x-secondary-button>

                <x-button wire:click="save">
                    @lang('app.save')
                </x-button>
            </div>
        </div>
    </x-modal>
</div>
