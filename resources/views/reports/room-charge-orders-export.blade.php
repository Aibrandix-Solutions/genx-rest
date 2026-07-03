<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('menu.roomChargeOrdersReport') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 24px; color: #111827; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h2>{{ __('menu.roomChargeOrdersReport') }}</h2>
    <p>{{ __('modules.report.printedAt') }}: {{ $printedAt ?? now()->format('d M Y, h:i A') }}</p>
    <table>
        <thead>
        <tr>
            <th>{{ __('modules.report.dateAndTime') }}</th>
            <th>{{ __('modules.order.orderNumber') }}</th>
            <th>{{ __('modules.report.room') }}</th>
            <th>{{ __('modules.report.guest') }}</th>
            <th class="right">{{ __('modules.order.amount') }}</th>
            <th>{{ __('app.status') }}</th>
            <th class="right">{{ __('modules.report.settledAmount') }}</th>
            <th class="right">{{ __('modules.report.outstandingAmount') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($orders as $order)
            @php $status = \App\Services\RoomChargeOrderSettlement::status($order); @endphp
            <tr>
                <td>{{ optional($order->date_time)->timezone(timezone())->format('d M Y, h:i A') }}</td>
                <td>{{ $order->show_formatted_order_number }}</td>
                <td>{{ $order->hotelReservation?->room?->room_number ?? '--' }}</td>
                <td>{{ $order->hotelReservation?->guest?->full_name ?? '--' }}</td>
                <td class="right">{{ currency_format($order->total, $currencyId) }}</td>
                <td>{{ $status }}</td>
                <td class="right">{{ currency_format(\App\Services\RoomChargeOrderSettlement::settledAmount($order), $currencyId) }}</td>
                <td class="right">{{ currency_format(\App\Services\RoomChargeOrderSettlement::outstandingAmount($order), $currencyId) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
