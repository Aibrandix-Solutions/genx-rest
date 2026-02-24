<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Guests</h1>
            </div>
            <div class="items-center justify-between block sm:flex">
                <div class="lg:flex items-center mb-4 sm:mb-0">
                    <div class="relative w-48 mt-1 sm:w-64 xl:w-96">
                        <x-input id="search" class="block mt-1 w-full" type="text" placeholder="Search guests by name, email, or phone..." wire:model.live.debounce.500ms="search" />
                    </div>
                </div>

                <div class="lg:inline-flex items-center gap-4">
                    @if(user_can('create_guest'))
                    <x-button type='button' wire:click="$set('showAddGuest', true)">Add Guest</x-button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Guests Table --}}
    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Name</th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Contact</th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">ID Type</th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Customer Link</th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Reservations</th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse($guests as $guest)
                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <td class="p-4 text-sm font-normal text-gray-900 whitespace-nowrap dark:text-white">
                                        <div class="font-semibold">{{ $guest->full_name }}</div>
                                        @if($guest->country)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $guest->country }}</div>
                                        @endif
                                    </td>
                                    <td class="p-4 text-sm font-normal text-gray-500 whitespace-nowrap dark:text-gray-400">
                                        <div>{{ $guest->email }}</div>
                                        <div class="text-xs">{{ $guest->phone }}</div>
                                    </td>
                                    <td class="p-4 text-sm font-normal text-gray-900 whitespace-nowrap dark:text-white">
                                        @if($guest->id_type)
                                            <div class="text-xs">{{ ucfirst($guest->id_type) }}</div>
                                            <div class="text-xs text-gray-500">{{ $guest->id_number }}</div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-sm font-normal whitespace-nowrap">
                                        @if($guest->customer)
                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded">
                                                Linked
                                            </span>
                                        @else
                                            <span class="text-gray-400">No</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-sm font-semibold text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ $guest->reservations->count() }}
                                    </td>
                                    <td class="p-4 space-x-2 whitespace-nowrap">
                                        @if(user_can('edit_guest'))
                                        <button wire:click="editGuest({{ $guest->id }})" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-blue-700 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                                            Edit
                                        </button>
                                        @endif
                                        @if(user_can('delete_guest'))
                                        <button wire:click="confirmDeleteGuest({{ $guest->id }})" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-red-600 rounded-lg hover:bg-red-800 dark:hover:bg-red-700">
                                            Delete
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-12 text-center">
                                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <p class="text-gray-500 dark:text-gray-400">No guests found</p>
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
        {{ $guests->links() }}
    </div>

    {{-- Add Guest Modal --}}
    <x-right-modal wire:model.live="showAddGuest">
        <x-slot name="title">Add Guest</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveGuest">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="first_name" value="First Name" />
                            <x-input id="first_name" type="text" class="block w-full mt-1" wire:model="first_name" required />
                            <x-input-error for="first_name" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="last_name" value="Last Name" />
                            <x-input id="last_name" type="text" class="block w-full mt-1" wire:model="last_name" required />
                            <x-input-error for="last_name" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="email" value="Email" />
                            <x-input id="email" type="email" class="block w-full mt-1" wire:model="email" />
                            <x-input-error for="email" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="phone" value="Phone" />
                            <x-input id="phone" type="text" class="block w-full mt-1" wire:model="phone" />
                            <x-input-error for="phone" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="id_type" value="ID Type" />
                            <select id="id_type" wire:model="id_type" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                <option value="">Select Type</option>
                                <option value="national_id">National ID</option>
                                <option value="passport">Passport</option>
                                <option value="driving_license">Driving License</option>
                                <option value="other">Other</option>
                            </select>
                            <x-input-error for="id_type" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="id_number" value="ID Number" />
                            <x-input id="id_number" type="text" class="block w-full mt-1" wire:model="id_number" />
                            <x-input-error for="id_number" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-label for="address" value="Address" />
                        <x-input id="address" type="text" class="block w-full mt-1" wire:model="address" />
                        <x-input-error for="address" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="city" value="City" />
                            <x-input id="city" type="text" class="block w-full mt-1" wire:model="city" />
                            <x-input-error for="city" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="country" value="Country" />
                            <x-input id="country" type="text" class="block w-full mt-1" wire:model="country" />
                            <x-input-error for="country" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-label for="notes" value="Notes" />
                        <textarea id="notes" wire:model="notes" rows="3" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                        <x-input-error for="notes" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showAddGuest', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled">
                        Save
                    </x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>

    {{-- Edit Guest Modal --}}
    <x-right-modal wire:model.live="showEditGuest">
        <x-slot name="title">Edit Guest</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveGuest">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="edit_first_name" value="First Name" />
                            <x-input id="edit_first_name" type="text" class="block w-full mt-1" wire:model="first_name" required />
                            <x-input-error for="first_name" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="edit_last_name" value="Last Name" />
                            <x-input id="edit_last_name" type="text" class="block w-full mt-1" wire:model="last_name" required />
                            <x-input-error for="last_name" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="edit_email" value="Email" />
                            <x-input id="edit_email" type="email" class="block w-full mt-1" wire:model="email" />
                            <x-input-error for="email" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="edit_phone" value="Phone" />
                            <x-input id="edit_phone" type="text" class="block w-full mt-1" wire:model="phone" />
                            <x-input-error for="phone" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="edit_id_type" value="ID Type" />
                            <select id="edit_id_type" wire:model="id_type" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                <option value="">Select Type</option>
                                <option value="national_id">National ID</option>
                                <option value="passport">Passport</option>
                                <option value="driving_license">Driving License</option>
                                <option value="other">Other</option>
                            </select>
                            <x-input-error for="id_type" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="edit_id_number" value="ID Number" />
                            <x-input id="edit_id_number" type="text" class="block w-full mt-1" wire:model="id_number" />
                            <x-input-error for="id_number" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-label for="edit_address" value="Address" />
                        <x-input id="edit_address" type="text" class="block w-full mt-1" wire:model="address" />
                        <x-input-error for="address" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="edit_city" value="City" />
                            <x-input id="edit_city" type="text" class="block w-full mt-1" wire:model="city" />
                            <x-input-error for="city" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="edit_country" value="Country" />
                            <x-input id="edit_country" type="text" class="block w-full mt-1" wire:model="country" />
                            <x-input-error for="country" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-label for="edit_notes" value="Notes" />
                        <textarea id="edit_notes" wire:model="notes" rows="3" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                        <x-input-error for="notes" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showEditGuest', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
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
