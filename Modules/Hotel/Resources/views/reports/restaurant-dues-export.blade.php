<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('hotel::modules.restaurantDues.title') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111827; margin: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
        .toolbar { margin-bottom: 12px; }
        .btn { border: 1px solid #d1d5db; background: white; padding: 6px 12px; border-radius: 6px; }
        @media print { .toolbar { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
<div class="toolbar">
    <button class="btn" onclick="window.print()">{{ __('app.print') }}</button>
</div>
<h2>{{ __('hotel::modules.restaurantDues.title') }} - {{ $activeTab === 'ledger' ? __('hotel::modules.restaurantDues.ledgerTab') : __('hotel::modules.restaurantDues.reportTab') }}</h2>
<p>Printed at: {{ now()->timezone(timezone())->format('d M Y, h:i A') }}</p>

@if($activeTab === 'ledger')
<table>
    <thead><tr><th>{{ __('app.date') }}</th><th>{{ __('app.description') }}</th><th class="right">{{ __('hotel::modules.restaurantDues.debit') }}</th><th class="right">{{ __('hotel::modules.restaurantDues.credit') }}</th><th class="right">{{ __('hotel::modules.restaurantDues.balance') }}</th></tr></thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
            <td>{{ $row['description'] }}</td>
            <td class="right">{{ $row['debit'] > 0 ? currency_format($row['debit'], $currencyId) : '-' }}</td>
            <td class="right">{{ $row['credit'] > 0 ? currency_format($row['credit'], $currencyId) : '-' }}</td>
            <td class="right">{{ currency_format($row['balance'], $currencyId) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@else
<table>
    <thead><tr><th>{{ __('hotel::modules.restaurantDues.roomNumber') }}</th><th>{{ __('hotel::modules.restaurantDues.guest') }}</th><th>{{ __('hotel::modules.restaurantDues.restaurantBranch') }}</th><th>{{ __('hotel::modules.restaurantDues.orders') }}</th><th class="right">{{ __('hotel::modules.restaurantDues.totalCharges') }}</th><th class="right">{{ __('hotel::modules.restaurantDues.totalPaid') }}</th><th class="right">{{ __('hotel::modules.restaurantDues.outstandingAmount') }}</th></tr></thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{ $row['room_number'] }}</td>
            <td>{{ $row['guest_name'] }}</td>
            <td>{{ $row['restaurant_branch_name'] }}</td>
            <td>{{ $row['orders_count'] }}</td>
            <td class="right">{{ currency_format($row['total_charges'], $currencyId) }}</td>
            <td class="right">{{ currency_format($row['total_paid'], $currencyId) }}</td>
            <td class="right">{{ currency_format($row['outstanding_amount'], $currencyId) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@endif
</body>
</html>
