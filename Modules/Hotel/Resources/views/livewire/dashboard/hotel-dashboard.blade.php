<div class="p-6 bg-gray-50 dark:bg-gray-900" wire:poll.30s>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Hotel Dashboard</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Revenue and expense first, then rooms and guest activity for <span class="font-medium text-gray-700 dark:text-gray-300">{{ $periodDescription }}</span>.
                Metrics refresh every 30 seconds.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if(user_can('create_reservation'))
                <a href="{{ route('hotel.reservations') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    New Reservation
                </a>
            @endif
            @if(user_can('view_hotel_reservations'))
                <a href="{{ route('hotel.reservations') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    All Reservations
                </a>
            @endif
            @if(user_can('view_hotel_housekeeping'))
                <a href="{{ route('hotel.housekeeping') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    Housekeeping
                </a>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-8 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Reporting period</label>
                <select wire:model.live="selectedPeriod" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Controls revenue, expenses, occupancy, and activity counts below.</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Activity list focus</label>
                <select wire:model.live="activityFilter" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <option value="all">Show all sections</option>
                    <option value="arrivals">Arrivals only</option>
                    <option value="departures">Departures only</option>
                    <option value="in_house">In-house guests only</option>
                    <option value="housekeeping">Housekeeping only</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hide sections you don't need right now.</p>
            </div>

            <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-900/40">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Billing policy</p>
                @if($hotelSettings)
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                        Tax {{ number_format((float) $hotelSettings->tax_rate, 2) }}%
                        @if((float) $hotelSettings->service_charge_rate > 0)
                            · Service {{ number_format((float) $hotelSettings->service_charge_rate, 2) }}%
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Check-in {{ substr($hotelSettings->default_check_in_time ?? '14:00', 0, 5) }}
                        · Checkout {{ substr($hotelSettings->default_checkout_time ?? '12:00', 0, 5) }}
                    </p>
                @else
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure rates in Hotel Settings.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Revenue & expense (priority) --}}
    <div class="mb-2 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Revenue &amp; expense</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $periodLabel }} · Folio charges vs hotel expenses</p>
    </div>
    <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="overflow-hidden rounded-lg border border-emerald-200 bg-white shadow-sm dark:border-emerald-800 dark:bg-gray-800">
            <div class="p-5">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Total revenue</h4>
                <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ currency_format($revenueMetrics['total_revenue']) }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Room {{ currency_format($revenueMetrics['room_revenue']) }}
                    @if($revenueMetrics['other_revenue'] > 0)
                        · Other {{ currency_format($revenueMetrics['other_revenue']) }}
                    @endif
                </p>
            </div>
            <div class="h-1.5 w-full bg-emerald-500"></div>
        </div>

        @if(user_can('view_hotel_expenses'))
        <div class="overflow-hidden rounded-lg border border-rose-200 bg-white shadow-sm dark:border-rose-800 dark:bg-gray-800">
            <div class="p-5">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">Expenses</h4>
                    <a href="{{ route('hotel.expenses') }}" class="text-xs font-medium text-rose-600 hover:underline dark:text-rose-300">View</a>
                </div>
                <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ currency_format($revenueMetrics['expenses']) }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Paid {{ currency_format($revenueMetrics['expenses_paid']) }}
                    @if(($revenueMetrics['expenses_outstanding'] ?? $revenueMetrics['expenses_pending']) > 0)
                        · Still owed {{ currency_format($revenueMetrics['expenses_outstanding'] ?? $revenueMetrics['expenses_pending']) }}
                    @endif
                </p>
            </div>
            <div class="h-1.5 w-full bg-rose-500"></div>
        </div>

        <div class="overflow-hidden rounded-lg border {{ $revenueMetrics['net'] >= 0 ? 'border-indigo-200 dark:border-indigo-800' : 'border-orange-200 dark:border-orange-800' }} bg-white shadow-sm dark:bg-gray-800">
            <div class="p-5">
                <h4 class="text-xs font-semibold uppercase tracking-wide {{ $revenueMetrics['net'] >= 0 ? 'text-indigo-700 dark:text-indigo-300' : 'text-orange-700 dark:text-orange-300' }}">Net</h4>
                <div class="mt-1 text-3xl font-bold {{ $revenueMetrics['net'] >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-orange-700 dark:text-orange-300' }}">{{ currency_format($revenueMetrics['net']) }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Revenue minus billed expenses</p>
            </div>
            <div class="h-1.5 w-full {{ $revenueMetrics['net'] >= 0 ? 'bg-indigo-500' : 'bg-orange-500' }}"></div>
        </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-amber-200 bg-white shadow-sm dark:border-amber-800 dark:bg-gray-800">
            <div class="p-5">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">Outstanding balance</h4>
                <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ currency_format($reservationStats['outstanding_balance']) }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Unpaid folio total (confirmed & in-house)</p>
            </div>
            <div class="h-1.5 w-full bg-amber-500"></div>
        </div>
    </div>

    <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
            <div class="p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">ADR</h4>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ currency_format($revenueMetrics['adr']) }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Room revenue ÷ room-nights sold</p>
            </div>
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
            <div class="p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">RevPAR</h4>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ currency_format($revenueMetrics['rev_par']) }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Room revenue ÷ (rooms × days)</p>
            </div>
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
            <div class="p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Room-nights sold</h4>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $revenueMetrics['rooms_sold'] }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $periodLabel }} · billed nights</p>
            </div>
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
            <div class="p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Occupancy</h4>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $occupancyRate }}%</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $periodLabel }} · stay nights vs inventory</p>
            </div>
        </div>
    </div>

    {{-- Today's pulse --}}
    <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
            <p class="text-xs font-medium uppercase text-blue-700 dark:text-blue-300">Arriving today</p>
            <p class="mt-1 text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $reservationStats['arriving_today'] }}</p>
            <p class="text-xs text-blue-600 dark:text-blue-400">Confirmed, not yet checked in</p>
        </div>
        <div class="rounded-lg border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
            <p class="text-xs font-medium uppercase text-purple-700 dark:text-purple-300">Departing today</p>
            <p class="mt-1 text-2xl font-bold text-purple-900 dark:text-purple-100">{{ $reservationStats['departing_today'] }}</p>
            <p class="text-xs text-purple-600 dark:text-purple-400">Checked-in guests due out</p>
        </div>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
            <p class="text-xs font-medium uppercase text-green-700 dark:text-green-300">In-house now</p>
            <p class="mt-1 text-2xl font-bold text-green-900 dark:text-green-100">{{ $reservationStats['total_checked_in'] }}</p>
            <p class="text-xs text-green-600 dark:text-green-400">Currently checked in</p>
        </div>
        <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900/20">
            <p class="text-xs font-medium uppercase text-indigo-700 dark:text-indigo-300">Reserved now</p>
            <p class="mt-1 text-2xl font-bold text-indigo-900 dark:text-indigo-100">{{ $roomStats['reserved'] }}</p>
            <p class="text-xs text-indigo-600 dark:text-indigo-400">Booked, awaiting check-in</p>
        </div>
    </div>

    {{-- Room Statistics --}}
    <div class="mb-2 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Room inventory</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">Physical room status right now</p>
    </div>
    <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
            <div class="p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Rooms</h4>
                <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $roomStats['total'] }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">All rooms in property</p>
            </div>
            <div class="h-1 w-full bg-blue-500"></div>
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
            <div class="p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Available</h4>
                <div class="mt-1 text-3xl font-bold text-green-600 dark:text-green-400">{{ $roomStats['available'] }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ready to sell</p>
            </div>
            <div class="h-1 w-full bg-green-500"></div>
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
            <div class="p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Occupied</h4>
                <div class="mt-1 text-3xl font-bold text-red-600 dark:text-red-400">{{ $roomStats['occupied'] }}</div>
                <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $occupancyRate }}% occupancy ({{ $periodLabel }})</p>
            </div>
            <div class="h-1 w-full bg-red-500"></div>
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
            <div class="p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Reserved</h4>
                <div class="mt-1 text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ $roomStats['reserved'] }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Booked, awaiting check-in</p>
            </div>
            <div class="h-1 w-full bg-indigo-500"></div>
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
            <div class="p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Cleaning</h4>
                <div class="mt-1 text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $roomStats['cleaning'] }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Being prepared</p>
            </div>
            <div class="h-1 w-full bg-yellow-500"></div>
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
            <div class="p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Maintenance</h4>
                <div class="mt-1 text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $roomStats['maintenance'] }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Out of service</p>
            </div>
            <div class="h-1 w-full bg-orange-500"></div>
        </div>
    </div>

    {{-- Reservation flow stats --}}
    <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="rounded-lg border-l-4 border-blue-500 bg-white p-6 shadow-sm dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">Check-ins ({{ $periodLabel }})</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $reservationStats['check_ins_period'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Completed + still expected arrivals in period</p>
        </div>
        <div class="rounded-lg border-l-4 border-purple-500 bg-white p-6 shadow-sm dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">Check-outs ({{ $periodLabel }})</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $reservationStats['checkouts_period'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Completed + still expected departures in period</p>
        </div>
        <div class="rounded-lg border-l-4 border-gray-400 bg-white p-6 shadow-sm dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">Pipeline</p>
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">
                <span class="font-semibold">{{ $reservationStats['total_confirmed'] }}</span> confirmed ·
                <span class="font-semibold">{{ $reservationStats['arriving_next'] }}</span> arriving tomorrow
                @if($reservationStats['no_shows_period'] > 0)
                    · <span class="font-semibold text-red-600">{{ $reservationStats['no_shows_period'] }}</span> no-shows
                @endif
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Use Reservations page to check in arriving guests</p>
        </div>
    </div>

    @if(user_can('view_hotel_reservations') && in_array($activityFilter, ['all', 'arrivals', 'departures'], true))
        <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            @if(in_array($activityFilter, ['all', 'arrivals'], true))
                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Arrivals ({{ $periodLabel }})</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Confirmed guests expected to check in</p>
                        </div>
                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ count($todaysArrivals) }}</span>
                    </div>
                    <div class="space-y-3">
                        @forelse($todaysArrivals as $arrival)
                            <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $arrival->guest->full_name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Room {{ $arrival->room->room_number ?? 'TBA' }} · {{ $arrival->room->roomType->name ?? '' }}
                                        · {{ $arrival->getNumberOfNights() }} {{ Str::plural('night', $arrival->getNumberOfNights()) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ substr((string) $arrival->check_in_time, 0, 5) }}</span>
                                    <p class="text-xs text-gray-500">{{ $arrival->check_in_date->format('M d') }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="py-4 text-center text-gray-500 dark:text-gray-400">No arrivals for this period</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if(in_array($activityFilter, ['all', 'departures'], true))
                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Departures ({{ $periodLabel }})</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">In-house guests scheduled to check out</p>
                        </div>
                        <span class="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">{{ count($todaysDepartures) }}</span>
                    </div>
                    <div class="space-y-3">
                        @forelse($todaysDepartures as $departure)
                            <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $departure->guest->full_name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Room {{ $departure->room->room_number ?? 'TBA' }} · Balance {{ currency_format($departure->balance_due, restaurant()->currency_id) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-medium text-purple-600 dark:text-purple-400">{{ substr((string) $departure->checkout_time, 0, 5) }}</span>
                                    <p class="text-xs text-gray-500">{{ $departure->checkout_date->format('M d') }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="py-4 text-center text-gray-500 dark:text-gray-400">No departures for this period</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if(user_can('view_hotel_reservations') && in_array($activityFilter, ['all', 'in_house'], true))
        <div class="mb-8 rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">In-House Guests</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Guests currently checked in with open folios</p>
                </div>
                <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">{{ count($inHouseGuests) }}</span>
            </div>
            <div class="space-y-3">
                @forelse($inHouseGuests as $guest)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                        <div class="flex items-center space-x-4">
                            <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $guest->guest->full_name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Room {{ $guest->room->room_number ?? 'TBA' }} · Due out {{ $guest->checkout_date->format('M d') }}
                                    · {{ $guest->adults + $guest->children }} guest(s)
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="block text-sm font-bold {{ $guest->balance_due > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ currency_format($guest->balance_due, restaurant()->currency_id) }}
                            </span>
                            <span class="text-xs text-gray-500">{{ $guest->balance_due > 0 ? 'Balance due' : 'Settled' }}</span>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-gray-500 dark:text-gray-400">No guests currently checked in.</p>
                @endforelse
            </div>
        </div>
    @endif

    @if(user_can('view_hotel_housekeeping') && in_array($activityFilter, ['all', 'housekeeping'], true))
        <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Pending Housekeeping</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Rooms waiting to be cleaned or inspected</p>
                </div>
                <span class="rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                    {{ count($pendingHousekeeping) }} Tasks
                </span>
            </div>
            <div class="space-y-3">
                @forelse($pendingHousekeeping as $task)
                    <div class="flex items-center justify-between rounded-lg border-l-4 p-3 @if($task->priority == 'urgent') border-red-500 bg-red-50 dark:bg-red-900/20 @else border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20 @endif">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">Room {{ $task->room->room_number }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($task->task_type) }}</p>
                        </div>
                        <span class="rounded px-2 py-1 text-xs font-medium @if($task->priority == 'urgent') bg-red-100 text-red-800 @else bg-yellow-100 text-yellow-800 @endif">
                            {{ ucfirst($task->priority) }}
                        </span>
                    </div>
                @empty
                    <p class="py-4 text-center text-gray-500 dark:text-gray-400">No pending tasks — all rooms are ready.</p>
                @endforelse
            </div>
        </div>
    @endif

    @if($restaurantStats)
        <div class="mt-8">
            <div class="mb-4 flex items-center gap-2">
                <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                <span class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    Restaurant ({{ $periodLabel }})
                </span>
                <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-lg border-l-4 border-indigo-500 bg-white p-4 shadow-sm dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Orders</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $restaurantStats['orders'] }}</p>
                </div>
                <div class="rounded-lg border-l-4 border-emerald-500 bg-white p-4 shadow-sm dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Earnings</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ currency_format($restaurantStats['earnings']) }}</p>
                </div>
                <div class="rounded-lg border-l-4 border-amber-500 bg-white p-4 shadow-sm dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Customers</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $restaurantStats['customers'] }}</p>
                </div>
            </div>

            <div class="mt-3 text-center">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm text-indigo-600 transition hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                    View Full Restaurant Dashboard
                </a>
            </div>
        </div>
    @endif
</div>
