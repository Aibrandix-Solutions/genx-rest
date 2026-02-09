<div class="max-w-3xl mx-auto p-8 bg-white" id="invoice">
    <!-- Header -->
    <div class="flex justify-between items-start mb-8 border-b pb-4">
        <div>
            @if($hotelLogo)
                <img src="{{ asset_url_local_s3('hotel-logo/' . $hotelLogo) }}" alt="Hotel Logo" class="h-12 mb-2">
            @elseif($reservation->branch->logo_url)
                <img src="{{ $reservation->branch->logo_url }}" alt="Logo" class="h-12 mb-2">
            @endif
            <h1 class="text-2xl font-bold">{{ $hotelName }}</h1>
            <div class="text-sm text-gray-600">
                <p>{{ $reservation->branch->address }}</p>
                <p>{{ $reservation->branch->phone }}</p>
            </div>
        </div>
        <div class="text-right">
            <h2 class="text-xl font-bold text-gray-800">@lang('hotel::modules.invoice.title')</h2>
            <p class="text-gray-600">@lang('hotel::modules.invoice.resNumber'): {{ $reservation->reservation_number }}</p>
            <p class="text-gray-600">@lang('hotel::modules.folio.date'): {{ now()->format('d-M-Y') }}</p>
        </div>
    </div>

    <!-- Guest Info -->
    <div class="grid grid-cols-2 gap-8 mb-8">
        <div>
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2">@lang('hotel::modules.invoice.guestDetails')</h3>
             <p class="font-bold">{{ $reservation->guest->full_name }}</p>
             <p class="text-gray-600">{{ $reservation->guest->email }}</p>
             <p class="text-gray-600">{{ $reservation->guest->phone }}</p>
        </div>
        <div class="text-right">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2">@lang('hotel::modules.invoice.stayInfo')</h3>
            <p><span class="text-gray-600">@lang('hotel::modules.reservation.room'):</span> <span class="font-bold">{{ $reservation->room->room_number }}</span> ({{ $reservation->room->roomType->name }})</p>
            <p><span class="text-gray-600">@lang('hotel::modules.reservation.checkIn'):</span> {{ $reservation->check_in_date->format('d-M-Y') }}</p>
            <p><span class="text-gray-600">@lang('hotel::modules.reservation.checkOut'):</span> {{ $reservation->checkout_date->format('d-M-Y') }}</p>
            <p><span class="text-gray-600">@lang('hotel::modules.folio.nights'):</span> {{ $reservation->getNumberOfNights() }}</p>
        </div>
    </div>

    <!-- Charges Table -->
    <table class="w-full mb-6">
        <thead>
            <tr class="border-b-2 border-gray-800">
                <th class="text-left py-2">@lang('hotel::modules.folio.date')</th>
                <th class="text-left py-2">@lang('hotel::modules.folio.description')</th>
                <th class="text-right py-2">@lang('hotel::modules.folio.amount')</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($charges as $charge)
            <tr>
                <td class="py-3 text-sm">{{ $charge->charge_date->format('d/m/Y') }}</td>
                <td class="py-3">
                    <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $charge->charge_type)) }}</p>
                    @if($charge->description)
                        <p class="text-xs text-gray-500">{{ $charge->description }}</p>
                    @endif
                </td>
                <td class="py-3 text-right font-medium text-gray-800">
                    {{ currency_format($charge->amount) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Payments Table -->
    @if($payments->isNotEmpty())
    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2 mt-6">@lang('hotel::modules.invoice.paymentsReceived')</h3>
    <table class="w-full mb-6">
        <thead>
            <tr class="border-b-2 border-gray-800">
                <th class="text-left py-2">@lang('hotel::modules.folio.date')</th>
                <th class="text-left py-2">@lang('hotel::modules.folio.method')</th>
                <th class="text-left py-2">@lang('hotel::modules.folio.type')</th>
                <th class="text-right py-2">@lang('hotel::modules.folio.amount')</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($payments as $payment)
            <tr>
                <td class="py-2 text-sm">{{ $payment->created_at->format('d/m/Y') }}</td>
                <td class="py-2 text-sm">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                <td class="py-2 text-sm">{{ ucfirst($payment->payment_type) }}</td>
                <td class="py-2 text-right font-medium {{ $payment->payment_type === 'refund' ? 'text-red-600' : 'text-green-700' }}">
                    {{ $payment->payment_type === 'refund' ? '-' : '' }}{{ currency_format($payment->amount) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Totals -->
    <div class="flex justify-end border-t pt-4">
        <div class="w-64 space-y-2">
            <div class="flex justify-between text-gray-600">
                <span>@lang('hotel::modules.folio.totalCharges')</span>
                <span>{{ currency_format($totalCharges) }}</span>
            </div>
            <div class="flex justify-between text-green-700">
                <span>@lang('hotel::modules.folio.totalPaid')</span>
                <span>- {{ currency_format($totalPayments) }}</span>
            </div>
            <div class="flex justify-between text-xl font-bold border-t border-gray-800 pt-2">
                <span>@lang('hotel::modules.folio.balanceDue')</span>
                <span>{{ currency_format($balance) }}</span>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-12 text-center text-sm text-gray-500 border-t pt-8">
        <p>@lang('hotel::modules.invoice.thankYou')</p>
    </div>

    <script>
        window.print();
    </script>
</div>
