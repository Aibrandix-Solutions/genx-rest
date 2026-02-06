<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Rooms</h1>
            </div>
            <div class="items-center justify-between block sm:flex">
                <div class="lg:flex items-center mb-4 sm:mb-0 gap-3">
                    <div class="relative w-48 mt-1 sm:w-64">
                        <x-input id="search" class="block mt-1 w-full" type="text" placeholder="Search rooms..." wire:model.live.debounce.500ms="search" />
                    </div>
                    <select wire:model.live="statusFilter" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <option value="all">All Status</option>
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="cleaning">Cleaning</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="blocked">Blocked</option>
                    </select>
                    <select wire:model.live="roomTypeFilter" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <option value="all">All Room Types</option>
                        @foreach($roomTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:inline-flex items-center gap-4">
                    <x-button type='button' wire:click="$set('showAddRoom', true)">Add Room</x-button>
                </div>
            </div>
        </div>
    </div>

    {{-- Rooms Grid --}}
    <div class="p-4 bg-white dark:bg-gray-800">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            @forelse($rooms as $room)
                <div @class([
                    'border-2 rounded-lg p-4 transition cursor-pointer',
                    'border-green-500 bg-green-50 dark:bg-green-900/20' => $room->status === 'available',
                    'border-red-500 bg-red-50 dark:bg-red-900/20' => $room->status === 'occupied',
                    'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' => $room->status === 'cleaning',
                    'border-orange-500 bg-orange-50 dark:bg-orange-900/20' => $room->status === 'maintenance',
                    'border-gray-500 bg-gray-50 dark:bg-gray-900/20' => $room->status === 'blocked',
                ])>
                    <div class="text-center mb-3">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $room->room_number }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $room->roomType->name }}</div>
                        @if($room->floor)
                            <div class="text-xs text-gray-400 dark:text-gray-500">Floor {{ $room->floor }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <span @class([
                            'block text-center text-xs font-medium px-2 py-1 rounded',
                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $room->status === 'available',
                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $room->status === 'occupied',
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $room->status === 'cleaning',
                            'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' => $room->status === 'maintenance',
                            'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' => $room->status === 'blocked',
                        ])>
                            {{ ucfirst($room->status) }}
                        </span>
                    </div>

                    @if($room->status === 'occupied' && $room->currentReservation)
                        <div class="text-xs text-center text-gray-600 dark:text-gray-400 mb-2">
                            {{ $room->currentReservation->guest->full_name }}
                        </div>
                    @endif

                   <div class="flex gap-1">
                        <button wire:click="editRoom({{ $room->id }})" class="flex-1 px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded transition">
                            Edit
                        </button>
                        @if($room->status !== 'occupied')
                            <div class="dropdown relative inline-block">
                                <button class="px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                    ⋮
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">No rooms found</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Add Room Modal --}}
    <x-right-modal wire:model.live="showAddRoom">
        <x-slot name="title">Add Room</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveRoom">
                <div class="space-y-4">
                    <div>
                        <x-label for="room_number" value="Room Number" />
                        <x-input id="room_number" type="text" class="block w-full mt-1" wire:model="room_number" required />
                        <x-input-error for="room_number" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="floor" value="Floor" />
                        <x-input id="floor" type="text" class="block w-full mt-1" wire:model="floor" placeholder="e.g. 1, 2, G" />
                        <x-input-error for="floor" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="room_type_id" value="Room Type" />
                        <select id="room_type_id" wire:model="room_type_id" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required>
                            <option value="">Select Room Type</option>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="room_type_id" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="status" value="Status" />
                        <select id="status" wire:model="status" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="blocked">Blocked</option>
                        </select>
                        <x-input-error for="status" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showAddRoom', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled">
                        Save
                    </x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>

    {{-- Edit Room Modal --}}
    <x-right-modal wire:model.live="showEditRoom">
        <x-slot name="title">Edit Room</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveRoom">
                <div class="space-y-4">
                    <div>
                        <x-label for="edit_room_number" value="Room Number" />
                        <x-input id="edit_room_number" type="text" class="block w-full mt-1" wire:model="room_number" required />
                        <x-input-error for="room_number" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="edit_floor" value="Floor" />
                        <x-input id="edit_floor" type="text" class="block w-full mt-1" wire:model="floor" placeholder="e.g. 1, 2, G" />
                        <x-input-error for="floor" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="edit_room_type_id" value="Room Type" />
                        <select id="edit_room_type_id" wire:model="room_type_id" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required>
                            <option value="">Select Room Type</option>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="room_type_id" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="edit_status" value="Status" />
                        <select id="edit_status" wire:model="status" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="blocked">Blocked</option>
                        </select>
                        <x-input-error for="status" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showEditRoom', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
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
