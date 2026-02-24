<div>
    {{-- ══════════════════════════════════════════════════════════════════
         HEADER  — Title + Date range + contextual filters
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="p-4 bg-white border-b dark:bg-gray-800 dark:border-gray-700">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Hotel Reports</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} –
                    {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                </p>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                {{-- Date From --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">From</label>
                    <input type="date" wire:model.live="dateFrom"
                        class="w-40 text-sm rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                </div>
                {{-- Date To --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">To</label>
                    <input type="date" wire:model.live="dateTo"
                        class="w-40 text-sm rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                </div>

                {{-- Status filter (reservations only) --}}
                @if($activeTab === 'reservations')
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Status</label>
                    <select wire:model.live="statusFilter"
                        class="w-44 text-sm rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="checked_in">Checked In</option>
                        <option value="checked_out">Checked Out</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="no_show">No Show</option>
                    </select>
                </div>
                @endif

                {{-- Room Type filter (reservations + revenue) --}}
                @if(in_array($activeTab, ['reservations', 'revenue']))
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Room Type</label>
                    <select wire:model.live="roomTypeFilter"
                        class="w-44 text-sm rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">All Types</option>
                        @foreach($roomTypes as $rt)
                            <option value="{{ $rt['id'] }}">{{ $rt['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Export CSV --}}
                <button wire:click="exportCsv"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         TAB BAR
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 px-4">
        <nav class="flex gap-0 -mb-px overflow-x-auto" aria-label="Report Tabs">
            @foreach([
                ['key' => 'overview',      'label' => 'Overview',      'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6'],
                ['key' => 'reservations',  'label' => 'Reservations',  'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['key' => 'revenue',       'label' => 'Revenue',       'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['key' => 'housekeeping',  'label' => 'Housekeeping',  'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ] as $tab)
            <button
                wire:click="$set('activeTab', '{{ $tab['key'] }}')"
                class="group inline-flex items-center gap-2 py-3 px-4 text-sm font-medium border-b-2 whitespace-nowrap transition
                    {{ $activeTab === $tab['key']
                        ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}"/>
                </svg>
                {{ $tab['label'] }}
            </button>
            @endforeach
        </nav>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         TAB CONTENT
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen" wire:loading.class="opacity-50">

        {{-- ── OVERVIEW TAB ──────────────────────────────────────────────── --}}
        @if($activeTab === 'overview')

        {{-- Section: Room Status --}}
        <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">Room Status</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
            @php
            $roomCards = [
                ['label'=>'Total Rooms',   'key'=>'total_rooms',       'color'=>'text-gray-800 dark:text-white',              'bg'=>'bg-gray-100 dark:bg-gray-700',   'icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6'],
                ['label'=>'Occupied',      'key'=>'occupied_rooms',    'color'=>'text-emerald-700 dark:text-emerald-400',     'bg'=>'bg-emerald-50 dark:bg-emerald-900/30', 'icon'=>'M5 13l4 4L19 7'],
                ['label'=>'Reserved',      'key'=>'reserved_rooms',    'color'=>'text-blue-700 dark:text-blue-400',           'bg'=>'bg-blue-50 dark:bg-blue-900/30', 'icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['label'=>'Available',     'key'=>'available_rooms',   'color'=>'text-violet-700 dark:text-violet-400',       'bg'=>'bg-violet-50 dark:bg-violet-900/30','icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label'=>'Cleaning',      'key'=>'cleaning_rooms',    'color'=>'text-amber-700 dark:text-amber-400',         'bg'=>'bg-amber-50 dark:bg-amber-900/30','icon'=>'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
                ['label'=>'Maintenance',   'key'=>'maintenance_rooms', 'color'=>'text-rose-700 dark:text-rose-400',           'bg'=>'bg-rose-50 dark:bg-rose-900/30', 'icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
            ];
            @endphp
            @foreach($roomCards as $card)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 flex flex-col gap-3">
                <div class="p-2 rounded-lg {{ $card['bg'] }} w-fit">
                    <svg class="w-5 h-5 {{ $card['color'] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/>
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $card['label'] }}</div>
                    <div class="text-2xl font-bold mt-0.5 {{ $card['color'] }}">{{ $stats[$card['key']] ?? 0 }}</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Section: Occupancy -- special wide card --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 mb-8">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Occupancy Rate</span>
                <span class="text-xl font-bold {{ ($stats['occupancy_rate'] ?? 0) >= 75 ? 'text-emerald-600 dark:text-emerald-400' : (($stats['occupancy_rate'] ?? 0) >= 40 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                    {{ $stats['occupancy_rate'] ?? 0 }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div class="h-3 rounded-full transition-all duration-500
                    {{ ($stats['occupancy_rate'] ?? 0) >= 75 ? 'bg-emerald-500' : (($stats['occupancy_rate'] ?? 0) >= 40 ? 'bg-amber-500' : 'bg-rose-500') }}"
                    style="width: {{ min(100, $stats['occupancy_rate'] ?? 0) }}%"></div>
            </div>
        </div>

        {{-- Section: Reservations KPIs --}}
        <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">Reservations ({{ $dateFrom }} – {{ $dateTo }})</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4 mb-8">
            @php
            $resCards = [
                ['label'=>'Total',        'key'=>'reservations_in_range', 'sub'=>null,              'color'=>'text-gray-800 dark:text-white'],
                ['label'=>'Confirmed',    'key'=>'confirmed_reservations','sub'=>null,              'color'=>'text-blue-600 dark:text-blue-400'],
                ['label'=>'Checked In',   'key'=>'checked_in_count',      'sub'=>null,              'color'=>'text-emerald-600 dark:text-emerald-400'],
                ['label'=>'Checked Out',  'key'=>'checked_out_count',     'sub'=>null,              'color'=>'text-violet-600 dark:text-violet-400'],
                ['label'=>'Cancelled',    'key'=>'cancelled_reservations','sub'=>null,              'color'=>'text-rose-600 dark:text-rose-400'],
                ['label'=>'No Show',      'key'=>'no_show_count',         'sub'=>null,              'color'=>'text-orange-600 dark:text-orange-400'],
                ['label'=>'Check-ins Today',  'key'=>'check_ins_today',   'sub'=>null,              'color'=>'text-indigo-600 dark:text-indigo-400'],
                ['label'=>'Check-outs Today', 'key'=>'check_outs_today',  'sub'=>null,              'color'=>'text-indigo-600 dark:text-indigo-400'],
            ];
            @endphp
            @foreach($resCards as $card)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $card['label'] }}</div>
                <div class="text-2xl font-bold mt-1 {{ $card['color'] }}">{{ $stats[$card['key']] ?? 0 }}</div>
            </div>
            @endforeach
        </div>

        {{-- Section: Revenue KPIs --}}
        <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">Revenue</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Revenue</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ currency_format($stats['total_revenue'] ?? 0, restaurant()->currency_id) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Refunds</div>
                <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ currency_format($stats['total_refunds'] ?? 0, restaurant()->currency_id) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Net Revenue</div>
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ currency_format($stats['net_revenue'] ?? 0, restaurant()->currency_id) }}</div>
            </div>
        </div>

        {{-- Section: Avg Length of Stay --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 inline-flex items-center gap-4">
            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Avg Length of Stay</div>
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $stats['avg_length_of_stay'] ?? 0 }} <span class="text-base font-normal text-gray-500">nights</span></div>
            </div>
        </div>

        @endif

        {{-- ── RESERVATIONS TAB ──────────────────────────────────────────── --}}
        @if($activeTab === 'reservations')
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800 dark:text-white">Reservations Report</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($reservationsReport) }} records</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            @foreach(['Reservation #', 'Guest', 'Room', 'Type', 'Check-in', 'Check-out', 'Nights', 'Revenue', 'Status'] as $col)
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($reservationsReport as $r)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3 font-mono text-xs text-indigo-600 dark:text-indigo-400 whitespace-nowrap">{{ $r['reservation_number'] }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white whitespace-nowrap">{{ $r['guest_name'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $r['room_number'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $r['room_type'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $r['check_in_date'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $r['checkout_date'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-center">{{ $r['nights'] }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white font-medium whitespace-nowrap">{{ currency_format($r['total_amount'], restaurant()->currency_id) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                $statusColors = [
                                    'pending'     => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                    'confirmed'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                    'checked_in'  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    'checked_out' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                                    'cancelled'   => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                    'no_show'     => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                                ];
                                $sc = $statusColors[$r['status']] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sc }}">
                                    {{ ucfirst(str_replace('_', ' ', $r['status'])) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                <svg class="w-8 h-8 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                No reservations found for the selected filters.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ── REVENUE TAB ───────────────────────────────────────────────── --}}
        @if($activeTab === 'revenue')
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800 dark:text-white">Revenue by Room Type</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($revenueReport) }} types</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            @foreach(['Room Type', 'Rooms', 'Bookings', 'Total Nights', 'Total Revenue', 'Avg Nightly Rate', 'Occupancy %'] as $col)
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($revenueReport as $r)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ $r['room_type'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-center">{{ $r['total_rooms'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-center">{{ $r['booking_count'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-center">{{ $r['total_nights'] }}</td>
                            <td class="px-4 py-3 font-semibold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">{{ currency_format($r['total_revenue'], restaurant()->currency_id) }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ currency_format($r['avg_nightly_rate'], restaurant()->currency_id) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 w-20">
                                        <div class="h-2 rounded-full {{ $r['occupancy_pct'] >= 75 ? 'bg-emerald-500' : ($r['occupancy_pct'] >= 40 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                            style="width: {{ min(100, $r['occupancy_pct']) }}%"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-200 whitespace-nowrap">{{ $r['occupancy_pct'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No revenue data for the selected range.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($revenueReport) > 0)
                    <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                        <tr class="font-semibold text-gray-800 dark:text-white text-sm">
                            <td class="px-4 py-3">Totals</td>
                            <td class="px-4 py-3 text-center">{{ collect($revenueReport)->sum('total_rooms') }}</td>
                            <td class="px-4 py-3 text-center">{{ collect($revenueReport)->sum('booking_count') }}</td>
                            <td class="px-4 py-3 text-center">{{ collect($revenueReport)->sum('total_nights') }}</td>
                            <td class="px-4 py-3 text-emerald-600 dark:text-emerald-400">{{ currency_format(collect($revenueReport)->sum('total_revenue'), restaurant()->currency_id) }}</td>
                            <td class="px-4 py-3">—</td>
                            <td class="px-4 py-3">—</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @endif

        {{-- ── HOUSEKEEPING TAB ──────────────────────────────────────────── --}}
        @if($activeTab === 'housekeeping')

        {{-- Summary totals row --}}
        @php $hkTotals = $housekeepingReport['totals'] ?? []; @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            @foreach([
                ['label'=>'Total Tasks',  'val'=>$hkTotals['total'] ?? 0,       'color'=>'text-gray-800 dark:text-white',           'bg'=>'bg-gray-100 dark:bg-gray-700'],
                ['label'=>'Pending',      'val'=>$hkTotals['pending'] ?? 0,     'color'=>'text-amber-700 dark:text-amber-400',      'bg'=>'bg-amber-50 dark:bg-amber-900/30'],
                ['label'=>'In Progress',  'val'=>$hkTotals['in_progress'] ?? 0, 'color'=>'text-blue-700 dark:text-blue-400',        'bg'=>'bg-blue-50 dark:bg-blue-900/30'],
                ['label'=>'Completed',    'val'=>$hkTotals['completed'] ?? 0,   'color'=>'text-emerald-700 dark:text-emerald-400',  'bg'=>'bg-emerald-50 dark:bg-emerald-900/30'],
            ] as $card)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 flex items-center gap-4">
                <div class="p-2.5 rounded-xl {{ $card['bg'] }}">
                    <svg class="w-5 h-5 {{ $card['color'] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $card['label'] }}</div>
                    <div class="text-2xl font-bold {{ $card['color'] }}">{{ $card['val'] }}</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- By-type breakdown table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-800 dark:text-white">Task Breakdown by Type</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            @foreach(['Task Type', 'Total', 'Pending', 'In Progress', 'Completed', 'Completion %', 'Avg Time (mins)'] as $col)
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($housekeepingReport['by_type'] ?? [] as $r)
                        @php $completionPct = $r['total'] > 0 ? round(($r['completed'] / $r['total']) * 100) : 0; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white whitespace-nowrap capitalize">{{ str_replace('_', ' ', $r['task_type']) }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-center">{{ $r['total'] }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-6 rounded-md bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 text-xs font-semibold">{{ $r['pending'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-6 rounded-md bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs font-semibold">{{ $r['in_progress'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-6 rounded-md bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-xs font-semibold">{{ $r['completed'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 w-20">
                                        <div class="h-2 rounded-full {{ $completionPct >= 75 ? 'bg-emerald-500' : ($completionPct >= 40 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                            style="width: {{ $completionPct }}%"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ $completionPct }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">
                                {{ $r['avg_mins'] > 0 ? $r['avg_mins'] . ' min' : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No housekeeping tasks found for the selected date range.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @endif

    </div>{{-- end tab content --}}

    {{-- Loading indicator --}}
    <div wire:loading.flex class="fixed inset-0 bg-black/20 dark:bg-black/40 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-xl flex items-center gap-3">
            <svg class="w-5 h-5 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Loading...</span>
        </div>
    </div>
</div>
