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
                    <x-button type='button' wire:click="$set('showCreateReservation', true)">New Reservation</x-button>
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
                                        @if($reservation->source)
                                            <div class="text-xs text-gray-500">{{ ucfirst($reservation->source) }}</div>
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
                                        @if($reservation->status === 'confirmed')
                                            <button wire:click="checkIn({{ $reservation->id }})" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                                Check In
                                            </button>
                                        @endif
                                        @if($reservation->status === 'checked_in')
                                            <button wire:click="editReservation({{ $reservation->id }})" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                                                Checkout
                                            </button>
                                        @endif
                                        @if(in_array($reservation->status, ['confirmed', 'checked_in', 'checked_out']))
                                            <a href="{{ route('hotel.folio', $reservation->reservation_number) }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                                Folio
                                            </a>
                                        @endif
                                        @if(in_array($reservation->status, ['confirmed', 'checked_in']))
                                            <button wire:click="cancelReservation({{ $reservation->id }})" wire:confirm="Cancel this reservation?" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
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

                    {{-- Occupancy --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="create_adults" value="Adults" />
                            <x-input id="create_adults" type="number" min="1" class="block w-full mt-1" wire:model="create_adults" required />
                            <x-input-error for="create_adults" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="create_children" value="Children" />
                            <x-input id="create_children" type="number" min="0" class="block w-full mt-1" wire:model="create_children" />
                            <x-input-error for="create_children" class="mt-2" />
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700">

                    {{-- Room Selection --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                             <x-label for="create_room_id" value="Select Room" />
                             {{-- Room Type Filter --}}
                             <select wire:model.live="create_room_type_id" class="text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                 <option value="">All Types</option>
                                 @foreach($roomTypes as $type)
                                     <option value="{{ $type->id }}">{{ $type->name }}</option>
                                 @endforeach
                             </select>
                        </div>
                        
                        @if(!empty($available_rooms))
                            <select id="create_room_id" wire:model="create_room_id" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required size="5">
                                <option value="">-- Available Rooms --</option>
                                @foreach($available_rooms as $room)
                                    <option value="{{ $room->id }}">
                                        Room {{ $room->room_number }} - {{ $room->roomType->name }} ({{ currency_format($room->roomType->base_price, restaurant()->currency_id) }}/night)
                                    </option>
                                @endforeach
                            </select>
                        @elseif($create_check_in_date && $create_check_out_date)
                            <div class="p-3 bg-red-50 text-red-700 rounded text-sm dark:bg-red-900/30 dark:text-red-300">
                                No rooms available for these dates and criteria.
                            </div>
                        @else
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Select dates to see available rooms.
                            </div>
                        @endif
                        <x-input-error for="create_room_id" class="mt-2" />
                    </div>

                    {{-- Notes --}}
                    <div>
                        <x-label for="create_notes" value="Notes" />
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

                    {{-- Billing --}}
                    <div>
                        <div class="flex justify-between items-center mb-4 border-b pb-2 dark:border-gray-600">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">Total Amount</span>
                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ currency_format($checkout_total_amount, restaurant()->currency_id) }}</span>
                        </div>
                        
                        <div class="bg-red-50 dark:bg-red-900/30 p-3 rounded-lg mb-4 flex justify-between items-center">
                            <span class="font-semibold text-red-800 dark:text-red-200">Balance Due</span>
                            <span class="font-bold text-red-800 dark:text-red-200">{{ currency_format($checkout_balance_due, restaurant()->currency_id) }}</span>
                        </div>
                    </div>

                    {{-- Payment Form --}}
                    <form wire:submit.prevent="processCheckout">
                        <div class="space-y-4">
                            <div>
                                <x-label for="checkout_amount_paid" value="Payment Amount" />
                                <div class="relative mt-1">
                                        <span class="text-gray-500 sm:text-sm">{{ restaurant()->currency->symbol ?? '' }}</span>
                                    </div>
                                    <x-input id="checkout_amount_paid" type="number" step="0.01" class="block w-full pl-7" wire:model="checkout_amount_paid" required />
                                </div>
                                <x-input-error for="checkout_amount_paid" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="checkout_payment_method" value="Payment Method" />
                                <select id="checkout_payment_method" wire:model="checkout_payment_method" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="other">Other</option>
                                </select>
                                <x-input-error for="checkout_payment_method" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="checkout_notes" value="Notes" />
                                <textarea id="checkout_notes" wire:model="checkout_notes" rows="2" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                            </div>
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
</div>
