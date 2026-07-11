<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Income & Expense Report</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; color: #1f2937; margin: 0; padding: 12px 14px; background: #fff; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 8px; margin-bottom: 12px; }
        .header h1 { margin: 0 0 2px; font-size: 14px; color: #111827; font-weight: 700; }
        .header p { margin: 0; color: #4b5563; font-size: 8px; }
        
        .section-label { font-size: 9px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.5px; margin: 14px 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 2px; }
        
        /* Master Table */
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 4px; margin-bottom: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #e5e7eb; padding: 4px 6px; vertical-align: top; }
        table.data-table th { background: #f9fafb; font-size: 8px; text-transform: uppercase; color: #4b5563; text-align: left; font-weight: 700; }
        table.data-table td.num, table.data-table th.num { text-align: right; }
        table.data-table tr.total td { background: #f9fafb; font-weight: 700; }
        
        .sub-txt { color: #6b7280; font-size: 7.5px; margin-top: 1px; }
        .empty { color: #9ca3af; font-style: italic; font-size: 8px; padding: 6px 0; }
        
        .status-badge { font-weight: 700; text-transform: uppercase; font-size: 6.5px; padding: 1px 3px; border-radius: 2px; display: inline-block; }
        .status-paid { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .status-unpaid { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; }
        .status-partial { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
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
    <table class="data-table" style="width: 70%;">
        <thead>
            <tr>
                <th>Category</th>
                <th class="num">Total Amount</th>
                <th class="num">Paid</th>
                <th class="num">Unpaid</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight: 700;">Total Revenue (Sales)</td>
                <td class="num" style="font-weight: 700; color: #059669;">{{ currency_format($summary['totalRevenue'], $currencyId) }}</td>
                <td class="num">{{ currency_format($summary['totalCollected'], $currencyId) }}</td>
                <td class="num">{{ currency_format($summary['hotelOutstanding'], $currencyId) }}</td>
            </tr>
            <tr>
                <td style="font-weight: 700;">LESS: Expenses</td>
                <td class="num" style="font-weight: 700; color: #dc2626;">-{{ currency_format($summary['hotelExpenses'], $currencyId) }}</td>
                <td class="num">-{{ currency_format($summary['hotelExpensesPaid'], $currencyId) }}</td>
                <td class="num">-{{ currency_format($summary['hotelExpensesUnpaid'], $currencyId) }}</td>
            </tr>
            <tr class="total">
                <td>Net Profit / (Loss)</td>
                @php $netProfit = $summary['totalRevenue'] - $summary['hotelExpenses']; @endphp
                <td class="num" style="color: {{ $netProfit >= 0 ? '#059669' : '#dc2626' }}; font-weight: 900;">
                    {{ currency_format($netProfit, $currencyId) }}
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

    <div class="section-label">Detailed Revenue (Reservation Charges)</div>
    @if($detailedIncome->isEmpty())
        <p class="empty">No reservation income found for this period.</p>
    @else
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">Date</th>
                <th style="width: 25%;">Reservation / Guest</th>
                <th style="width: 10%;">Room</th>
                <th style="width: 30%;">Charge Details</th>
                <th class="num" style="width: 15%;">Amount</th>
                <th style="text-align: center; width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $sumTotal = 0; @endphp
            @foreach($detailedIncome as $charge)
                @php
                    $res = $charge->reservation;
                    $paid = $res ? (float)$res->paid_amount : 0;
                    $unpaid = $res ? (float)$res->balance_due : 0;
                    $sumTotal += (float)$charge->amount;
                @endphp
                <tr>
                    <td>{{ $charge->charge_date ? $charge->charge_date->format('Y-m-d') : ($res ? $res->check_in_date->format('Y-m-d') : '—') }}</td>
                    <td>
                        @if($res)
                            <div style="font-weight: 700;">{{ $res->reservation_number }}</div>
                            <div class="sub-txt">{{ $res->guest?->name ?? '—' }}</div>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $res->room?->name ?? '—' }}</td>
                    <td>
                        <div style="font-weight: 700;">
                            @if($charge->charge_type === \Modules\Hotel\Entities\RoomCharge::TYPE_ROOM_NIGHT)
                                Room Charge
                            @elseif($charge->charge_type === \Modules\Hotel\Entities\RoomCharge::TYPE_LAUNDRY)
                                Laundry
                            @elseif($charge->charge_type === \Modules\Hotel\Entities\RoomCharge::TYPE_MINIBAR)
                                Minibar
                            @else
                                {{ str_replace('_', ' ', $charge->charge_type) }}
                            @endif
                        </div>
                        @if($charge->description)
                            <div class="sub-txt">{{ $charge->getDisplayDescription() }}</div>
                        @endif
                    </td>
                    <td class="num" style="font-weight: 600;">{{ currency_format($charge->amount, $currencyId) }}</td>
                    <td style="text-align: center;">
                        @if($res)
                            @if($unpaid <= 0)
                                <span class="status-badge status-paid">Paid</span>
                            @elseif($paid > 0)
                                <span class="status-badge status-partial">Partial</span>
                            @else
                                <span class="status-badge status-unpaid">Unpaid</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="4">TOTAL</td>
                <td class="num">{{ currency_format($sumTotal, $currencyId) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    @endif

    <div class="section-label">Detailed Expenses</div>
    @if($detailedExpenses->isEmpty())
        <p class="empty">No expenses found for this period.</p>
    @else
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">Date</th>
                <th style="width: 35%;">Expense / Reference</th>
                <th style="width: 15%;">Category</th>
                <th class="num" style="width: 15%;">Total Amount</th>
                <th style="width: 15%;">Paid By</th>
                <th style="text-align: center; width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $sumExpenses = 0; @endphp
            @foreach($detailedExpenses as $expense)
                @php $sumExpenses += (float)$expense->amount; @endphp
                <tr>
                    <td>{{ $expense->expense_date->format('Y-m-d') }}</td>
                    <td>
                        <div style="font-weight: 700;">{{ $expense->receipt_number ?: 'EXP' . str_pad($expense->id, 6, '0', STR_PAD_LEFT) }}</div>
                        <div class="sub-txt">{{ $expense->title }}@if($expense->description) - {{ $expense->description }}@endif</div>
                    </td>
                    <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $expense->department) }}</td>
                    <td class="num" style="font-weight: 600;">{{ currency_format((float)$expense->amount, $currencyId) }}</td>
                    <td style="text-transform: capitalize;">{{ $expense->payment_method }}</td>
                    <td style="text-align: center;">
                        @if($expense->status === 'paid')
                            <span class="status-badge status-paid">Paid</span>
                        @else
                            <span class="status-badge status-partial">Pending</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="3">TOTAL</td>
                <td class="num">{{ currency_format($sumExpenses, $currencyId) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>
    @endif
</body>
</html>
