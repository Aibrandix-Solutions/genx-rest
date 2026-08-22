<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Property P&amp;L Report</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111827; margin: 0; padding: 16px 18px; }
        .header { background: #4338ca; color: #fff; padding: 14px 16px; border-radius: 6px; margin-bottom: 12px; }
        .header h1 { margin: 0 0 4px; font-size: 18px; }
        .header p { margin: 0; color: #e0e7ff; font-size: 10px; }
        .cards-3 { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 12px; }
        .cards-3 td { width: 33.33%; vertical-align: top; padding: 0; }
        .card { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; background: #fff; min-height: 72px; text-align: center; }
        .card.green { border-color: #86efac; background: #f0fdf4; }
        .card.red { border-color: #fca5a5; background: #fef2f2; }
        .card.loss { border-color: #fca5a5; background: #fef2f2; }
        .card-title { font-size: 9px; color: #6b7280; margin-bottom: 6px; text-transform: uppercase; font-weight: 700; }
        .card-value { font-size: 16px; font-weight: 700; color: #111827; }
        .card-value.profit { color: #15803d; }
        .card-value.loss { color: #b91c1c; }
        .card-sub { font-size: 8px; color: #6b7280; margin-top: 6px; }
        .section-label { font-size: 10px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin: 14px 0 8px; }
        .cards-2 { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 12px; }
        .cards-2 td { width: 50%; vertical-align: top; }
        .card.revenue { border-color: #bbf7d0; background: #f0fdf4; }
        .card.expense { border-color: #fecaca; background: #fef2f2; }
        .card-heading { font-size: 11px; font-weight: 700; color: #374151; margin-bottom: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .line { display: table; width: 100%; margin-bottom: 5px; }
        .line .lbl { display: table-cell; color: #374151; font-size: 9px; }
        .line .val { display: table-cell; text-align: right; font-weight: 600; font-size: 9px; }
        .line.total { border-top: 1px solid #d1d5db; padding-top: 6px; margin-top: 6px; }
        .line.total .lbl, .line.total .val { font-weight: 700; }
        .line.total.revenue .val { color: #15803d; }
        .line.total.expense .val { color: #b91c1c; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: 600; }
        .badge.green { background: #dcfce7; color: #166534; }
        .badge.purple { background: #f3e8ff; color: #6b21a8; }
        .badge.blue { background: #dbeafe; color: #1e40af; }
        .badge.orange { background: #ffedd5; color: #9a3412; }
        .empty { color: #9ca3af; font-style: italic; font-size: 9px; }
        table.trend { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.trend th, table.trend td { border: 1px solid #d1d5db; padding: 6px 7px; }
        table.trend th { background: #e5e7eb; font-size: 8px; text-transform: uppercase; color: #374151; text-align: center; }
        table.trend td { font-size: 9px; }
        table.trend td.month { text-align: left; font-weight: 600; }
        table.trend td.num { text-align: right; }
        table.trend td.revenue { color: #15803d; }
        table.trend td.expense { color: #b91c1c; }
        table.trend td.profit-pos { color: #15803d; font-weight: 700; }
        table.trend td.profit-neg { color: #b91c1c; font-weight: 700; }
        .margin-badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: 600; }
        .margin-badge.pos { background: #dcfce7; color: #166534; }
        .margin-badge.neg { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Property P&amp;L Report</h1>
        <p>
            @if($propertyName){{ $propertyName }} &bull; @endif
            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            &bull; Generated {{ now()->timezone(timezone())->format('d M Y, h:i A') }}
        </p>
    </div>

    <table class="cards-3">
        <tr>
            <td>
                <div class="card {{ $data['netProfit'] >= 0 ? 'green' : 'loss' }}">
                    <div class="card-title">{{ $data['netProfit'] >= 0 ? 'Net Profit' : 'Net Loss' }}</div>
                    <div class="card-value {{ $data['netProfit'] >= 0 ? 'profit' : 'loss' }}">{{ currency_format(abs($data['netProfit']), $currencyId) }}</div>
                    <div class="card-sub">{{ $data['netProfit'] >= 0 ? '▲ Profit' : '▼ Loss' }} · {{ $data['profitMargin'] }}% margin</div>
                </div>
            </td>
            <td>
                <div class="card green">
                    <div class="card-title">Total Revenue</div>
                    <div class="card-value">{{ currency_format($data['totalRevenue'], $currencyId) }}</div>
                    <div class="card-sub">Hotel + Restaurant combined</div>
                </div>
            </td>
            <td>
                <div class="card red">
                    <div class="card-title">Total Expenses</div>
                    <div class="card-value">{{ currency_format($data['totalExpenses'], $currencyId) }}</div>
                    <div class="card-sub">Hotel + Restaurant combined</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-label">Revenue &amp; Expense Breakdown</div>
    <table class="cards-2">
        <tr>
            <td>
                <div class="card revenue">
                    <div class="card-heading">Revenue Breakdown</div>
                    <div class="line">
                        <span class="lbl"><span class="badge green">Room Nights</span></span>
                        <span class="val">{{ currency_format($data['roomNightRevenue'], $currencyId) }}</span>
                    </div>
                    <div class="line">
                        <span class="lbl"><span class="badge purple">Room Service</span></span>
                        <span class="val">{{ currency_format($data['roomServiceSales'], $currencyId) }}</span>
                    </div>
                    <div class="line">
                        <span class="lbl"><span class="badge blue">Restaurant</span></span>
                        <span class="val">{{ currency_format($data['restaurantSales'], $currencyId) }}</span>
                    </div>
                    <div class="line">
                        <span class="lbl"><span class="badge orange">Hotel Add-ons</span></span>
                        <span class="val">{{ currency_format($data['hotelAddOns'], $currencyId) }}</span>
                    </div>
                    <div class="line total revenue">
                        <span class="lbl">Total Revenue</span>
                        <span class="val">{{ currency_format($data['totalRevenue'], $currencyId) }}</span>
                    </div>
                </div>
            </td>
            <td>
                <div class="card expense">
                    <div class="card-heading">Expense Breakdown</div>
                    @forelse($data['hotelExpByDept'] as $exp)
                        <div class="line">
                            <span class="lbl">Hotel · {{ ucwords(str_replace('_', ' ', $exp->department)) }}</span>
                            <span class="val">{{ currency_format($exp->total, $currencyId) }}</span>
                        </div>
                    @empty
                        @if($data['restaurantExpenses'] <= 0)
                            <div class="empty">No hotel expenses this period.</div>
                        @endif
                    @endforelse
                    @if($data['restaurantExpenses'] > 0)
                        <div class="line">
                            <span class="lbl">Restaurant Expenses</span>
                            <span class="val">{{ currency_format($data['restaurantExpenses'], $currencyId) }}</span>
                        </div>
                    @endif
                    <div class="line total expense">
                        <span class="lbl">Total Expenses</span>
                        <span class="val">{{ currency_format($data['totalExpenses'], $currencyId) }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-label">6-Month Trend</div>
    <table class="trend">
        <thead>
            <tr>
                <th>Month</th>
                <th>Revenue</th>
                <th>Expenses</th>
                <th>Net Profit</th>
                <th>Margin</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['trend'] as $t)
                @php $margin = $t['revenue'] > 0 ? round(($t['profit'] / $t['revenue']) * 100, 1) : 0; @endphp
                <tr>
                    <td class="month">{{ $t['label'] }}</td>
                    <td class="num revenue">{{ currency_format($t['revenue'], $currencyId) }}</td>
                    <td class="num expense">{{ currency_format($t['expenses'], $currencyId) }}</td>
                    <td class="num {{ $t['profit'] >= 0 ? 'profit-pos' : 'profit-neg' }}">{{ currency_format($t['profit'], $currencyId) }}</td>
                    <td class="num">
                        <span class="margin-badge {{ $margin >= 0 ? 'pos' : 'neg' }}">{{ $margin }}%</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
