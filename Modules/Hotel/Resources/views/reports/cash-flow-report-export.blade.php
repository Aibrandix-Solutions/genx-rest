<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Cash Flow Report</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; color: #1f2937; margin: 0; padding: 12px 14px; background: #fff; }
        .header { border-bottom: 2px solid #057857; padding-bottom: 8px; margin-bottom: 12px; }
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
        .status-refund { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Cash Flow Report</h1>
        <p>
            @if($propertyName){{ $propertyName }} &bull; @endif
            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            &bull; Generated {{ now()->timezone(timezone())->format('d M Y, h:i A') }}
        </p>
    </div>

    <div class="section-label">Cash Flow Summary</div>
    <table class="data-table" style="width: 50%;">
        <thead>
            <tr>
                <th>Category</th>
                <th class="num">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight: 700;">Cash Inflow (Payments Received)</td>
                <td class="num" style="font-weight: 700; color: #059669;">{{ currency_format($summary['totalInflow'], $currencyId) }}</td>
            </tr>
            <tr>
                <td style="font-weight: 700;">LESS: Cash Outflow (Paid Expenses)</td>
                <td class="num" style="font-weight: 700; color: #dc2626;">-{{ currency_format($summary['totalOutflow'], $currencyId) }}</td>
            </tr>
            <tr class="total">
                <td>Net Cash Flow</td>
                @php $netCashFlow = $summary['totalInflow'] - $summary['totalOutflow']; @endphp
                <td class="num" style="color: {{ $netCashFlow >= 0 ? '#059669' : '#dc2626' }}; font-weight: 900;">
                    {{ currency_format($netCashFlow, $currencyId) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="section-label">Detailed Transactions (Debits & Credits)</div>
    @if($detailedInflow->isEmpty())
        <p class="empty">No transactions found for this period.</p>
    @else
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 25%;">Reservation / Guest</th>
                <th style="width: 15%;">Room</th>
                <th style="width: 25%;">Transaction Details</th>
                <th class="num" style="width: 10%;">Debit (Dr)</th>
                <th class="num" style="width: 10%;">Credit (Cr)</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $sumDebit = 0; 
                $sumCredit = 0;
            @endphp
            @foreach($detailedInflow as $row)
                @php
                    $sumDebit += (float)$row->debit;
                    $sumCredit += (float)$row->credit;
                @endphp
                <tr>
                    <td>{{ $row->date ? \Carbon\Carbon::parse($row->date)->format('Y-m-d h:i A') : '—' }}</td>
                    <td>
                        @if($row->reservation_number !== '—')
                            <div style="font-weight: 700;">{{ $row->reservation_number }}</div>
                            <div class="sub-txt">{{ $row->guest_name }}</div>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($row->room_number !== '—')
                            <div style="font-weight: 700;">Room {{ $row->room_number }}</div>
                            <div class="sub-txt">{{ $row->room_type }}</div>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <div style="font-size: 7.5px; font-weight: 700;">
                            {{ $row->description }}
                            <span class="status-badge" style="font-size: 5.5px; margin-left: 2px; padding: 0.5px 2px; background: #f3f4f6; color: #475569; border: 1px solid #e5e7eb;">{{ $row->type }}</span>
                        </div>
                    </td>
                    <td class="num" style="font-weight: 700; color: {{ $row->badge === 'refund' ? '#dc2626' : '#1f2937' }}">{{ $row->debit > 0 ? currency_format($row->debit, $currencyId) : '—' }}</td>
                    <td class="num" style="font-weight: 700; color: #059669;">{{ $row->credit > 0 ? currency_format($row->credit, $currencyId) : '—' }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="4">TOTAL</td>
                <td class="num">{{ currency_format($sumDebit, $currencyId) }}</td>
                <td class="num">{{ currency_format($sumCredit, $currencyId) }}</td>
            </tr>
            <tr class="total" style="background: #f3f4f6;">
                @php $unpaidBalance = $sumDebit - $sumCredit; @endphp
                <td colspan="4">Net Receivables / Unpaid Balance</td>
                <td colspan="2" class="num" style="color: {{ $unpaidBalance >= 0 ? '#d97706' : '#059669' }};">{{ currency_format($unpaidBalance, $currencyId) }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <div class="section-label">Detailed Expenses Paid (Cash Outflow)</div>
    @if($detailedOutflow->isEmpty())
        <p class="empty">No paid expenses found for this period.</p>
    @else
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 35%;">Expense / Reference</th>
                <th style="width: 20%;">Category</th>
                <th class="num" style="width: 10%;">Amount</th>
                <th style="width: 10%;">Paid By</th>
                <th style="text-align: center; width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $sumOutflow = 0; @endphp
            @foreach($detailedOutflow as $expense)
                @php
                    $sumOutflow += (float)$expense->amount;
                @endphp
                <tr>
                    <td>{{ $expense->expense_date->format('Y-m-d') }}</td>
                    <td>
                        <div style="font-weight: 700;">{{ $expense->receipt_number ?: 'EXP' . str_pad($expense->id, 6, '0', STR_PAD_LEFT) }}</div>
                        <div class="sub-txt">{{ $expense->title }}@if($expense->description) - {{ $expense->description }}@endif</div>
                    </td>
                    <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $expense->department) }}</td>
                    <td class="num" style="font-weight: 700;">{{ currency_format($expense->amount, $currencyId) }}</td>
                    <td style="text-transform: capitalize;">{{ $expense->payment_method }}</td>
                    <td style="text-align: center;"><span class="status-badge status-paid">Paid</span></td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="3">TOTAL</td>
                <td class="num">{{ currency_format($sumOutflow, $currencyId) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>
    @endif
</body>
</html>
