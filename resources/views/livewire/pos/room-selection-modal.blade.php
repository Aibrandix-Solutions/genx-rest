<x-dialog-modal wire:model.live="showRoomSelectionModal" maxWidth="md">
    <x-slot name="title">
        @lang('modules.order.selectRoom')
    </x-slot>

    <x-slot name="content">
        <div class="space-y-4">
            <x-label value="{{ __('modules.order.selectRoom') }}" />
            
            <div class="grid grid-cols-1 gap-2 max-h-60 overflow-y-auto">
                @forelse($roomServiceReservations as $reservation)
                    <button type="button" 
                        wire:click="selectRoomReservation({{ $reservation->id }})"
                        class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 w-full text-left">
                        <div>
                            <div class="font-bold text-gray-800 dark:text-gray-200">
                                Room {{ $reservation->room?->room_number ?? '--' }} 
                                <span class="text-xs font-normal text-gray-500">({{ $reservation->room?->roomType?->name ?? '--' }})</span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Guest: {{ $reservation->guest?->full_name ?? $reservation->guest?->name ?? 'Guest' }}
                            </div>
                        </div>
                        <div>
                             <span class="text-green-600 text-xs font-medium bg-green-100 px-2 py-1 rounded-full">
                                Checked In
                             </span>
                        </div>
                    </button>
                @empty
                    <div class="text-center text-gray-500 p-4">
                        No checked-in rooms found.
                    </div>
                @endforelse
            </div>
            
            @if(collect($roomServiceReservations)->isEmpty())
            <div class="mt-4 text-center">
                <a href="{{ route('hotel.reservations') }}" class="text-blue-600 hover:underline">Create a Reservation</a>
            </div>
            @endif
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-button-cancel wire:click="closeRoomSelectionModal" wire:loading.attr="disabled" />
    </x-slot>
</x-dialog-modal>
