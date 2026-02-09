<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Room Types</h1>
            </div>
            <div class="items-center justify-between block sm:flex">
                <div class="lg:flex items-center mb-4 sm:mb-0">
                    <form class="sm:pr-3" action="#" method="GET">
                        <label for="search" class="sr-only">Search</label>
                        <div class="relative w-48 mt-1 sm:w-64 xl:w-96">
                            <x-input id="search" class="block mt-1 w-full" type="text" placeholder="Search room types..." wire:model.live.debounce.500ms="search" />
                        </div>
                    </form>
                </div>

                <div class="lg:inline-flex items-center gap-4">
                    @if(user_can('create_room_type'))
                    <x-button type='button' wire:click="$set('showAddRoomType', true)">Add Room Type</x-button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Room Types List --}}
    <div class="p-4 bg-white dark:bg-gray-800">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($roomTypes as $roomType)
                <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm p-4 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $roomType->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $roomType->description }}</p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded @if($roomType->is_active) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @else bg-gray-100 text-gray-800 @endif">
                            {{ $roomType->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Base Price:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ currency_format($roomType->base_price, restaurant()->currency_id) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Max Occupancy:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $roomType->max_occupancy }} persons</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Total Rooms:</span>
                            <span class="font-medium text-blue-600 dark:text-blue-400">{{ $roomType->rooms->count() }}</span>
                        </div>
                    </div>

                    @if($roomType->amenities && count($roomType->amenities) > 0)
                        <div class="mb-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Amenities:</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach(array_slice($roomType->amenities, 0, 4) as $amenity)
                                    <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded">{{ $amenity }}</span>
                                @endforeach
                                @if(count($roomType->amenities) > 4)
                                    <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded">+{{ count($roomType->amenities) - 4 }}</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center gap-2">
                        @if(user_can('edit_room_type'))
                        <button wire:click="editRoomType({{ $roomType->id }})" class="flex-1 px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 border border-blue-600 dark:border-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30 transition">
                            Edit
                        </button>
                        @endif
                        @if(user_can('delete_room_type'))
                        <button wire:click="confirmDeleteRoomType({{ $roomType->id }})" class="px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 border border-red-600 dark:border-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                            Delete
                        </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-lg">No room types found</p>  
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Add your first room type to get started</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Add Room Type Modal --}}
    <x-right-modal wire:model.live="showAddRoomType">
        <x-slot name="title">
            Add Room Type
        </x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveRoomType">
                <div class="space-y-4">
                    <div>
                        <x-label for="name" value="Name" />
                        <x-input id="name" type="text" class="block w-full mt-1" wire:model="name" required />
                        <x-input-error for="name" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="description" value="Description" />
                        <textarea id="description" wire:model="description" rows="3" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                        <x-input-error for="description" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="base_price" value="Base Price" />
                            <x-input id="base_price" type="number" step="0.01" class="block w-full mt-1" wire:model="base_price" required />
                            <x-input-error for="base_price" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="max_occupancy" value="Max Occupancy" />
                            <x-input id="max_occupancy" type="number" min="1" class="block w-full mt-1" wire:model="max_occupancy" required />
                            <x-input-error for="max_occupancy" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-label for="amenities" value="Amenities (comma separated)" />
                        <x-input id="amenities" type="text" class="block w-full mt-1" wire:model="amenitiesInput" placeholder="WiFi, TV, AC, Minibar" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Separate multiple amenities with commas</p>
                        <x-input-error for="amenitiesInput" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <input id="is_active" type="checkbox" wire:model="is_active" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="is_active" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Active</label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showAddRoomType', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled">
                        Save
                    </x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>

    {{-- Edit Room Type Modal --}}
    <x-right-modal wire:model.live="showEditRoomType">
        <x-slot name="title">
            Edit Room Type
        </x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveRoomType">
                <div class="space-y-4">
                    <div>
                        <x-label for="edit_name" value="Name" />
                        <x-input id="edit_name" type="text" class="block w-full mt-1" wire:model="name" required />
                        <x-input-error for="name" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="edit_description" value="Description" />
                        <textarea id="edit_description" wire:model="description" rows="3" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                        <x-input-error for="description" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="edit_base_price" value="Base Price" />
                            <x-input id="edit_base_price" type="number" step="0.01" class="block w-full mt-1" wire:model="base_price" required />
                            <x-input-error for="base_price" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="edit_max_occupancy" value="Max Occupancy" />
                            <x-input id="edit_max_occupancy" type="number" min="1" class="block w-full mt-1" wire:model="max_occupancy" required />
                            <x-input-error for="max_occupancy" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-label for="edit_amenities" value="Amenities (comma separated)" />
                        <x-input id="edit_amenities" type="text" class="block w-full mt-1" wire:model="amenitiesInput" placeholder="WiFi, TV, AC, Minibar" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Separate multiple amenities with commas</p>
                        <x-input-error for="amenitiesInput" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <input id="edit_is_active" type="checkbox" wire:model="is_active" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="edit_is_active" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Active</label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showEditRoomType', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled">
                        Update
                    </x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>
</div>
