<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Unified Finance Report</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111827; margin: 0; padding: 16px 18px; }
        .header { background: #4338ca; color: #fff; padding: 14px 16px; border-radius: 6px; margin-bottom: 12px; }
        .header h1 { margin: 0 0 4px; font-size: 18px; }
        .header p { margin: 0; color: #e0e7ff; font-size: 10px; }
        .section-label { font-size: 10px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin: 14px 0 8px; }
        .cards-4 { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 8px; }
        .cards-4 td { width: 25%; vertical-align: top; padding: 0; }
        .card { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; background: #fff; min-height: 70px; }
        .card.blue { border-color: #bfdbfe; background: #eff6ff; }
        .card.purple { border-color: #e9d5ff; background: #faf5ff; }
        .card.green { border-color: #bbf7d0; background: #f0fdf4; }
        .card.orange { border-color: #fed7aa; background: #fff7ed; }
        .card-title { font-size: 9px; color: #6b7280; margin-bottom: 4px; }
        .card-value { font-size: 14px; font-weight: 700; color: #111827; }
        .card-sub { font-size: 8px; color: #9ca3af; margin-top: 4px; }
        .banner { background: #2563eb; color: #fff; text-align: center; padding: 12px; border-radius: 6px; margin-bottom: 12px; }
        .banner .label { font-size: 9px; opacity: 0.9; text-transform: uppercase; }
        .banner .value { font-size: 20px; font-weight: 700; margin-top: 2px; }
        .cards-3 { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 12px; }
        .cards-3 td { width: 33.33%; vertical-align: top; }
        .card.neutral { border-color: #e5e7eb; background: #f9fafb; }
        .card.yellow { border-color: #fde68a; background: #fffbeb; }
        .card-heading { font-size: 11px; font-weight: 700; color: #374151; margin-bottom: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .line { display: table; width: 100%; margin-bottom: 4px; }
        .line .lbl { display: table-cell; color: #6b7280; font-size: 9px; }
        .line .val { display: table-cell; text-align: right; font-weight: 600; font-size: 9px; }
        .line.total { border-top: 1px solid #e5e7eb; padding-top: 6px; margin-top: 6px; }
        .line.total .lbl, .line.total .val { font-weight: 700; color: #111827; }
        .outstanding-value { font-size: 18px; font-weight: 700; color: #d97706; margin: 6px 0; }
        table.daily { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.daily th, table.daily td { border: 1px solid #d1d5db; padding: 6px 7px; }
        table.daily th { background: #e5e7eb; font-size: 8px; text-transform: uppercase; color: #374151; text-align: center; }
        table.daily td { font-size: 9px; }
        table.daily td.date { text-align: left; font-weight: 600; }
        table.daily td.num { text-align: right; }
        table.daily tr.total td { background: #f3f4f6; font-weight: 700; }
        .empty { color: #9ca3af; font-style: italic; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Unified Finance Report</h1>
        <p>
            @if($propertyName){{ $propertyName }} &bull; @endif
            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            &bull; Generated {{ now()->timezone(timezone())->format('d M Y, h:i A') }}
        </p>
    </div>

    <div class="section-label">Revenue Breakdown</div>
    <table class="cards-4">
        <tr>
            <td>
                <div class="card blue">
                    <div class="card-title">Restaurant Sales</div>
                    <div class="card-value">{{ currency_format($summary['restaurantSales'], $currencyId) }}</div>
                    <div class="card-sub">Dine-in, delivery &amp; pickup</div>
                </div>
            </td>
            <td>
                <div class="card purple">
                    <div class="card-title">Room Service</div>
                    <div class="card-value">{{ currency_format($summary['roomServiceSales'], $currencyId) }}</div>
                    <div class="card-sub">In-room F&amp;B orders</div>
                </div>
            </td>
            <td>
                <div class="card green">
                    <div class="card-title">Room Revenue</div>
                    <div class="card-value">{{ currency_format($summary['roomNightRevenue'], $currencyId) }}</div>
                    <div class="card-sub">Nightly room charges</div>
                </div>
            </td>
            <td>
                <div class="card orange">
                    <div class="card-title">Hotel Add-ons</div>
                    <div class="card-value">{{ currency_format($summary['hotelAddOns'], $currencyId) }}</div>
                    <div class="card-sub">Minibar, laundry, service</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="banner">
        <div class="label">Total Property Revenue</div>
        <div class="value">{{ currency_format($summary['totalRevenue'], $currencyId) }}</div>
    </div>

    <table class="cards-3">
        <tr>
            <td>
                <div class="card neutral">
                    <div class="card-heading">Payments Collected</div>
                    <div class="line"><span class="lbl">Hotel Payments</span><span class="val">{{ currency_format($summary['hotelPaymentsReceived'], $currencyId) }}</span></div>
                    <div class="line"><span class="lbl">Restaurant Payments</span><span class="val">{{ currency_format($summary['restaurantPaymentsReceived'], $currencyId) }}</span></div>
                    @if($summary['hotelRefunds'] > 0)
                    <div class="line"><span class="lbl">Hotel Refunds</span><span class="val">-{{ currency_format($summary['hotelRefunds'], $currencyId) }}</span></div>
                    @endif
                    <div class="line total"><span class="lbl">Total Collected</span><span class="val">{{ currency_format($summary['totalCollected'], $currencyId) }}</span></div>
                </div>
            </td>
            <td>
                <div class="card neutral">
                    <div class="card-heading">Hotel Expenses</div>
                    @forelse($summary['hotelExpensesByDept'] as $dept => $amount)
                        <div class="line"><span class="lbl">{{ ucwords(str_replace('_', ' ', $dept)) }}</span><span class="val">{{ currency_format($amount, $currencyId) }}</span></div>
                    @empty
                        <div class="empty">No expenses recorded for this period.</div>
                    @endforelse
                    <div class="line total"><span class="lbl">Total Expenses</span><span class="val">{{ currency_format($summary['hotelExpenses'], $currencyId) }}</span></div>
                </div>
            </td>
            <td>
                <div class="card yellow">
                    <div class="card-heading">Hotel Outstanding</div>
                    <div class="outstanding-value">{{ currency_format($summary['hotelOutstanding'], $currencyId) }}</div>
                    <div class="card-sub">Pending balances on active reservations</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-label">Daily Breakdown</div>
    @if($dailyBreakdown->isEmpty())
        <p class="empty">No transactions found for this period.</p>
    @else
    <table class="daily">
        <thead>
            <tr>
                <th>Date</th>
                <th>Restaurant</th>
                <th>Room Service</th>
                <th>Hotel Charges</th>
                <th>Total Revenue</th>
                <th>Expenses</th>
                <th>Net</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyBreakdown as $row)
            <tr>
                <td class="date">{{ \Carbon\Carbon::parse($row['day'])->format('D, M d') }}</td>
                <td class="num">{{ $row['restaurant'] > 0 ? currency_format($row['restaurant'], $currencyId) : '—' }}</td>
                <td class="num">{{ $row['room_service'] > 0 ? currency_format($row['room_service'], $currencyId) : '—' }}</td>
                <td class="num">{{ $row['hotel_charges'] > 0 ? currency_format($row['hotel_charges'], $currencyId) : '—' }}</td>
                <td class="num">{{ currency_format($row['total_revenue'], $currencyId) }}</td>
                <td class="num">{{ $row['expenses'] > 0 ? currency_format($row['expenses'], $currencyId) : '—' }}</td>
                <td class="num">{{ currency_format($row['net'], $currencyId) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total">
                <td class="date">TOTAL</td>
                <td class="num">{{ currency_format($dailyBreakdown->sum('restaurant'), $currencyId) }}</td>
                <td class="num">{{ currency_format($dailyBreakdown->sum('room_service'), $currencyId) }}</td>
                <td class="num">{{ currency_format($dailyBreakdown->sum('hotel_charges'), $currencyId) }}</td>
                <td class="num">{{ currency_format($dailyBreakdown->sum('total_revenue'), $currencyId) }}</td>
                <td class="num">{{ currency_format($dailyBreakdown->sum('expenses'), $currencyId) }}</td>
                <td class="num">{{ currency_format($dailyBreakdown->sum('net'), $currencyId) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif
</body>
</html>
