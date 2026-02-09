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

    {{-- Revenue Metrics (This Month) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="p-1.5 bg-emerald-50 dark:bg-emerald-900/20 rounded">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Room Revenue</h4>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ currency_format($revenueMetrics['room_revenue']) }}</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $periodLabel }}</p>
            </div>
            <div class="h-1 w-full bg-emerald-500"></div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="p-1.5 bg-indigo-50 dark:bg-indigo-900/20 rounded">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">ADR</h4>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ currency_format($revenueMetrics['adr']) }}</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Avg Daily Rate</p>
            </div>
            <div class="h-1 w-full bg-indigo-500"></div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="p-1.5 bg-violet-50 dark:bg-violet-900/20 rounded">
                        <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">RevPAR</h4>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ currency_format($revenueMetrics['rev_par']) }}</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Rev Per Available Room</p>
            </div>
            <div class="h-1 w-full bg-violet-500"></div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="p-1.5 bg-amber-50 dark:bg-amber-900/20 rounded">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Rooms Sold</h4>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $revenueMetrics['rooms_sold'] }}</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $periodLabel }}</p>
            </div>
            <div class="h-1 w-full bg-amber-500"></div>
        </div>
    </div>

    {{-- Reservation Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Check-ins ({{ $periodLabel }})</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $reservationStats['check_ins_period'] }}</p>
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
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Check-outs ({{ $periodLabel }})</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $reservationStats['checkouts_period'] }}</p>
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
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Arrivals ({{ $periodLabel }})</h3>
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
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4">No arrivals for this period</p>
                @endforelse
            </div>
        </div>

        {{-- Today's Departures --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Departures ({{ $periodLabel }})</h3>
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
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4">No departures for this period</p>
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

    {{-- ═══ Restaurant Quick Stats (hotel_primary & equal modes) ═══ --}}
    @if($restaurantStats)
        <div class="mt-8">
            <div class="flex items-center gap-2 mb-4">
                <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75l-1.5.75a3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0L3 16.5m15-3.379a48.474 48.474 0 00-6-.371c-2.032 0-4.034.126-6 .371m12 0c.39.049.777.102 1.163.16 1.07.16 1.837 1.094 1.837 2.175v5.169c0 .621-.504 1.125-1.125 1.125H4.125A1.125 1.125 0 013 20.625v-5.17c0-1.08.768-2.014 1.837-2.174A47.78 47.78 0 016 13.12M16.5 3.75V16.5"/>
                    </svg>
                    Restaurant ({{ $periodLabel }})
                </span>
                <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-indigo-500">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Orders</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $restaurantStats['orders'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-emerald-500">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Earnings</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ currency_format($restaurantStats['earnings']) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-amber-500">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Customers</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $restaurantStats['customers'] }}</p>
                </div>
            </div>

            <div class="mt-3 text-center">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition">
                    View Full Restaurant Dashboard
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
            </div>
        </div>
    @endif
</div>
