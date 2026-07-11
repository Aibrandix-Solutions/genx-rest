<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Income & Expense Report</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #111827; margin: 0; padding: 12px 14px; }
        .header { background: #4338ca; color: #fff; padding: 12px 14px; border-radius: 6px; margin-bottom: 12px; }
        .header h1 { margin: 0 0 4px; font-size: 16px; }
        .header p { margin: 0; color: #e0e7ff; font-size: 9px; }
        
        .section-label { font-size: 10px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.5px; margin: 16px 0 6px; border-bottom: 2px solid #e5e7eb; padding-bottom: 4px; }
        
        /* Summary Table */
        table.summary-tbl { width: 60%; border-collapse: collapse; margin-bottom: 12px; margin-top: 6px; }
        table.summary-tbl th, table.summary-tbl td { border: 1px solid #d1d5db; padding: 5px 8px; }
        table.summary-tbl th { background: #f3f4f6; text-align: left; font-weight: 700; }
        table.summary-tbl td.num { text-align: right; }
        table.summary-tbl tr.bold-row td { font-weight: 700; background: #f9fafb; }
        
        /* Detailed Tables */
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 4px; margin-bottom: 12px; }
        table.data-table th, table.data-table td { border: 1px solid #d1d5db; padding: 5px 6px; }
        table.data-table th { background: #e5e7eb; font-size: 8px; text-transform: uppercase; color: #374151; text-align: center; }
        table.data-table td { font-size: 8px; }
        table.data-table td.num { text-align: right; }
        table.data-table tr.total td { background: #f3f4f6; font-weight: 700; }
        
        .empty { color: #9ca3af; font-style: italic; font-size: 9px; padding: 10px 0; }
        .status-badge { font-weight: 700; text-transform: uppercase; font-size: 7px; padding: 2px 4px; border-radius: 3px; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-unpaid { background: #fee2e2; color: #991b1b; }
        .status-partial { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Income & Expense Report</h1>
        <p>
            @if($propertyName){{ $propertyName }} &bull; @endif
            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            &bull; Generated {{ now()->timezone(timezone())->format('d M Y, h:i A') }}
        </p>
    </div>

    <div class="section-label">Financial Summary (P&L)</div>
    <table class="summary-tbl">
        <thead>
            <tr>
                <th>Category</th>
                <th style="text-align: right;">Total Amount</th>
                <th style="text-align: right;">Paid</th>
                <th style="text-align: right;">Unpaid</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Revenue (Sales)</td>
                <td class="num">{{ currency_format($summary['totalRevenue'], $currencyId) }}</td>
                <td class="num">{{ currency_format($summary['totalCollected'], $currencyId) }}</td>
                <td class="num">{{ currency_format($summary['hotelOutstanding'], $currencyId) }}</td>
            </tr>
            <tr>
                <td>LESS: Expenses</td>
                <td class="num">-{{ currency_format($summary['hotelExpenses'], $currencyId) }}</td>
                <td class="num">-{{ currency_format($summary['hotelExpensesPaid'], $currencyId) }}</td>
                <td class="num">-{{ currency_format($summary['hotelExpensesUnpaid'], $currencyId) }}</td>
            </tr>
            <tr class="bold-row">
                <td>Net Profit / (Loss)</td>
                <td class="num {{ $summary['totalRevenue'] - $summary['hotelExpenses'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ currency_format($summary['totalRevenue'] - $summary['hotelExpenses'], $currencyId) }}
                </td>
                <td class="num">
                    {{ currency_format($summary['totalCollected'] - $summary['hotelExpensesPaid'], $currencyId) }}
                </td>
                <td class="num">
                    {{ currency_format($summary['hotelOutstanding'] - $summary['hotelExpensesUnpaid'], $currencyId) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="section-label">Income Details (Reservations)</div>
    @if($detailedIncome->isEmpty())
        <p class="empty">No reservation income found for this period.</p>
    @else
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Reservation #</th>
                <th>Guest</th>
                <th>Room</th>
                <th>Room Charge</th>
                <th>Laundry</th>
                <th>Minibar</th>
                <th>Other Services</th>
                <th>Total Charges</th>
                <th>Paid Amount</th>
                <th>Unpaid Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $sumRoom = 0; $sumLaundry = 0; $sumMinibar = 0; $sumOther = 0;
                $sumTotal = 0; $sumPaid = 0; $sumUnpaid = 0;
            @endphp
            @foreach($detailedIncome as $res)
                @php
                    $roomNightAmt = (float)$res->charges->where('charge_type', \Modules\Hotel\Entities\RoomCharge::TYPE_ROOM_NIGHT)->sum('amount');
                    $laundryAmt = (float)$res->charges->where('charge_type', \Modules\Hotel\Entities\RoomCharge::TYPE_LAUNDRY)->sum('amount');
                    $minibarAmt = (float)$res->charges->where('charge_type', \Modules\Hotel\Entities\RoomCharge::TYPE_MINIBAR)->sum('amount');
                    $otherAmt = (float)$res->charges->whereNotIn('charge_type', [
                        \Modules\Hotel\Entities\RoomCharge::TYPE_ROOM_NIGHT,
                        \Modules\Hotel\Entities\RoomCharge::TYPE_LAUNDRY,
                        \Modules\Hotel\Entities\RoomCharge::TYPE_MINIBAR
                    ])->sum('amount');

                    $totalCharges = $roomNightAmt + $laundryAmt + $minibarAmt + $otherAmt;
                    $paid = (float)$res->paid_amount;
                    $unpaid = (float)$res->balance_due;

                    $sumRoom += $roomNightAmt;
                    $sumLaundry += $laundryAmt;
                    $sumMinibar += $minibarAmt;
                    $sumOther += $otherAmt;
                    $sumTotal += $totalCharges;
                    $sumPaid += $paid;
                    $sumUnpaid += $unpaid;
                @endphp
                <tr>
                    <td>{{ $res->check_in_date ? $res->check_in_date->format('Y-m-d') : '—' }}</td>
                    <td style="font-weight: 600;">{{ $res->reservation_number }}</td>
                    <td>{{ $res->guest?->name ?? '—' }}</td>
                    <td>{{ $res->room?->name ?? '—' }}</td>
                    <td class="num">{{ $roomNightAmt > 0 ? currency_format($roomNightAmt, $currencyId) : '—' }}</td>
                    <td class="num">{{ $laundryAmt > 0 ? currency_format($laundryAmt, $currencyId) : '—' }}</td>
                    <td class="num">{{ $minibarAmt > 0 ? currency_format($minibarAmt, $currencyId) : '—' }}</td>
                    <td class="num">{{ $otherAmt > 0 ? currency_format($otherAmt, $currencyId) : '—' }}</td>
                    <td class="num" style="font-weight: 600;">{{ currency_format($totalCharges, $currencyId) }}</td>
                    <td class="num" style="color: #059669; font-weight: 600;">{{ currency_format($paid, $currencyId) }}</td>
                    <td class="num" style="color: #dc2626;">{{ currency_format($unpaid, $currencyId) }}</td>
                    <td style="text-align: center;">
                        @if($unpaid <= 0)
                            <span class="status-badge status-paid">Paid</span>
                        @elseif($paid > 0)
                            <span class="status-badge status-partial">Partial</span>
                        @else
                            <span class="status-badge status-unpaid">Unpaid</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total">
                <td colspan="4">TOTAL</td>
                <td class="num">{{ currency_format($sumRoom, $currencyId) }}</td>
                <td class="num">{{ currency_format($sumLaundry, $currencyId) }}</td>
                <td class="num">{{ currency_format($sumMinibar, $currencyId) }}</td>
                <td class="num">{{ currency_format($sumOther, $currencyId) }}</td>
                <td class="num">{{ currency_format($sumTotal, $currencyId) }}</td>
                <td class="num">{{ currency_format($sumPaid, $currencyId) }}</td>
                <td class="num">{{ currency_format($sumUnpaid, $currencyId) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    @endif

    <div class="section-label">Expense Details</div>
    @if($detailedExpenses->isEmpty())
        <p class="empty">No expenses found for this period.</p>
    @else
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Department</th>
                <th>Vendor</th>
                <th>Status</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $sumExpenses = 0; @endphp
            @foreach($detailedExpenses as $expense)
                @php $sumExpenses += (float)$expense->amount; @endphp
                <tr>
                    <td>{{ $expense->expense_date->format('Y-m-d') }}</td>
                    <td style="font-weight: 600;">{{ $expense->title }}@if($expense->description) <span style="font-weight: normal; color: #6b7280;">- {{ $expense->description }}</span> @endif</td>
                    <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $expense->department) }}</td>
                    <td>{{ $expense->vendor ?? '—' }}</td>
                    <td style="text-transform: capitalize; text-align: center;">
                        @if($expense->status === 'paid')
                            <span class="status-badge status-paid">Paid</span>
                        @else
                            <span class="status-badge status-partial">Pending</span>
                        @endif
                    </td>
                    <td class="num" style="color: #dc2626; font-weight: 600;">{{ currency_format((float)$expense->amount, $currencyId) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total">
                <td colspan="5">TOTAL</td>
                <td class="num">{{ currency_format($sumExpenses, $currencyId) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif
</body>
</html>
