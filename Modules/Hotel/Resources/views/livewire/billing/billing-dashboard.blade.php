<div wire:poll.30s>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Hotel Billing</h1>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Outstanding balances and recent payments.
            </div>
        </div>
    </div>

    <div class="p-4 bg-gray-50 dark:bg-gray-900 min-h-[calc(100vh-200px)]">
        {{-- Summary Card --}}
        <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-500 dark:text-gray-400 font-semibold tracking-wide">Total Outstanding</div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ currency_format($totalOutstandingBalance, restaurant()->currency_id) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Across {{ $outstandingReservations->count() }} reservation(s)</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-500 dark:text-gray-400 font-semibold tracking-wide">Recent Payments</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ currency_format($recentPayments->sum('amount'), restaurant()->currency_id) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Last {{ $recentPayments->count() }} payment(s) recorded</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Outstanding Balances</h2>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="p-3 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Reservation</th>
                                <th class="p-3 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Guest</th>
                                <th class="p-3 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Room</th>
                                <th class="p-3 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Balance</th>
                                <th class="p-3 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse($outstandingReservations as $reservation)
                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <td class="p-3 text-sm font-semibold text-gray-900 dark:text-white">{{ $reservation->reservation_number }}</td>
                                    <td class="p-3 text-sm text-gray-500 dark:text-gray-400">{{ $reservation->guest?->full_name ?? '-' }}</td>
                                    <td class="p-3 text-sm text-gray-500 dark:text-gray-400">{{ $reservation->room?->room_number ?? '-' }}</td>
                                    <td class="p-3 text-sm font-semibold text-gray-900 dark:text-white">{{ currency_format($reservation->balance_due, restaurant()->currency_id) }}</td>
                                    <td class="p-3 text-sm">
                                        <a href="{{ route('hotel.folio', $reservation->reservation_number) }}" class="text-blue-600 hover:underline dark:text-blue-400">Open Folio</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-gray-500 dark:text-gray-400">No outstanding balances.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Payments</h2>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="p-3 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Date</th>
                                <th class="p-3 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Reservation</th>
                                <th class="p-3 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Amount</th>
                                <th class="p-3 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">Method</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse($recentPayments as $payment)
                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <td class="p-3 text-sm text-gray-500 dark:text-gray-400">{{ $payment->created_at?->format('d M Y') }}</td>
                                    <td class="p-3 text-sm font-semibold text-gray-900 dark:text-white">{{ $payment->reservation?->reservation_number ?? '-' }}</td>
                                    <td class="p-3 text-sm text-gray-900 dark:text-white">{{ currency_format($payment->amount, restaurant()->currency_id) }}</td>
                                    <td class="p-3 text-sm text-gray-500 dark:text-gray-400">{{ $payment->payment_method ? strtoupper($payment->payment_method) : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-6 text-center text-gray-500 dark:text-gray-400">No recent payments.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
