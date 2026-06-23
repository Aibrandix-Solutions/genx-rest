<div>
    {{-- Header --}}
    <div class="p-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="flex flex-row items-center justify-between gap-4 mb-4">
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Rooms</h1>
            @if(user_can('create_room'))
            <x-button type='button' wire:click="createRoom">
                Add Room
            </x-button>
            @endif
        </div>

        {{-- Summary Stats Bar --}}
        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-4">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2 text-center cursor-pointer transition {{ $statusFilter === 'all' ? 'ring-2 ring-gray-400' : 'hover:ring-2 hover:ring-gray-300' }}" wire:click="$set('statusFilter', 'all')">
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $stats->total ?? 0 }}</div>
                <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 font-medium">Total</div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg px-3 py-2 text-center cursor-pointer transition {{ $statusFilter === 'available' ? 'ring-2 ring-green-500' : 'hover:ring-2 hover:ring-green-400' }}" wire:click="$set('statusFilter', '{{ $statusFilter === 'available' ? 'all' : 'available' }}')">
                <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $stats->available ?? 0 }}</div>
                <div class="text-[10px] uppercase tracking-wider text-green-600/70 dark:text-green-400/70 font-medium">Available</div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 text-center cursor-pointer transition {{ $statusFilter === 'occupied' ? 'ring-2 ring-red-500' : 'hover:ring-2 hover:ring-red-400' }}" wire:click="$set('statusFilter', '{{ $statusFilter === 'occupied' ? 'all' : 'occupied' }}')">
                <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ $stats->occupied ?? 0 }}</div>
                <div class="text-[10px] uppercase tracking-wider text-red-600/70 dark:text-red-400/70 font-medium">Occupied</div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg px-3 py-2 text-center cursor-pointer transition {{ $statusFilter === 'cleaning' ? 'ring-2 ring-yellow-500' : 'hover:ring-2 hover:ring-yellow-400' }}" wire:click="$set('statusFilter', '{{ $statusFilter === 'cleaning' ? 'all' : 'cleaning' }}')">
                <div class="text-lg font-bold text-yellow-600 dark:text-yellow-400">{{ $stats->cleaning ?? 0 }}</div>
                <div class="text-[10px] uppercase tracking-wider text-yellow-600/70 dark:text-yellow-400/70 font-medium">Cleaning</div>
            </div>
            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg px-3 py-2 text-center cursor-pointer transition {{ $statusFilter === 'maintenance' ? 'ring-2 ring-orange-500' : 'hover:ring-2 hover:ring-orange-400' }}" wire:click="$set('statusFilter', '{{ $statusFilter === 'maintenance' ? 'all' : 'maintenance' }}')">
                <div class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ $stats->maintenance ?? 0 }}</div>
                <div class="text-[10px] uppercase tracking-wider text-orange-600/70 dark:text-orange-400/70 font-medium">Maintenance</div>
            </div>
            <div class="bg-gray-100 dark:bg-gray-600/30 rounded-lg px-3 py-2 text-center cursor-pointer transition {{ $statusFilter === 'blocked' ? 'ring-2 ring-gray-500' : 'hover:ring-2 hover:ring-gray-400' }}" wire:click="$set('statusFilter', '{{ $statusFilter === 'blocked' ? 'all' : 'blocked' }}')">
                <div class="text-lg font-bold text-gray-600 dark:text-gray-400">{{ $stats->blocked ?? 0 }}</div>
                <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400/70 font-medium">Blocked</div>
            </div>
        </div>

        {{-- Filters Row --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative w-full sm:w-64">
                <!-- <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div> -->
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Search rooms..." class="pl-9 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <select wire:model.live="roomTypeFilter" class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                <option value="all">All Types</option>
                @foreach($roomTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
            @if($floors->count() > 1)
            <select wire:model.live="floorFilter" class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                <option value="all">All Floors</option>
                @foreach($floors as $f)
                    <option value="{{ $f }}">Floor {{ $f }}</option>
                @endforeach
            </select>
            @endif
            @if($statusFilter !== 'all' || $roomTypeFilter !== 'all' || $floorFilter !== 'all' || $search)
                <button wire:click="clearFilters" class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium">
                    Clear filters
                </button>
            @endif
        </div>
    </div>

    {{-- Rooms Grid --}}
    <div class="p-4 bg-gray-50 dark:bg-gray-900 min-h-[60vh]">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-3">
            @forelse($rooms as $room)
                <div x-data="{ open: false }" class="group relative">
                    <div @class([
                        'relative rounded-xl transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5',
                        'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700',
                        'ring-2 ring-green-400/50' => $room->status === 'available',
                        'ring-2 ring-red-400/50' => $room->status === 'occupied',
                        'ring-2 ring-yellow-400/50' => $room->status === 'cleaning',
                        'ring-2 ring-orange-400/50' => $room->status === 'maintenance',
                        'ring-2 ring-gray-400/50' => $room->status === 'blocked',
                    ])>
                        {{-- Status indicator strip --}}
                        <div @class([
                            'h-1.5 rounded-t-xl',
                            'bg-green-500' => $room->status === 'available',
                            'bg-red-500' => $room->status === 'occupied',
                            'bg-yellow-500' => $room->status === 'cleaning',
                            'bg-orange-500' => $room->status === 'maintenance',
                            'bg-gray-400' => $room->status === 'blocked',
                        ])></div>

                        <div class="p-3">
                            {{-- Room number & menu --}}
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white leading-none">{{ $room->room_number }}</div>
                                    @if($room->floor)
                                        <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Floor {{ $room->floor }}</div>
                                    @endif
                                </div>

                                {{-- 3-dot menu --}}
                                <div class="relative">
                                    <button @click="open = !open" class="p-1 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="4" r="1.5"/><circle cx="10" cy="10" r="1.5"/><circle cx="10" cy="16" r="1.5"/></svg>
                                    </button>
                                    <div x-show="open" x-cloak @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 z-50 mt-1 w-44 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 py-1">

                                        <button @click="open = false" wire:click="viewRoomReservations({{ $room->id }})" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            View Reservations
                                        </button>

                                        @if(user_can('edit_room'))
                                        <button @click="open = false" wire:click="editRoom({{ $room->id }})" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Edit Room
                                        </button>

                                        @if($room->status !== 'occupied')
                                        <div class="border-t border-gray-100 dark:border-gray-600 my-1"></div>
                                        <div class="px-3 py-1">
                                            <div class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 font-medium mb-1">Set Status</div>
                                            @foreach(['available' => 'Available', 'cleaning' => 'Cleaning', 'maintenance' => 'Maintenance', 'blocked' => 'Blocked'] as $key => $label)
                                                @if($room->status !== $key)
                                                <button @click="open = false" wire:click="updateRoomStatus({{ $room->id }}, '{{ $key }}')" class="w-full text-left px-2 py-1.5 text-xs rounded hover:bg-gray-100 dark:hover:bg-gray-600 transition flex items-center gap-2">
                                                    <span @class([
                                                        'w-2 h-2 rounded-full',
                                                        'bg-green-500' => $key === 'available',
                                                        'bg-yellow-500' => $key === 'cleaning',
                                                        'bg-orange-500' => $key === 'maintenance',
                                                        'bg-gray-400' => $key === 'blocked',
                                                    ])></span>
                                                    <span class="text-gray-700 dark:text-gray-200">{{ $label }}</span>
                                                </button>
                                                @endif
                                            @endforeach
                                        </div>
                                        @endif
                                        @endif

                                        @if(user_can('delete_room') && $room->status !== 'occupied')
                                        <div class="border-t border-gray-100 dark:border-gray-600 my-1"></div>
                                        <button @click="open = false" wire:click="confirmDeleteRoom({{ $room->id }})" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Delete Room
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Room icon --}}
                            <div class="flex justify-center my-2">
                                @if($room->status === 'occupied')
                                    <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                @elseif($room->status === 'available')
                                    <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                @elseif($room->status === 'cleaning')
                                    <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                    </div>
                                @elseif($room->status === 'maintenance')
                                    <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                @else
                                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Room type badge --}}
                            <div class="text-center mb-2">
                                <span class="inline-block text-[10px] font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 truncate max-w-full">{{ $room->roomType->name ?? 'N/A' }}</span>
                            </div>

                            {{-- Status badge --}}
                            <div class="text-center mb-2">
                                <span @class([
                                    'inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wider',
                                    'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' => $room->status === 'available',
                                    'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' => $room->status === 'occupied',
                                    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' => $room->status === 'cleaning',
                                    'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' => $room->status === 'maintenance',
                                    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' => $room->status === 'blocked',
                                ])>
                                    {{ ucfirst($room->status) }}
                                </span>
                            </div>

                            {{-- Guest info for occupied rooms --}}
                            @if($room->status === 'occupied' && $room->currentReservation)
                                <div class="bg-red-50 dark:bg-red-900/10 rounded-lg px-2 py-1.5 text-center border border-red-100 dark:border-red-900/30">
                                    <div class="text-[11px] font-medium text-gray-700 dark:text-gray-300 truncate">{{ $room->currentReservation->guest->full_name ?? 'Guest' }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                        Out: {{ $room->currentReservation->checkout_date?->format('M d') }}
                                    </div>
                                </div>
                            @endif

                            {{-- Price --}}
                            @if($room->roomType)
                            <div class="text-center mt-2">
                                <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ currency_format($room->roomType->base_price, restaurant()->currency_id ?? null) }}</span>
                                <span class="text-[10px] text-gray-400">/night</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-20">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">No rooms found</p>
                    <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Try changing your filters or add a new room</p>
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
                        <x-label for="section" value="Section / Wing" />
                        <x-input id="section" type="text" class="block w-full mt-1" wire:model="section" placeholder="e.g. North Wing, Block A" />
                        <x-input-error for="section" class="mt-2" />
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
                    <div>
                        <x-label for="notes" value="Notes" />
                        <textarea id="notes" wire:model="notes" rows="2" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" placeholder="Internal notes about this room..."></textarea>
                        <x-input-error for="notes" class="mt-2" />
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
                        <x-label for="edit_section" value="Section / Wing" />
                        <x-input id="edit_section" type="text" class="block w-full mt-1" wire:model="section" placeholder="e.g. North Wing, Block A" />
                        <x-input-error for="section" class="mt-2" />
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
                    <div>
                        <x-label for="edit_notes" value="Notes" />
                        <textarea id="edit_notes" wire:model="notes" rows="2" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" placeholder="Internal notes about this room..."></textarea>
                        <x-input-error for="notes" class="mt-2" />
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

    {{-- Room Reservations Modal --}}
    <x-right-modal wire:model.live="showRoomReservations">
        <x-slot name="title">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Room {{ $selectedRoomNumber }} &mdash; Reservations
            </div>
        </x-slot>
        <x-slot name="content">
            {{-- Reservation status filter --}}
            <div class="mb-4">
                <select wire:model.live="reservationStatusFilter" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <option value="all">All Reservations</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="checked_in">Checked In</option>
                    <option value="checked_out">Checked Out</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="no_show">No Show</option>
                </select>
            </div>

            @if(count($roomReservations) > 0)
                <div class="space-y-3">
                    @foreach($roomReservations as $res)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 p-3">
                            {{-- Header row --}}
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-mono font-semibold text-blue-600 dark:text-blue-400">{{ $res['reservation_number'] }}</span>
                                <span @class([
                                    'px-2 py-0.5 text-[10px] font-semibold rounded-full uppercase tracking-wider',
                                    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' => $res['status'] === 'confirmed',
                                    'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' => $res['status'] === 'checked_in',
                                    'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => $res['status'] === 'checked_out',
                                    'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' => $res['status'] === 'cancelled',
                                    'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300' => $res['status'] === 'no_show',
                                ])>
                                    {{ ucfirst(str_replace('_', ' ', $res['status'])) }}
                                </span>
                            </div>

                            {{-- Guest --}}
                            @if($res['guest'] ?? null)
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ ($res['guest']['first_name'] ?? '') . ' ' . ($res['guest']['last_name'] ?? '') }}</span>
                            </div>
                            @endif

                            {{-- Dates --}}
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <div class="bg-white dark:bg-gray-800 rounded px-2 py-1.5">
                                    <div class="text-[10px] text-gray-400 uppercase tracking-wider">Check-in</div>
                                    <div class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $res['check_in_date'] ? \Carbon\Carbon::parse($res['check_in_date'])->format('M d, Y') : '—' }}
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded px-2 py-1.5">
                                    <div class="text-[10px] text-gray-400 uppercase tracking-wider">Checkout</div>
                                    <div class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $res['checkout_date'] ? \Carbon\Carbon::parse($res['checkout_date'])->format('M d, Y') : '—' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Amount row --}}
                            <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-600">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ ($res['adults'] ?? 0) }} adult(s){{ ($res['children'] ?? 0) > 0 ? ', ' . $res['children'] . ' child(ren)' : '' }}
                                </div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ currency_format($res['total_amount'] ?? 0, restaurant()->currency_id ?? null) }}
                                </div>
                            </div>

                            {{-- Balance due --}}
                            @if(($res['balance_due'] ?? 0) > 0)
                                <div class="mt-1 text-right">
                                    <span class="text-[10px] text-red-500 font-medium">Balance: {{ currency_format($res['balance_due'], restaurant()->currency_id ?? null) }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No reservations found</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">This room has no {{ $reservationStatusFilter !== 'all' ? str_replace('_', ' ', $reservationStatusFilter) : '' }} reservations yet</p>
                </div>
            @endif
        </x-slot>
    </x-right-modal>
</div>
