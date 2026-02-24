<div>
    {{-- Header --}}
    <div class="p-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="flex flex-row items-center justify-between gap-4 mb-4">
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Room Types</h1>
            @if(user_can('create_room_type'))
            <x-button type='button' wire:click="createRoomType">
                Add Room Type
            </x-button>
            @endif
        </div>

        {{-- Search & Filters --}}
        <div class="flex items-center gap-4">
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Search room types..." class="pl-9 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>

    {{-- Room Types Grid --}}
    <div class="p-4 bg-gray-50 dark:bg-gray-900 min-h-[calc(100vh-200px)]">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @forelse($roomTypes as $roomType)
                <div class="group relative bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 overflow-visible">
                    {{-- Active Status Indicator --}}
                    @if(!$roomType->is_active)
                        <div class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 z-10 rounded-xl flex items-center justify-center backdrop-blur-[1px]">
                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-semibold rounded-full shadow-sm">Inactive</span>
                        </div>
                    @endif

                    <div class="p-5">
                        {{-- Header: Name & Price --}}
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white leading-tight">{{ $roomType->name }}</h3>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ currency_format($roomType->base_price, restaurant()->currency_id) }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">/ night</span>
                                </div>
                            </div>
                            
                            {{-- Actions Dropdown --}}
                            <div class="relative z-20" x-data="{ open: false }">
                                <button @click.prevent="open = !open" class="p-1 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="4" r="1.5"/><circle cx="10" cy="10" r="1.5"/><circle cx="10" cy="16" r="1.5"/></svg>
                                </button>
                                <div x-show="open" x-cloak @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-1 w-40 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 py-1 origin-top-right">
                                    @if(user_can('edit_room_type'))
                                    <button @click="open = false" wire:click="editRoomType({{ $roomType->id }})" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </button>
                                    @endif
                                    @if(user_can('delete_room_type'))
                                    @if($roomType->rooms->count() === 0)
                                    <button @click="open = false" wire:click="confirmDeleteRoomType({{ $roomType->id }})" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Delete
                                    </button>
                                    @else
                                    <div class="px-3 py-2 text-xs text-gray-400 dark:text-gray-500 italic">
                                        Cannot delete (in use)
                                    </div>
                                    @endif
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Description --}}
                        @if($roomType->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2 h-10">{{ $roomType->description }}</p>
                        @else
                            <div class="h-10 mb-4"></div>
                        @endif

                        {{-- Stats Grid --}}
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2.5 flex items-center gap-3 border border-gray-100 dark:border-gray-700">
                                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 font-semibold truncate">Occupancy</div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $roomType->max_occupancy }} <span class="text-xs font-normal text-gray-500">Person{{ $roomType->max_occupancy != 1 ? 's' : '' }}</span></div>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2.5 flex items-center gap-3 border border-gray-100 dark:border-gray-700">
                                <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 font-semibold truncate">Total Rooms</div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $roomType->rooms->count() }} <span class="text-xs font-normal text-gray-500">Unit{{ $roomType->rooms->count() != 1 ? 's' : '' }}</span></div>
                                </div>
                            </div>
                            {{-- Extra Bed Charge --}}
                            @if(($roomType->extra_bed_charge ?? 0) > 0)
                            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-2.5 flex items-center gap-3 border border-amber-100 dark:border-amber-700/40">
                                <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="text-[10px] uppercase tracking-wider text-amber-600 dark:text-amber-400 font-semibold truncate">Extra Bed</div>
                                    <div class="text-sm font-bold text-amber-700 dark:text-amber-300">{{ currency_format($roomType->extra_bed_charge, restaurant()->currency_id) }}<span class="text-[10px] font-normal text-amber-500 ml-0.5">/night</span></div>
                                </div>
                            </div>
                            @endif
                            {{-- Extra Person Charge --}}
                            @if(($roomType->extra_person_charge ?? 0) > 0)
                            <div class="bg-rose-50 dark:bg-rose-900/20 rounded-lg p-2.5 flex items-center gap-3 border border-rose-100 dark:border-rose-700/40">
                                <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="text-[10px] uppercase tracking-wider text-rose-600 dark:text-rose-400 font-semibold truncate">Extra Person</div>
                                    <div class="text-sm font-bold text-rose-700 dark:text-rose-300">{{ currency_format($roomType->extra_person_charge, restaurant()->currency_id) }}<span class="text-[10px] font-normal text-rose-500 ml-0.5">/night</span></div>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- Amenities --}}
                        @if($roomType->amenities && count($roomType->amenities) > 0)
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($roomType->amenities as $amenity)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $amenity }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700 min-h-[46px] flex items-center">
                                <span class="text-xs text-gray-400 italic">No specific amenities listed</span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-20 h-20 mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">No Room Types Found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                        Get started by creating your first room type to define pricing and occupancy rules.
                    </p>
                    <div class="mt-6">
                        <x-button type='button' wire:click="$set('showAddRoomType', true)">
                            Create Room Type
                        </x-button>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Add Room Type Modal --}}
    <x-right-modal wire:model.live="showAddRoomType">
        <x-slot name="title">Add Room Type</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveRoomType">
                <div class="space-y-5">
                    {{-- Name --}}
                    <div>
                        <x-label for="name" value="Name" />
                        <x-input id="name" type="text" class="block w-full mt-1" wire:model="name" placeholder="e.g. Deluxe Suite" required />
                        <x-input-error for="name" class="mt-1" />
                    </div>

                    {{-- Price & Occupancy --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="base_price" value="Base Price" />
                            <div class="relative mt-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500">{{ restaurant()->currency->currency_symbol }}</span>
                                </div>
                                <x-input id="base_price" type="number" step="0.01" min="0" class="block w-full pl-10" wire:model="base_price" placeholder="0.00" required />
                            </div>
                            <x-input-error for="base_price" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="max_occupancy" value="Max Occupancy" />
                            <x-input id="max_occupancy" type="number" min="1" class="block w-full mt-1" wire:model="max_occupancy" required />
                            <x-input-error for="max_occupancy" class="mt-1" />
                        </div>
                    </div>

                    {{-- Extra Charges --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="extra_bed_charge" value="Extra Bed Charge" />
                            <div class="relative mt-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500">{{ restaurant()->currency->currency_symbol }}</span>
                                </div>
                                <x-input id="extra_bed_charge" type="number" step="0.01" min="0" class="block w-full pl-10" wire:model="extra_bed_charge" placeholder="0.00" />
                            </div>
                            <x-input-error for="extra_bed_charge" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="extra_person_charge" value="Extra Person Charge" />
                            <div class="relative mt-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500">{{ restaurant()->currency->currency_symbol }}</span>
                                </div>
                                <x-input id="extra_person_charge" type="number" step="0.01" min="0" class="block w-full pl-10" wire:model="extra_person_charge" placeholder="0.00" />
                            </div>
                            <x-input-error for="extra_person_charge" class="mt-2" />
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <x-label for="description" value="Description" />
                        <textarea id="description" wire:model="description" rows="3" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" placeholder="Describe the room features and view..."></textarea>
                        <x-input-error for="description" class="mt-1" />
                    </div>

                    {{-- Amenities --}}
                    <div>
                        <x-label for="amenities" value="Amenities" />
                        <x-input id="amenities" type="text" class="block w-full mt-1" wire:model="amenitiesInput" placeholder="WiFi, A/C, Balcony, Minibar..." />
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Separate multiple amenities with commas.</p>
                        <x-input-error for="amenitiesInput" class="mt-1" />
                    </div>

                    {{-- Status --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center">
                            <input id="is_active" type="checkbox" wire:model="is_active" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="is_active" class="ml-2 block text-sm font-medium text-gray-900 dark:text-gray-300">
                                Active Status
                            </label>
                        </div>
                        <p class="ml-6 mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Inactive room types don't appear in new reservation selections.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showAddRoomType', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-700 text-white">
                        Create Room Type
                    </x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>

    {{-- Edit Room Type Modal --}}
    <x-right-modal wire:model.live="showEditRoomType">
        <x-slot name="title">Edit Room Type</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveRoomType">
                <div class="space-y-5">
                    {{-- Name --}}
                    <div>
                        <x-label for="edit_name" value="Name" />
                        <x-input id="edit_name" type="text" class="block w-full mt-1" wire:model="name" required />
                        <x-input-error for="name" class="mt-1" />
                    </div>

                    {{-- Price & Occupancy --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="edit_base_price" value="Base Price" />
                            <div class="relative mt-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500">{{ restaurant()->currency->currency_symbol }}</span>
                                </div>
                                <x-input id="edit_base_price" type="number" step="0.01" min="0" class="block w-full pl-10" wire:model="base_price" required />
                            </div>
                            <x-input-error for="base_price" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="edit_max_occupancy" value="Max Occupancy" />
                            <x-input id="edit_max_occupancy" type="number" min="1" class="block w-full mt-1" wire:model="max_occupancy" required />
                            <x-input-error for="max_occupancy" class="mt-1" />
                        </div>
                    </div>

                    {{-- Extra Charges --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="edit_extra_bed_charge" value="Extra Bed Charge" />
                            <div class="relative mt-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500">{{ restaurant()->currency->currency_symbol }}</span>
                                </div>
                                <x-input id="edit_extra_bed_charge" type="number" step="0.01" min="0" class="block w-full pl-10" wire:model="extra_bed_charge" />
                            </div>
                            <x-input-error for="extra_bed_charge" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="edit_extra_person_charge" value="Extra Person Charge" />
                            <div class="relative mt-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500">{{ restaurant()->currency->currency_symbol }}</span>
                                </div>
                                <x-input id="edit_extra_person_charge" type="number" step="0.01" min="0" class="block w-full pl-10" wire:model="extra_person_charge" />
                            </div>
                            <x-input-error for="extra_person_charge" class="mt-2" />
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <x-label for="edit_description" value="Description" />
                        <textarea id="edit_description" wire:model="description" rows="3" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                        <x-input-error for="description" class="mt-1" />
                    </div>

                    {{-- Amenities --}}
                    <div>
                        <x-label for="edit_amenities" value="Amenities" />
                        <x-input id="edit_amenities" type="text" class="block w-full mt-1" wire:model="amenitiesInput" placeholder="WiFi, A/C, Balcony, Minibar..." />
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Separate multiple amenities with commas.</p>
                        <x-input-error for="amenitiesInput" class="mt-1" />
                    </div>

                    {{-- Status --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center">
                            <input id="edit_is_active" type="checkbox" wire:model="is_active" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="edit_is_active" class="ml-2 block text-sm font-medium text-gray-900 dark:text-gray-300">
                                Active Status
                            </label>
                        </div>
                        <p class="ml-6 mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Inactive room types don't appear in new reservation selections.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showEditRoomType', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-700 text-white">
                        Update Changes
                    </x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>
</div>
