<div class="max-w-3xl mx-auto p-8 bg-white" id="invoice">
    <!-- Header -->
    <div class="flex justify-between items-start mb-8 border-b pb-4">
        <div>
            @if($reservation->branch->logo_url)
                <img src="{{ $reservation->branch->logo_url }}" alt="Logo" class="h-12 mb-2">
            @else
                <h1 class="text-2xl font-bold">{{ $reservation->branch->name }}</h1>
            @endif
            <div class="text-sm text-gray-600">
                <p>{{ $reservation->branch->address }}</p>
                <p>{{ $reservation->branch->phone }}</p>
            </div>
        </div>
        <div class="text-right">
            <h2 class="text-xl font-bold text-gray-800">INVOICE</h2>
            <p class="text-gray-600">Res #: {{ $reservation->id }}</p>
            <p class="text-gray-600">Date: {{ now()->format('d-M-Y') }}</p>
        </div>
    </div>

    <!-- Guest Info -->
    <div class="grid grid-cols-2 gap-8 mb-8">
        <div>
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2">Details</h3>
             <p class="font-bold">{{ $reservation->guest->name }}</p>
             <p class="text-gray-600">{{ $reservation->guest->email }}</p>
             <p class="text-gray-600">{{ $reservation->guest->phone }}</p>
        </div>
        <div class="text-right">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2">Stay Info</h3>
            <p><span class="text-gray-600">Room:</span> <span class="font-bold">{{ $reservation->room->room_number }}</span></p>
            <p><span class="text-gray-600">Check In:</span> {{ $reservation->check_in_date->format('d-M-Y') }}</p>
            <p><span class="text-gray-600">Check Out:</span> {{ $reservation->check_out_date->format('d-M-Y') }}</p>
        </div>
    </div>

    <!-- Charges Table -->
    <table class="w-full mb-8">
        <thead>
            <tr class="border-b-2 border-gray-800">
                <th class="text-left py-2">Date</th>
                <th class="text-left py-2">Description</th>
                <th class="text-right py-2">Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($charges as $charge)
            <tr>
                <td class="py-3 text-sm">{{ $charge->charge_date->format('d/m/Y') }}</td>
                <td class="py-3">
                    <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $charge->charge_type)) }}</p>
                    @if($charge->remarks)
                        <p class="text-xs text-gray-500">{{ $charge->remarks }}</p>
                    @endif
                </td>
                <td class="py-3 text-right font-medium text-gray-800">
                    {{ currency_format($charge->amount) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="flex justify-end border-t pt-4">
        <div class="w-64 space-y-2">
            <div class="flex justify-between text-gray-600">
                <span>Subtotal</span>
                <span>{{ currency_format($totalCharges) }}</span>
            </div>
            {{-- Tax would go here if applicable --}}
            <div class="flex justify-between text-xl font-bold border-t border-gray-800 pt-2">
                <span>Total Due</span>
                <span>{{ currency_format($balance) }}</span>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-12 text-center text-sm text-gray-500 border-t pt-8">
        <p>Thank you for staying with us!</p>
    </div>

    <script>
        window.print();
    </script>
</div>
