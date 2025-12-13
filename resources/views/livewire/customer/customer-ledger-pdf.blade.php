<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('modules.customer.ledger_title') }} - {{ $customer->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        [dir="rtl"] {
            text-align: right;
        }

        [dir="ltr"] {
            text-align: left;
        }

        body {
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .restaurant-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .document-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
        }

        .customer-info {
            margin-bottom: 20px;
        }

        .info-row {
            margin-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }

        .summary-section {
            margin-bottom: 20px;
            display: table;
            width: 100%;
        }

        .summary-box {
            display: table-cell;
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
            width: 25%;
        }

        .summary-label {
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
        }

        .opening-balance {
            background-color: #e3f2fd;
        }

        .total-debit {
            background-color: #e8f5e9;
        }

        .total-credit {
            background-color: #fff3e0;
        }

        .closing-balance {
            background-color: #f3e5f5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #f5f5f5;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
            font-size: 11px;
        }

        td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 11px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .debit {
            color: #d32f2f;
        }

        .credit {
            color: #388e3c;
        }

        .balance {
            font-weight: bold;
        }

        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }

        .status-paid {
            background-color: #c8e6c9;
            color: #2e7d32;
        }

        .status-pending {
            background-color: #fff9c4;
            color: #f57f17;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="restaurant-name">{{ restaurant()->name }}</div>
        <div class="document-title">{{ __('modules.customer.ledger_title') }}</div>
    </div>

    <div class="customer-info">
        <div class="info-row">
            <span class="info-label">{{ __('modules.customer.name') }}:</span>
            <span>{{ $customer->name }}</span>
        </div>
        @if($customer->email)
        <div class="info-row">
            <span class="info-label">{{ __('app.email') }}:</span>
            <span>{{ $customer->email }}</span>
        </div>
        @endif
        @if($customer->phone)
        <div class="info-row">
            <span class="info-label">{{ __('modules.customer.phone') }}:</span>
            <span>{{ $customer->phone }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">{{ __('app.dateRange') }}:</span>
            <span>{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">{{ __('app.date') }}:</span>
            <span>{{ \Carbon\Carbon::now()->format('M d, Y h:i A') }}</span>
        </div>
    </div>

    <div class="summary-section">
        <div class="summary-box opening-balance">
            <div class="summary-label">{{ __('modules.customer.opening_balance') }}</div>
            <div class="summary-value">{{ currency_format($openingBalance, restaurant()->currency_id) }}</div>
        </div>
        <div class="summary-box total-debit">
            <div class="summary-label">{{ __('modules.customer.total_debit') }}</div>
            <div class="summary-value">{{ currency_format($totalDebit, restaurant()->currency_id) }}</div>
        </div>
        <div class="summary-box total-credit">
            <div class="summary-label">{{ __('modules.customer.total_credit') }}</div>
            <div class="summary-value">{{ currency_format($totalCredit, restaurant()->currency_id) }}</div>
        </div>
        <div class="summary-box closing-balance">
            <div class="summary-label">{{ __('modules.customer.closing_balance') }}</div>
            <div class="summary-value">{{ currency_format($closingBalance, restaurant()->currency_id) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('app.date') }}</th>
                <th>{{ __('modules.customer.reference') }}</th>
                <th>{{ __('app.description') }}</th>
                <th class="text-right">{{ __('modules.customer.debit') }}</th>
                <th class="text-right">{{ __('modules.customer.credit') }}</th>
                <th class="text-right">{{ __('modules.customer.balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('M d, Y h:i A') }}</td>
                    <td>{{ $transaction['reference'] }}</td>
                    <td>
                        {{ $transaction['description'] }}
                        @if($transaction['type'] === 'order' && isset($transaction['status']))
                            <span class="status-badge status-{{ $transaction['status'] === 'paid' ? 'paid' : 'pending' }}">
                                {{ strtoupper($transaction['status']) }}
                            </span>
                        @endif
                    </td>
                    <td class="text-right debit">
                        {{ $transaction['debit'] > 0 ? currency_format($transaction['debit'], restaurant()->currency_id) : '—' }}
                    </td>
                    <td class="text-right credit">
                        {{ $transaction['credit'] > 0 ? currency_format($transaction['credit'], restaurant()->currency_id) : '—' }}
                    </td>
                    <td class="text-right balance {{ $transaction['balance'] > 0 ? 'debit' : '' }}">
                        {{ currency_format($transaction['balance'], restaurant()->currency_id) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">{{ __('modules.customer.no_transactions_found') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ __('app.rightReserved') }} - {{ restaurant()->name }}
    </div>
</body>
</html>

