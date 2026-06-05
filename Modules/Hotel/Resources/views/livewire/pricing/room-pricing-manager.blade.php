<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Room Pricing Management</h1>
                <p class="text-gray-600 mt-2">Manage dynamic pricing for room types</p>
            </div>
            <div class="flex items-center gap-4">
                @if ($isDynamicPricingEnabled)
                    <span class="inline-flex items-center px-4 py-2 rounded-lg bg-green-100 text-green-800">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Dynamic Pricing Enabled
                    </span>
                @else
                    <span class="inline-flex items-center px-4 py-2 rounded-lg bg-yellow-100 text-yellow-800">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        Dynamic Pricing Disabled
                    </span>
                @endif
            </div>
        </div>

        <!-- Room Type Selection -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold text-gray-800">Select Room Type</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    @forelse ($roomTypes as $roomType)
                        <button
                            wire:click="selectRoomType({{ $roomType->id }})"
                            class="px-4 py-3 rounded-lg border-2 transition {{ $selectedRoomTypeId === $roomType->id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300' }}"
                        >
                            <div class="font-semibold text-sm text-gray-900">{{ $roomType->name }}</div>
                            <div class="text-xs text-gray-500">{{ currency_format($roomType->base_price) }}/night</div>
                        </button>
                    @empty
                        <div class="col-span-full text-center py-8">
                            <p class="text-gray-500">No active room types found</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Pricing Table & Actions -->
        @if ($selectedRoomTypeId)
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">Price Overrides</h2>
                    <button
                        wire:click="openPricingModal"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                        {{ !$isDynamicPricingEnabled ? 'disabled' : '' }}
                    >
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Price Override
                    </button>
                </div>

                <div class="overflow-x-auto">
                    @if ($prices->count() > 0)
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b">
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Date From</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Date To</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Price</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Reason</th>
                                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($prices as $pricing)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ $pricing->date_from->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ $pricing->date_to->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                            {{ currency_format($pricing->price) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $pricing->reason ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button
                                                wire:click="editPricing({{ $pricing->id }})"
                                                class="text-blue-600 hover:text-blue-800 font-semibold text-sm mr-3"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                wire:click="confirmDeletePricing({{ $pricing->id }})"
                                                class="text-red-600 hover:text-red-800 font-semibold text-sm"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="p-8 text-center">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-gray-500">No price overrides set for this room type</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Add/Edit Pricing Modal -->
    @if ($showPricingModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ $editingPricingId ? 'Edit Price Override' : 'Add Price Override' }}
                    </h3>
                    <button wire:click="$set('showPricingModal', false)" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="savePricing" class="p-6 space-y-4">
                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                        <input
                            type="date"
                            wire:model="dateFrom"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        >
                        @error ('dateFrom')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                        <input
                            type="date"
                            wire:model="dateTo"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        >
                        @error ('dateTo')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Price -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                        <input
                            type="number"
                            step="0.01"
                            wire:model="price"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        >
                        @error ('price')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Reason (Optional) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                        <input
                            type="text"
                            wire:model="reason"
                            placeholder="e.g., Holiday season, Peak events"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-3 pt-4">
                        <button
                            type="button"
                            wire:click="$set('showPricingModal', false)"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        >
                            {{ $editingPricingId ? 'Update' : 'Add' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
