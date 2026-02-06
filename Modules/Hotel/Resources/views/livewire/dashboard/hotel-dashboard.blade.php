<div class="p-6 bg-gray-50 dark:bg-gray-900">
    {{-- Header with Filters --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">Hotel Management Dashboard</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
            {{-- Branch Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Branch</label>
                <select wire:model.live="selectedBranch" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Period Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Period</label>
                <select wire:model.live="selectedPeriod" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Room Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Rooms</h4>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $roomStats['total'] }}</div>
            </div>
            <div class="h-1 w-full bg-blue-500"></div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Available</h4>
                </div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $roomStats['available'] }}</div>
            </div>
            <div class="h-1 w-full bg-green-500"></div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Occupied</h4>
                </div>
                <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $roomStats['occupied'] }}</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $occupancyRate }}% Occupancy</p>
            </div>
            <div class="h-1 w-full bg-red-500"></div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Cleaning</h4>
                </div>
                <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $roomStats['cleaning'] }}</div>
            </div>
            <div class="h-1 w-full bg-yellow-500"></div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Maintenance</h4>
                </div>
                <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $roomStats['maintenance'] }}</div>
            </div>
            <div class="h-1 w-full bg-orange-500"></div>
        </div>
    </div>

    {{-- Reservation Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Check-ins Today</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $reservationStats['check_ins_today'] }}</p>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-full">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Check-outs Today</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $reservationStats['checkouts_today'] }}</p>
                </div>
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-full">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Current Guests</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $reservationStats['total_checked_in'] }}</p>
                </div>
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-full">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Today's Arrivals --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Today's Arrivals</h3>
            <div class="space-y-3">
                @forelse($todaysArrivals as $arrival)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $arrival->guest->full_name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Room {{ $arrival->room->room_number ?? 'TBA' }} - {{ $arrival->room->roomType->name ?? '' }}</p>
                        </div>
                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ $arrival->check_in_time }}</span>
                    </div>
                @empty
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4">No arrivals today</p>
                @endforelse
            </div>
        </div>

        {{-- Today's Departures --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Today's Departures</h3>
            <div class="space-y-3">
                @forelse($todaysDepartures as $departure)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $departure->guest->full_name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Room {{ $departure->room->room_number }} - {{ $departure->room->roomType->name }}</p>
                        </div>
                        <span class="text-sm font-medium text-purple-600 dark:text-purple-400">{{ $departure->checkout_time }}</span>
                    </div>
                @empty
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4">No departures today</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- In-House Guests (Checked In) --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">In-House Guests</h3>
        <div class="space-y-3">
            @forelse($inHouseGuests as $guest)
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="p-2 bg-green-100 dark:bg-green-900 rounded-full">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $guest->guest->full_name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Room {{ $guest->room->room_number ?? 'TBA' }} 
                                <span class="mx-1">•</span> 
                                Due Out: {{ $guest->checkout_date->format('M d') }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                       <span class="block text-sm font-bold text-gray-900 dark:text-white">
                           {{ currency_format($guest->balance_due, restaurant()->currency_id) }}
                       </span>
                       <span class="text-xs text-red-500">Balance Due</span>
                    </div>
                </div>
            @empty
                 <p class="text-center text-gray-500 dark:text-gray-400 py-8">No guests currently checked in.</p>
            @endforelse
        </div>
    </div>

    {{-- Pending Housekeeping --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Pending Housekeeping Tasks</h3>
            <span class="px-3 py-1 text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full">
                {{ count($pendingHousekeeping) }} Tasks
            </span>
        </div>
        <div class="space-y-3">
            @forelse($pendingHousekeeping as $task)
                <div class="flex items-center justify-between p-3 border-l-4 @if($task->priority == 'urgent') border-red-500 bg-red-50 dark:bg-red-900/20 @else border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20 @endif rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Room {{ $task->room->room_number }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($task->task_type) }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded @if($task->priority == 'urgent') bg-red-100 text-red-800 @else bg-yellow-100 text-yellow-800 @endif">
                        {{ ucfirst($task->priority) }}
                    </span>
                </div>
            @empty
                <p class="text-center text-gray-500 dark:text-gray-400 py-4">No pending tasks</p>
            @endforelse
        </div>
    </div>
</div>
