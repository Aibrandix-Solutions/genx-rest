<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Reservations</h1>
            </div>
            <div class="items-center justify-between block sm:flex">
                <div class="lg:flex items-center mb-4 sm:mb-0 gap-3">
                    <div class="relative w-48 mt-1 sm:w-64">
                        <x-input id="search" class="block mt-1 w-full" type="text" placeholder="Search by guest or reservation #..." wire:model.live.debounce.500ms="search" />
                    </div>
                    <select wire:model.live="statusFilter" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <option value="all">All Status</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="checked_in">Checked In</option>
                        <option value="checked_out">Checked Out</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="no_show">No Show</option>
                    </select>
                    <select wire:model.live="dateFilter" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <option value="all">All Dates</option>
                        <option value="today">Today's Arrivals</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="current">Current Guests</option>
                    </select>
                </div>

                <div class="lg:inline-flex items-center gap-4">
                    @if(user_can('create_reservation'))
                    <x-button type='button' wire:click="$set('showCreateReservation', true)">New Reservation</x-button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Reservations List --}}
    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Reservation #</th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Guest</th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Room</th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Check-in</th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Check-out</th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Status</th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Amount</th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse($reservations as $reservation)
                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <td class="p-4 text-sm font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ $reservation->reservation_number }}
                                        @if($reservation->group_booking_id)
                                            <a href="#" class="inline-flex items-center gap-1 mt-0.5">
                                                <svg class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                                </svg>
                                                <span class="text-[10px] text-purple-600 dark:text-purple-400 font-medium">{{ $reservation->group_booking_id }}</span>
                                            </a>
                                        @endif
                                        @if($reservation->booking_source)
                                            <div class="text-xs text-gray-500">{{ ucfirst($reservation->booking_source) }}</div>
                                        @endif
                                    </td>
                                    <td class="p-4 text-sm font-normal text-gray-900 whitespace-nowrap dark:text-white">
                                        <div class="font-semibold">{{ $reservation->guest->full_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $reservation->guest->email }}</div>
                                    </td>
                                    <td class="p-4 text-sm font-normal text-gray-900 whitespace-nowrap dark:text-white">
                                        @if($reservation->room)
                                            <div>Room {{ $reservation->room->room_number }}</div>
                                            <div class="text-xs text-gray-500">{{ $reservation->room->roomType->name }}</div>
                                        @else
                                            <span class="text-gray-400">TBA</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-sm font-normal text-gray-500 whitespace-nowrap dark:text-gray-400">
                                        <div>{{ $reservation->check_in_date->format('M d, Y') }}</div>
                                        <div class="text-xs">{{ $reservation->check_in_time }}</div>
                                    </td>
                                    <td class="p-4 text-sm font-normal text-gray-500 whitespace-nowrap dark:text-gray-400">
                                        <div>{{ $reservation->checkout_date->format('M d, Y') }}</div>
                                        <div class="text-xs">{{ $reservation->checkout_time }}</div>
                                    </td>
                                    <td class="p-4 whitespace-nowrap">
                                        <span @class([
                                            'px-2 py-1 text-xs font-medium rounded',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $reservation->status === 'confirmed',
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $reservation->status === 'checked_in',
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $reservation->status === 'checked_out',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $reservation->status === 'cancelled',
                                            'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' => $reservation->status === 'no_show',
                                        ])>
                                            {{ ucfirst(str_replace('_', ' ', $reservation->status)) }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-sm font-semibold text-gray-900 whitespace-nowrap dark:text-white">
                                        <div>{{ currency_format($reservation->total_amount, restaurant()->currency_id) }}</div>
                                        @if($reservation->balance_due > 0)
                                            <div class="text-xs text-red-600">Due: {{ currency_format($reservation->balance_due, restaurant()->currency_id) }}</div>
                                        @endif
                                    </td>
                                    <td class="p-4 space-x-2 whitespace-nowrap">
                                        @if($reservation->status === 'confirmed' && user_can('check_in_guest'))
                                            <button wire:click="openCheckIn({{ $reservation->id }})" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                                Check In
                                            </button>
                                        @endif
                                        @if($reservation->status === 'checked_in' && user_can('check_out_guest'))
                                            <button wire:click="editReservation({{ $reservation->id }})" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                                                Checkout
                                            </button>
                                        @endif
                                        @if(in_array($reservation->status, ['confirmed', 'checked_in']) && user_can('add_room_charge'))
                                            <button wire:click="openAddCharge({{ $reservation->id }})" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700">
                                                Add Charge
                                            </button>
                                        @endif
                                        @if(in_array($reservation->status, ['confirmed', 'checked_in', 'checked_out']) && user_can('view_hotel_billing'))
                                            <a href="{{ route('hotel.folio', $reservation->reservation_number) }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                                Folio
                                            </a>
                                        @endif
                                        @if($reservation->status === 'confirmed' && user_can('edit_reservation'))
                                            <button wire:click="confirmMarkNoShow({{ $reservation->id }})" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-amber-500 rounded-lg hover:bg-amber-600" title="Mark as No-Show">
                                                No-Show
                                            </button>
                                        @endif
                                        @if(in_array($reservation->status, ['confirmed', 'checked_in']) && user_can('edit_reservation'))
                                            <button wire:click="confirmCancelReservation({{ $reservation->id }})" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                                Cancel
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-12 text-center">
                                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        <p class="text-gray-500 dark:text-gray-400">No reservations found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="p-4 bg-white dark:bg-gray-800">
        {{ $reservations->links() }}
    </div>

    {{-- Create Reservation Modal --}}
    <x-right-modal wire:model.live="showCreateReservation">
        <x-slot name="title">New Reservation</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveReservation">
                <div class="space-y-4">
                    {{-- Guest Selection --}}
                    <div>
                        <x-label for="create_guest_id" value="Select Guest" />
                        <select id="create_guest_id" wire:model="create_guest_id" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required>
                            <option value="">Select a Guest</option>
                            @foreach($guests as $guest)
                                <option value="{{ $guest->id }}">{{ $guest->full_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="create_guest_id" class="mt-2" />
                        <p class="text-xs text-blue-600 mt-1 cursor-pointer hover:underline" wire:click="$set('showCreateGuest', true)">+ Create New Guest</p>
                    </div>

                    {{-- Dates --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="create_check_in_date" value="Check In" />
                            <x-input id="create_check_in_date" type="date" class="block w-full mt-1" wire:model.live="create_check_in_date" required />
                            <x-input-error for="create_check_in_date" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="create_check_out_date" value="Check Out" />
                            <x-input id="create_check_out_date" type="date" class="block w-full mt-1" wire:model.live="create_check_out_date" required />
                            <x-input-error for="create_check_out_date" class="mt-2" />
                        </div>
                    </div>

                    {{-- Occupancy (default for new rooms) --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="create_adults" value="Default Adults" />
                            <x-input id="create_adults" type="number" min="1" class="block w-full mt-1" wire:model="create_adults" required />
                            <x-input-error for="create_adults" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="create_children" value="Default Children" />
                            <x-input id="create_children" type="number" min="0" class="block w-full mt-1" wire:model="create_children" />
                            <x-input-error for="create_children" class="mt-2" />
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 -mt-2">Sets initial occupancy when selecting rooms. Adjust per room below.</p>

                    <hr class="border-gray-200 dark:border-gray-700">

                    {{-- Room Selection --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                             <div class="flex items-center gap-2">
                                 <x-label for="create_room_id" value="Select Rooms" />
                                 @if(count($selected_rooms) > 0)
                                     <span class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-blue-500 rounded-full">{{ count($selected_rooms) }}</span>
                                 @endif
                             </div>
                             {{-- Room Type Filter --}}
                             <select wire:model.live="create_room_type_id" class="text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                 <option value="">All Types</option>
                                 @foreach($roomTypes as $type)
                                     <option value="{{ $type->id }}">{{ $type->name }}</option>
                                 @endforeach
                             </select>
                        </div>
                        
                        @if(!empty($available_rooms) && count($available_rooms) > 0)
                            <div class="grid grid-cols-3 gap-4 max-h-56 overflow-y-auto pr-1">
                                @foreach($available_rooms as $room)
                                    @php
                                        $isSelected = collect($selected_rooms)->contains('room_id', $room->id);
                                        $roomEntry = collect($selected_rooms)->firstWhere('room_id', $room->id);
                                        $capacityInfo = $this->getRoomCapacityInfo($room, $roomEntry);
                                        $maxOccupancy = $capacityInfo['max'];
                                        $roomGuests = $capacityInfo['total'];
                                        $isOverCapacity = $capacityInfo['is_over'];
                                    @endphp
                                    <div
                                        wire:click="toggleRoom({{ $room->id }})"
                                        class="relative cursor-pointer rounded-lg border p-3 transition-all duration-150
                                            {{ $isSelected
                                                ? 'border-blue-500 bg-blue-50/60 ring-1 ring-blue-500/20 dark:bg-blue-900/20 dark:border-blue-400 dark:ring-blue-400/20'
                                                : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-gray-500' }}"
                                    >
                                        {{-- Selection checkbox indicator --}}
                                        <div class="absolute top-2 right-2 flex items-center justify-center w-5 h-5 rounded border transition-colors
                                            {{ $isSelected
                                                ? 'bg-blue-500 border-blue-500 dark:bg-blue-500 dark:border-blue-500'
                                                : 'border-gray-300 bg-white dark:border-gray-500 dark:bg-gray-600' }}">
                                            @if($isSelected)
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                </svg>
                                            @endif
                                        </div>

                                        {{-- Room number --}}
                                        <div class="flex items-center gap-1.5 mb-2 pr-6">
                                            <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                            </svg>
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $room->room_number }}</span>
                                        </div>

                                        {{-- Type badge --}}
                                        <span class="inline-block px-1.5 py-0.5 text-[10px] font-medium rounded bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300">
                                            {{ $room->roomType->name }}
                                        </span>

                                        {{-- Price (effective / overridden) --}}
                                        @php
                                            $defaultRate = $this->getDefaultRoomNightlyRate($room);
                                            $nightlyRate = $this->getEffectiveRoomNightlyRate($room);
                                            $hasCustomRate = $this->hasCustomRoomRate($room->id);
                                            $hasDynamicOverride = !$hasCustomRate && (float) $defaultRate !== (float) ($room->roomType->base_price ?? 0);
                                            $isEditingRate = (int) $editing_room_rate_id === (int) $room->id;
                                        @endphp
                                        <div class="mt-1.5" wire:click.stop>
                                            @if($isEditingRate)
                                                <div class="flex items-center gap-1" x-data x-init="$nextTick(() => $refs.roomRateInput?.focus())">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0.01"
                                                        wire:model="edit_room_rate_value"
                                                        wire:keydown.enter.prevent="saveEditRoomRate"
                                                        wire:keydown.escape="cancelEditRoomRate"
                                                        x-ref="roomRateInput"
                                                        class="w-full min-w-0 rounded border-blue-300 bg-white px-1.5 py-0.5 text-xs font-semibold text-gray-900 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30 dark:border-blue-700 dark:bg-gray-800 dark:text-white"
                                                    />
                                                    <button type="button" wire:click="saveEditRoomRate" wire:loading.attr="disabled" wire:target="saveEditRoomRate" class="flex-shrink-0 p-0.5 rounded text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/30" title="Save">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                                    </button>
                                                    <button type="button" wire:click="cancelEditRoomRate" class="flex-shrink-0 p-0.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Cancel">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                                @error('edit_room_rate_value')
                                                    <p class="mt-0.5 text-[10px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                                @enderror
                                            @else
                                                <div class="flex items-center gap-0.5">
                                                    <div class="min-w-0 flex-1">
                                                        @if($hasCustomRate)
                                                            <span class="text-[10px] line-through text-gray-400 dark:text-gray-500 mr-0.5">{{ currency_format($defaultRate, restaurant()->currency_id) }}</span>
                                                        @elseif($hasDynamicOverride)
                                                            <span class="text-[10px] line-through text-gray-400 dark:text-gray-500 mr-0.5">{{ currency_format($room->roomType->base_price, restaurant()->currency_id) }}</span>
                                                        @endif
                                                        <span class="text-sm font-semibold {{ ($hasCustomRate || $hasDynamicOverride) ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">{{ currency_format($nightlyRate, restaurant()->currency_id) }}</span>
                                                        <span class="text-[10px] font-normal text-gray-400 dark:text-gray-500">/night</span>
                                                        @if($hasCustomRate)
                                                            <span class="ml-1 inline-flex items-center px-1 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 uppercase tracking-wide">Custom</span>
                                                        @elseif($hasDynamicOverride)
                                                            <span class="ml-1 inline-flex items-center px-1 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 uppercase tracking-wide">Override</span>
                                                        @endif
                                                    </div>
                                                    <button
                                                        type="button"
                                                        wire:click.stop="startEditRoomRate({{ $room->id }})"
                                                        @disabled($editing_room_rate_id !== null && !$isEditingRate)
                                                        class="flex-shrink-0 p-0.5 rounded text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition disabled:opacity-40 dark:hover:bg-blue-950/30"
                                                        title="Edit nightly rate"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Meta: occupancy + floor --}}
                                        <div class="mt-1.5 pt-1.5 border-t border-gray-100 dark:border-gray-600 flex items-center gap-2.5 text-[11px] text-gray-500 dark:text-gray-400">
                                            <span class="flex items-center gap-0.5" title="Max occupancy: {{ $maxOccupancy }}">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                                </svg>
                                                {{ $maxOccupancy }}
                                            </span>
                                            @if($room->floor)
                                                <span class="flex items-center gap-0.5" title="Floor {{ $room->floor }}">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                                    </svg>
                                                    F{{ $room->floor }}
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Over-capacity warning --}}
                                        @if($isOverCapacity)
                                            <div class="mt-1.5 flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-50 border border-amber-200 text-amber-700 dark:bg-amber-900/20 dark:border-amber-700/50 dark:text-amber-400 text-[10px] font-medium">
                                                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                </svg>
                                                {{ $roomGuests }}/{{ $maxOccupancy }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1.5">Click to select/deselect. Max {{ $maxRoomsPerBooking }} rooms per booking.</p>
                        @elseif($create_check_in_date && $create_check_out_date)
                            <div class="p-3 bg-red-50 text-red-700 rounded text-sm dark:bg-red-900/30 dark:text-red-300">
                                No rooms available for these dates and criteria.
                            </div>
                        @else
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Select dates to see available rooms.
                            </div>
                        @endif
                        <x-input-error for="selected_rooms" class="mt-2" />
                    </div>

                    {{-- Selected Rooms Summary with per-room occupancy --}}
                    @if(count($selected_rooms) > 0)
                        <div class="rounded-lg border border-blue-200 bg-blue-50/50 dark:border-blue-800 dark:bg-blue-900/10 p-3">
                            <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                Selected Rooms ({{ count($selected_rooms) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach($selected_rooms as $index => $entry)
                                    @php
                                        $selectedRoom = collect($available_rooms)->firstWhere('id', $entry['room_id']);
                                        $capacityInfo = $selectedRoom
                                            ? $this->getRoomCapacityInfo($selectedRoom, $entry)
                                            : ['max' => 99, 'total' => 0, 'is_over' => false];
                                        $roomMaxOcc = $capacityInfo['max'];
                                        $entryTotal = $capacityInfo['total'];
                                        $entryOver = $capacityInfo['is_over'];
                                    @endphp
                                    @if($selectedRoom)
                                        @php
                                            $selectedNightlyRate = isset($entry['nightly_rate_override'])
                                                ? (float) $entry['nightly_rate_override']
                                                : $this->getEffectiveRoomNightlyRate($selectedRoom);
                                        @endphp
                                        <div class="flex items-center gap-2 bg-white dark:bg-gray-700 rounded-md px-2.5 py-2 border border-gray-200 dark:border-gray-600">
                                            {{-- Room info --}}
                                            <div class="flex-shrink-0 min-w-[70px]">
                                                <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $selectedRoom->room_number }}</span>
                                                <span class="text-[10px] text-gray-500 dark:text-gray-400 ml-1">{{ $selectedRoom->roomType->name }}</span>
                                            </div>
                                            <div class="flex-shrink-0 text-[10px] text-gray-600 dark:text-gray-300 whitespace-nowrap" title="Nightly rate">
                                                {{ currency_format($selectedNightlyRate, restaurant()->currency_id) }}/night
                                            </div>
                                            {{-- Adults --}}
                                            <div class="flex items-center gap-1">
                                                <label class="text-[10px] text-gray-500 dark:text-gray-400">Adults</label>
                                                <input type="number" min="1" value="{{ $entry['adults'] ?? 1 }}"
                                                    wire:change="updateRoomOccupancy({{ $index }}, 'adults', $event.target.value)"
                                                    class="w-14 text-xs text-center border-gray-300 rounded px-1 py-0.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
                                            </div>
                                            {{-- Children --}}
                                            <div class="flex items-center gap-1">
                                                <label class="text-[10px] text-gray-500 dark:text-gray-400">Children</label>
                                                <input type="number" min="0" value="{{ $entry['children'] ?? 0 }}"
                                                    wire:change="updateRoomOccupancy({{ $index }}, 'children', $event.target.value)"
                                                    class="w-14 text-xs text-center border-gray-300 rounded px-1 py-0.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
                                            </div>
                                            {{-- Over-capacity indicator --}}
                                            @if($entryOver)
                                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" title="Exceeds capacity ({{ $entryTotal }}/{{ $roomMaxOcc }})">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                </svg>
                                            @endif
                                            {{-- Remove button --}}
                                            <button type="button" wire:click="removeRoom({{ $index }})" class="ml-auto flex-shrink-0 text-gray-400 hover:text-red-500 transition-colors" title="Remove room">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Booking Source --}}
                    <div>
                        <x-label for="create_booking_source" value="Booking Source" />
                        <select id="create_booking_source" wire:model="create_booking_source" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <option value="walk-in">Walk-in</option>
                            <option value="phone">Phone</option>
                            <option value="website">Website</option>
                            <option value="ota">OTA (Online Travel Agent)</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <x-label for="create_notes" value="Special Requests / Notes" />
                        <textarea id="create_notes" wire:model="create_notes" rows="2" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                        <x-input-error for="create_notes" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showCreateReservation', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled">
                        Create Reservation
                    </x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>

    <x-right-modal wire:model.live="showEditReservation">
        <x-slot name="title">Guest Checkout</x-slot>
        <x-slot name="content">
            @if($checkout_reservation)
                <div class="space-y-4">
                    {{-- Checkout Summary --}}
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Reservation Summary</h4>
                        <div class="text-sm space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Guest:</span>
                                <span class="font-medium dark:text-gray-200">{{ $checkout_reservation->guest->full_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Room:</span>
                                <span class="font-medium dark:text-gray-200">
                                    {{ $checkout_reservation->room ? $checkout_reservation->room->room_number : 'N/A' }} 
                                    ({{ $checkout_reservation->room && $checkout_reservation->room->roomType ? $checkout_reservation->room->roomType->name : '' }})
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Stay:</span>
                                <span class="font-medium dark:text-gray-200">
                                    {{ \Carbon\Carbon::parse($checkout_reservation->check_in_date)->format('M d') }} - 
                                    {{ \Carbon\Carbon::parse($checkout_reservation->checkout_date)->format('M d') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Billing (totals include any pending extended-stay charge + its tax) --}}
                    @php
                        $displayTotal   = (float) $checkout_total_amount + (float) $checkout_extended_amount + (float) $checkout_extended_tax;
                        $displayBalance = (float) $checkout_balance_due  + (float) $checkout_extended_amount + (float) $checkout_extended_tax;
                    @endphp
                    <div>
                        <div class="flex justify-between items-center mb-2 border-b pb-2 dark:border-gray-600">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">Total Charges</span>
                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ currency_format($displayTotal, restaurant()->currency_id) }}</span>
                        </div>

                        @if($displayBalance > 0)
                        <div class="bg-red-50 dark:bg-red-900/30 p-3 rounded-lg mb-4 flex justify-between items-center">
                            <span class="font-semibold text-red-800 dark:text-red-200">Balance Due</span>
                            <span class="font-bold text-red-800 dark:text-red-200">{{ currency_format($displayBalance, restaurant()->currency_id) }}</span>
                        </div>
                        @else
                        <div class="bg-green-50 dark:bg-green-900/30 p-3 rounded-lg mb-4 flex justify-between items-center">
                            <span class="font-semibold text-green-800 dark:text-green-200">Fully Paid</span>
                            <span class="font-bold text-green-800 dark:text-green-200">{{ currency_format(0, restaurant()->currency_id) }}</span>
                        </div>
                        @endif

                        <a href="{{ route('hotel.folio', $checkout_reservation->reservation_number) }}" target="_blank" class="text-sm text-blue-600 hover:underline dark:text-blue-400">View Full Folio &rarr;</a>
                    </div>

                    {{-- Payment Form --}}
                    <form wire:submit.prevent="processCheckout">
                        @php $currencySymbol = restaurant()->currency->currency_symbol ?? 'Rs'; @endphp
                        <div class="space-y-4">
                            <div>
                                <x-label for="checkout_date_actual" value="Checkout Date" />
                                <x-input id="checkout_date_actual" type="date" class="block w-full mt-1" wire:model.live="checkout_date_actual" required />
                                <x-input-error for="checkout_date_actual" class="mt-2" />
                            </div>

                            {{-- Extended Stay Charge panel ─────────────────────────────── --}}
                            @if($checkout_extended_days > 0)
                            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/50 rounded-lg p-3"
                                 x-data="{ editingRate: false, editingAmt: false }">

                                <div class="flex items-center gap-1.5 mb-3">
                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-amber-800 dark:text-amber-200">Extended Stay Charge</span>
                                </div>

                                <div class="flex items-start gap-3 flex-wrap">

                                    {{-- Days stepper --}}
                                    <div>
                                        <label class="block text-xs text-amber-700 dark:text-amber-300 mb-1.5">Extra Days</label>
                                        <div class="flex items-center gap-1">
                                            <button type="button" wire:click="decrementExtendedDays"
                                                class="w-7 h-7 flex items-center justify-center rounded border border-amber-300 dark:border-amber-700 bg-white dark:bg-gray-700 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/40 font-bold text-base leading-none select-none">−</button>
                                            <input type="number" step="0.5" min="0"
                                                wire:model.lazy="checkout_extended_days"
                                                class="w-14 text-center text-sm font-semibold border-amber-300 dark:border-amber-700 dark:bg-gray-700 dark:text-white rounded focus:border-amber-400 focus:ring-amber-400 py-1 px-1" />
                                            <button type="button" wire:click="incrementExtendedDays"
                                                class="w-7 h-7 flex items-center justify-center rounded border border-amber-300 dark:border-amber-700 bg-white dark:bg-gray-700 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/40 font-bold text-base leading-none select-none">+</button>
                                        </div>
                                    </div>

                                    {{-- Rate per day (pencil-editable) --}}
                                    <div class="flex-1 min-w-[100px]">
                                        <label class="block text-xs text-amber-700 dark:text-amber-300 mb-1.5">Rate / Day</label>
                                        <div class="flex items-center gap-1">
                                            <span x-show="!editingRate"
                                                class="flex-1 text-sm font-medium text-gray-900 dark:text-white px-2 py-1">
                                                {{ number_format((float) $checkout_extended_rate, 2) }}
                                            </span>
                                            <input x-show="editingRate" x-cloak type="number" step="0.01" min="0"
                                                wire:model.lazy="checkout_extended_rate"
                                                x-ref="rateInput"
                                                @blur="editingRate = false"
                                                class="flex-1 min-w-0 text-sm border-amber-300 dark:border-amber-700 dark:bg-gray-700 dark:text-white rounded focus:border-amber-400 focus:ring-amber-400 px-2 py-1" />
                                            <button type="button" x-show="!editingRate"
                                                @click="editingRate = true; $nextTick(() => $refs.rateInput.focus())"
                                                class="flex-shrink-0 text-amber-400 hover:text-amber-600 dark:hover:text-amber-300" title="Edit rate">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Total amount (pencil-editable) --}}
                                    <div class="min-w-[100px]">
                                        <label class="block text-xs text-amber-700 dark:text-amber-300 mb-1.5">Amount</label>
                                        <div class="flex items-center gap-1">
                                            <span x-show="!editingAmt"
                                                class="text-sm font-bold text-amber-800 dark:text-amber-200 px-2 py-1">
                                                {{ number_format((float) $checkout_extended_amount, 2) }}
                                            </span>
                                            <input x-show="editingAmt" x-cloak type="number" step="0.01" min="0"
                                                wire:model.lazy="checkout_extended_amount"
                                                x-ref="amtInput"
                                                @blur="editingAmt = false"
                                                class="w-28 text-sm border-amber-300 dark:border-amber-700 dark:bg-gray-700 dark:text-white rounded focus:border-amber-400 focus:ring-amber-400 px-2 py-1" />
                                            <button type="button" x-show="!editingAmt"
                                                @click="editingAmt = true; $nextTick(() => $refs.amtInput.focus())"
                                                class="flex-shrink-0 text-amber-400 hover:text-amber-600 dark:hover:text-amber-300" title="Override amount">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                </div>

                                @if($checkout_extended_tax > 0)
                                <div class="mt-2.5 pt-2 border-t border-amber-200 dark:border-amber-700/50 flex justify-between items-center text-xs">
                                    <span class="text-amber-700 dark:text-amber-300">
                                        Tax ({{ number_format($checkout_reservation->getEffectiveTaxRate(), 2) }}%)
                                    </span>
                                    <span class="font-semibold text-amber-800 dark:text-amber-200">
                                        + {{ number_format($checkout_extended_tax, 2) }}
                                    </span>
                                </div>
                                @endif

                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-2 flex items-center gap-1">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Added to folio as a room-night charge. Tax &amp; service charges are recalculated automatically.
                                </p>
                            </div>
                            @endif
                            {{-- ─────────────────────────────────────────────────────────── --}}

                            @if($paymentSurchargeEnabled)
                            <div
                                class="space-y-4"
                                x-data="{
                                    amount: @entangle('checkout_amount_paid'),
                                    method: @entangle('checkout_payment_method'),
                                    rate: @entangle('checkout_processing_rate'),
                                    currencySymbol: @js($currencySymbol),
                                    get showSurchargeFields() {
                                        return ['card', 'bank_transfer'].includes(this.method);
                                    },
                                    get surchargeAmount() {
                                        const amt = parseFloat(this.amount) || 0;
                                        const rt = parseFloat(this.rate) || 0;
                                        if (!this.showSurchargeFields || amt <= 0 || rt <= 0) return 0;
                                        return Math.round((amt * rt / 100) * 100) / 100;
                                    },
                                    get totalCollected() {
                                        const amt = parseFloat(this.amount) || 0;
                                        return Math.round((amt + this.surchargeAmount) * 100) / 100;
                                    }
                                }"
                            >
                                <div>
                                    <x-label for="checkout_amount_paid" value="Settlement Amount" />
                                    <input id="checkout_amount_paid" type="number" step="0.01" min="0" x-model="amount" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" />
                                    <p class="text-xs text-gray-500 mt-1">Enter 0 if balance was already settled.</p>
                                    <x-input-error for="checkout_amount_paid" class="mt-2" />
                                </div>

                                <div>
                                    <x-label for="checkout_payment_method" value="Payment Method" />
                                    <select id="checkout_payment_method" x-model="method" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="upi">UPI</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <x-input-error for="checkout_payment_method" class="mt-2" />
                                </div>

                                @include('hotel::partials.payment-surcharge-fields', ['rateInputId' => 'checkout_processing_rate'])

                                <div>
                                    <x-label for="checkout_notes" value="Notes" />
                                    <textarea id="checkout_notes" wire:model="checkout_notes" rows="2" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                                </div>
                            </div>
                            @else
                            <div class="space-y-4">
                                <div>
                                    <x-label for="checkout_amount_paid" value="Settlement Amount" />
                                    <input id="checkout_amount_paid" type="number" step="0.01" min="0" wire:model="checkout_amount_paid" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" />
                                    <p class="text-xs text-gray-500 mt-1">Enter 0 if balance was already settled.</p>
                                    <x-input-error for="checkout_amount_paid" class="mt-2" />
                                </div>

                                <div>
                                    <x-label for="checkout_payment_method" value="Payment Method" />
                                    <select id="checkout_payment_method" wire:model="checkout_payment_method" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="upi">UPI</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <x-input-error for="checkout_payment_method" class="mt-2" />
                                </div>

                                <div>
                                    <x-label for="checkout_notes" value="Notes" />
                                    <textarea id="checkout_notes" wire:model="checkout_notes" rows="2" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <x-button type="button" wire:click="$set('showEditReservation', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                Cancel
                            </x-button>
                            <x-button type="submit" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-700">
                                Complete Checkout
                            </x-button>
                        </div>
                    </form>
                </div>
            @endif
        </x-slot>
    </x-right-modal>
    <x-right-modal wire:model.live="showCreateGuest">
        <x-slot name="title">Add New Guest</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveGuest">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="new_guest_first_name" value="First Name" />
                            <x-input id="new_guest_first_name" type="text" class="block w-full mt-1" wire:model="new_guest_first_name" required />
                            <x-input-error for="new_guest_first_name" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="new_guest_last_name" value="Last Name" />
                            <x-input id="new_guest_last_name" type="text" class="block w-full mt-1" wire:model="new_guest_last_name" required />
                            <x-input-error for="new_guest_last_name" class="mt-2" />
                        </div>
                    </div>
                    
                    <div>
                        <x-label for="new_guest_email" value="Email" />
                        <x-input id="new_guest_email" type="email" class="block w-full mt-1" wire:model="new_guest_email" />
                        <x-input-error for="new_guest_email" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="new_guest_phone" value="Phone" />
                        <x-input id="new_guest_phone" type="text" class="block w-full mt-1" wire:model="new_guest_phone" />
                        <x-input-error for="new_guest_phone" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showCreateGuest', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled">
                        Save Guest
                    </x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>

    {{-- Check-In Modal --}}
    <x-right-modal wire:model.live="showCheckInModal">
        <x-slot name="title">Guest Check-In</x-slot>
        <x-slot name="content">
            @if($checkInReservation)
                <div class="space-y-4">
                    {{-- Reservation Summary --}}
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Reservation Summary</h4>
                        <div class="text-sm space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Guest:</span>
                                <span class="font-medium dark:text-gray-200">{{ $checkInReservation->guest->full_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Room:</span>
                                <span class="font-medium dark:text-gray-200">
                                    {{ $checkInReservation->room ? $checkInReservation->room->room_number : 'N/A' }}
                                    ({{ $checkInReservation->room?->roomType?->name }})
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Stay:</span>
                                <span class="font-medium dark:text-gray-200">
                                    {{ $checkInReservation->check_in_date->format('M d') }} -
                                    {{ $checkInReservation->checkout_date->format('M d, Y') }}
                                    ({{ $checkInReservation->getNumberOfNights() }} nights)
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Stay Total --}}
                    <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg flex justify-between items-center">
                        <span class="font-semibold text-blue-800 dark:text-blue-200">Estimated Stay Total</span>
                        <span class="font-bold text-blue-800 dark:text-blue-200 text-lg">{{ currency_format($checkInTotalAmount, restaurant()->currency_id) }}</span>
                    </div>

                    {{-- Advance Payment --}}
                    <form wire:submit.prevent="processCheckIn">
                        @php $currencySymbol = restaurant()->currency->currency_symbol ?? 'Rs'; @endphp
                        @if($paymentSurchargeEnabled)
                        <div
                            class="space-y-4"
                            x-data="{
                                amount: @entangle('checkInAdvanceAmount'),
                                method: @entangle('checkInPaymentMethod'),
                                rate: @entangle('checkInProcessingRate'),
                                currencySymbol: @js($currencySymbol),
                                get showSurchargeFields() {
                                    return ['card', 'bank_transfer'].includes(this.method);
                                },
                                get surchargeAmount() {
                                    const amt = parseFloat(this.amount) || 0;
                                    const rt = parseFloat(this.rate) || 0;
                                    if (!this.showSurchargeFields || amt <= 0 || rt <= 0) return 0;
                                    return Math.round((amt * rt / 100) * 100) / 100;
                                },
                                get totalCollected() {
                                    const amt = parseFloat(this.amount) || 0;
                                    return Math.round((amt + this.surchargeAmount) * 100) / 100;
                                }
                            }"
                        >
                            <div class="border-t pt-4 dark:border-gray-600">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Advance Payment (Optional)</h4>
                            </div>

                            <div>
                                <x-label for="checkInAdvanceAmount" value="Advance Amount" />
                                <input id="checkInAdvanceAmount" type="number" step="0.01" min="0" x-model="amount" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" />
                                <p class="text-xs text-gray-500 mt-1">Enter 0 if no advance payment is being collected.</p>
                                <x-input-error for="checkInAdvanceAmount" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="checkInPaymentMethod" value="Payment Method" />
                                <select id="checkInPaymentMethod" x-model="method" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="upi">UPI</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            @include('hotel::partials.payment-surcharge-fields', ['rateInputId' => 'checkInProcessingRate'])

                            <div>
                                <x-label for="checkInNotes" value="Notes" />
                                <textarea id="checkInNotes" wire:model="checkInNotes" rows="2" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                            </div>
                        </div>
                        @else
                        <div class="space-y-4">
                            <div class="border-t pt-4 dark:border-gray-600">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Advance Payment (Optional)</h4>
                            </div>

                            <div>
                                <x-label for="checkInAdvanceAmount" value="Advance Amount" />
                                <input id="checkInAdvanceAmount" type="number" step="0.01" min="0" wire:model="checkInAdvanceAmount" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" />
                                <p class="text-xs text-gray-500 mt-1">Enter 0 if no advance payment is being collected.</p>
                                <x-input-error for="checkInAdvanceAmount" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="checkInPaymentMethod" value="Payment Method" />
                                <select id="checkInPaymentMethod" wire:model="checkInPaymentMethod" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="upi">UPI</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div>
                                <x-label for="checkInNotes" value="Notes" />
                                <textarea id="checkInNotes" wire:model="checkInNotes" rows="2" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                            </div>
                        </div>
                        @endif

                        <div class="mt-6 flex justify-end gap-3">
                            <x-button type="button" wire:click="$set('showCheckInModal', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                Cancel
                            </x-button>
                            <x-button type="submit" wire:loading.attr="disabled" class="bg-green-600 hover:bg-green-700">
                                Confirm Check-In
                            </x-button>
                        </div>
                    </form>
                </div>
            @endif
        </x-slot>
    </x-right-modal>

    {{-- Add Charge Modal --}}
    <x-right-modal wire:model.live="showAddChargeModal">
        <x-slot name="title">@lang('hotel::modules.folio.addCharge')</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveQuickCharge">
                <div class="space-y-4" x-data="{ showCustomType: @js($charge_type === 'other') }">
                    <div>
                        <x-label for="charge_type" value="{{ __('hotel::modules.folio.chargeType') }}" />
                        <select id="charge_type" wire:model="charge_type"
                            x-on:change="showCustomType = ($event.target.value === 'other')"
                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <option value="room_night">@lang('hotel::modules.folio.roomNight')</option>
                            <option value="minibar">@lang('hotel::modules.folio.minibar')</option>
                            <option value="laundry">@lang('hotel::modules.folio.laundry')</option>
                            <option value="service">@lang('hotel::modules.folio.service')</option>
                            <option value="tax">@lang('hotel::modules.folio.tax')</option>
                            <option value="other">@lang('hotel::modules.folio.other')</option>
                        </select>
                        @error('charge_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div x-show="showCustomType" x-cloak x-transition.opacity.duration.150ms>
                        <x-label for="charge_type_custom" value="{{ __('hotel::modules.folio.customChargeType') }}" />
                        <x-input id="charge_type_custom" type="text" wire:model="charge_type_custom" class="mt-1 block w-full" placeholder="{{ __('hotel::modules.folio.customChargeTypePlaceholder') }}" />
                        @error('charge_type_custom') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <x-label for="charge_description" value="{{ __('app.description') }}" />
                        <x-input id="charge_description" type="text" wire:model="charge_description" class="mt-1 block w-full" placeholder="{{ __('hotel::modules.folio.chargeDescription') }}" />
                        @error('charge_description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <x-label for="charge_amount" value="{{ __('app.amount') }}" />
                        <x-input id="charge_amount" type="number" step="0.01" min="0" wire:model="charge_amount" class="mt-1 block w-full" placeholder="0.00" />
                        @error('charge_amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showAddChargeModal', false)" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                        @lang('app.cancel')
                    </button>
                    <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700">
                        @lang('hotel::modules.folio.addCharge')
                    </button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>
</div>
