<div class="card bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white">
            Guest Folio: {{ $reservation->guest->full_name }}
            <span class="text-sm font-normal text-gray-500">
                (Room: {{ $reservation->room->room_number }})
            </span>
        </h2>
        <div class="space-x-2">
            <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Print Invoice
            </button>
            <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Add Payment
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b dark:border-gray-700">
                    <th class="p-3 font-semibold text-gray-600 dark:text-gray-300">Date</th>
                    <th class="p-3 font-semibold text-gray-600 dark:text-gray-300">Description</th>
                    <th class="p-3 font-semibold text-gray-600 dark:text-gray-300 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($charges as $charge)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="p-3 text-gray-700 dark:text-gray-300">
                            {{ $charge->charge_date->format('d M Y, h:i A') }}
                        </td>
                        <td class="p-3 text-gray-700 dark:text-gray-300">
                            <span class="font-medium">
                                {{ ucfirst(str_replace('_', ' ', $charge->charge_type)) }}
                            </span>
                            @if($charge->description)
                                <div class="text-xs text-gray-500">{{ $charge->description }}</div>
                            @endif
                        </td>
                        <td class="p-3 text-right font-medium text-gray-700 dark:text-gray-300">
                            {{ currency_format($charge->amount) }}
                        </td>
                    </tr>
                @empty
                    @if($orders->isEmpty())
                    <tr>
                        <td colspan="3" class="p-6 text-center text-gray-500">
                            No charges or orders found on this folio.
                        </td>
                    </tr>
                    @endif
                @endforelse

                @if($orders->isNotEmpty())
                    <tr class="bg-yellow-50 dark:bg-yellow-900/20">
                        <td colspan="3" class="p-2 text-xs font-semibold text-yellow-700 dark:text-yellow-400 uppercase tracking-wide">
                            Pending Room Service Orders
                        </td>
                    </tr>
                @endif
                @foreach($orders as $order)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 bg-yellow-50/30 dark:bg-yellow-900/10">
                        <td class="p-3 text-gray-700 dark:text-gray-300">
                            {{ $order->created_at->format('d M Y, h:i A') }}
                        </td>
                        <td class="p-3 text-gray-700 dark:text-gray-300">
                            <span class="font-medium">
                                Order #{{ $order->order_number }}
                            </span>
                            <span class="text-xs px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-300">
                                {{ Str::ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="p-3 text-right font-medium text-gray-700 dark:text-gray-300">
                            {{ currency_format($order->total) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 dark:border-gray-600">
                    <td colspan="2" class="p-3 text-right font-bold text-gray-800 dark:text-white">Total Bill:</td>
                    <td class="p-3 text-right font-bold text-gray-800 dark:text-white">
                        {{ currency_format($totalCharges + $totalOrders) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="p-3 text-right font-bold text-gray-800 dark:text-white">Total Paid:</td>
                    <td class="p-3 text-right font-bold text-green-600">
                        {{ currency_format($totalPayments) }}
                    </td>
                </tr>
                <tr class="bg-gray-100 dark:bg-gray-900">
                    <td colspan="2" class="p-3 text-right font-bold text-blue-800 dark:text-blue-300 text-lg">
                        Balance Due:
                    </td>
                    <td class="p-3 text-right font-bold text-blue-800 dark:text-blue-300 text-lg">
                        {{ currency_format($balance) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
