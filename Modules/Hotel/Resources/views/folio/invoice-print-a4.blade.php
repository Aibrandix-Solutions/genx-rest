<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ isRtl() ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $hotelName }} - {{ $reservation->reservation_number }}</title>
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

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-size: 12px;
            line-height: 1.45;
        }

        body {
            padding: 12mm;
        }

        .invoice {
            max-width: 190mm;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 2px solid #333;
        }

        .header-left {
            flex: 1 1 auto;
            min-width: 0;
        }

        .header-right {
            flex: 0 0 auto;
            text-align: right;
        }

        [dir="rtl"] .header-right {
            text-align: left;
        }

        .hotel-logo {
            max-width: 80px;
            max-height: 48px;
            margin-bottom: 8px;
            object-fit: contain;
            display: block;
        }

        .hotel-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .hotel-info {
            font-size: 11px;
            color: #444;
            margin-bottom: 2px;
        }

        .doc-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .meta-line {
            font-size: 11px;
            color: #444;
            margin-bottom: 2px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 22px;
        }

        [dir="rtl"] .info-grid-right {
            text-align: left;
        }

        .info-grid-right {
            text-align: right;
        }

        .section-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #666;
            margin-bottom: 8px;
        }

        .guest-name {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 2px;
        }

        .info-line {
            font-size: 11px;
            color: #444;
            margin-bottom: 2px;
        }

        .stay-line {
            font-size: 11px;
            margin-bottom: 3px;
        }

        .stay-line .label {
            color: #666;
        }

        .stay-line .value {
            font-weight: bold;
        }

        .table-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #666;
            margin-bottom: 8px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.data-table thead tr {
            border-bottom: 2px solid #333;
        }

        table.data-table th {
            padding: 8px 6px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #444;
            text-align: left;
        }

        [dir="rtl"] table.data-table th {
            text-align: right;
        }

        table.data-table th.text-center {
            text-align: center;
        }

        table.data-table th.text-right {
            text-align: right;
        }

        [dir="rtl"] table.data-table th.text-right {
            text-align: left;
        }

        table.data-table td {
            padding: 8px 6px;
            font-size: 11px;
            vertical-align: top;
            border-bottom: 1px solid #ddd;
        }

        table.data-table td.text-center {
            text-align: center;
        }

        table.data-table td.text-right {
            text-align: right;
            white-space: nowrap;
        }

        [dir="rtl"] table.data-table td.text-right {
            text-align: left;
        }

        table.data-table td.muted {
            color: #999;
        }

        table.data-table td.charge-label {
            font-weight: 600;
        }

        table.data-table td.charge-meta {
            font-size: 10px;
            color: #666;
            font-weight: normal;
        }

        table.data-table tfoot td {
            border-top: 2px solid #333;
            border-bottom: none;
            font-weight: bold;
            padding-top: 10px;
        }

        .totals-wrap {
            display: flex;
            justify-content: flex-end;
            margin-top: 8px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
        }

        [dir="rtl"] .totals-wrap {
            justify-content: flex-start;
        }

        .totals {
            width: 240px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 6px;
            font-size: 12px;
        }

        .totals-row.paid {
            color: #166534;
        }

        .totals-row.balance {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 2px solid #333;
            font-size: 16px;
            font-weight: bold;
        }

        .refund {
            color: #b91c1c;
        }

        .footer {
            margin-top: 36px;
            padding-top: 16px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        @page {
            size: A4;
            margin: 10mm;
        }

        @media print {
            body {
                padding: 0;
            }

            .invoice {
                max-width: none;
            }
        }
    </style>
</head>

<body>
    <div class="invoice">
        <div class="header">
            <div class="header-left">
                @if($hotelLogo)
                    <img src="{{ asset_url_local_s3('hotel-logo/' . $hotelLogo) }}" alt="{{ $hotelName }}" class="hotel-logo">
                @endif
                <div class="hotel-name">{{ $hotelName }}</div>
                @if($hotelAddress)
                    <div class="hotel-info">{!! nl2br(e($hotelAddress)) !!}</div>
                @endif
                @if($hotelPhone)
                    <div class="hotel-info">@lang('modules.customer.phone'): <span dir="ltr" style="unicode-bidi: embed;">{{ $hotelPhone }}</span></div>
                @endif
            </div>
            <div class="header-right">
                <div class="doc-title">@lang('hotel::modules.invoice.title')</div>
                <div class="meta-line">@lang('hotel::modules.invoice.resNumber'): {{ $reservation->reservation_number }}</div>
                <div class="meta-line">@lang('hotel::modules.folio.date'): {{ now()->format('d M Y') }}</div>
            </div>
        </div>

        <div class="info-grid">
            <div>
                <div class="section-label">@lang('hotel::modules.invoice.guestDetails')</div>
                <div class="guest-name">{{ $reservation->guest->full_name }}</div>
                @if($reservation->guest->email)
                    <div class="info-line">{{ $reservation->guest->email }}</div>
                @endif
                @if($reservation->guest->phone)
                    <div class="info-line" dir="ltr" style="unicode-bidi: embed;">{{ $reservation->guest->phone }}</div>
                @endif
            </div>
            <div class="info-grid-right">
                <div class="section-label">@lang('hotel::modules.invoice.stayInfo')</div>
                @if($reservation->group_booking_id && in_array($viewMode, ['consolidated', 'roomwise']))
                    @php
                        $resIds = \Modules\Hotel\Entities\Reservation::where('group_booking_id', $reservation->group_booking_id)->pluck('id');
                        $rooms = \Modules\Hotel\Entities\Room::whereIn('id', function ($q) use ($resIds) {
                            $q->select('room_id')->from('hotel_reservations')->whereIn('id', $resIds);
                        })->with('roomType')->get();
                    @endphp
                    <div class="stay-line">
                        <span class="label">@lang('hotel::modules.reservation.room'):</span>
                        <span class="value">{{ $rooms->map(fn ($r) => $r->room_number . ' (' . ($r->roomType->name ?? '') . ')')->implode(', ') }}</span>
                    </div>
                @else
                    <div class="stay-line">
                        <span class="label">@lang('hotel::modules.reservation.room'):</span>
                        <span class="value">{{ $reservation->room->room_number ?? '—' }} ({{ $reservation->room->roomType->name ?? '' }})</span>
                    </div>
                @endif
                <div class="stay-line">
                    <span class="label">@lang('hotel::modules.reservation.checkIn'):</span>
                    <span class="value">{{ $reservation->check_in_date->format('d M Y') }}</span>
                </div>
                <div class="stay-line">
                    <span class="label">@lang('hotel::modules.reservation.checkOut'):</span>
                    <span class="value">{{ $reservation->checkout_date->format('d M Y') }}</span>
                </div>
                <div class="stay-line">
                    <span class="label">@lang('hotel::modules.folio.nights'):</span>
                    <span class="value">{{ $reservation->getNumberOfNights() }}</span>
                </div>
            </div>
        </div>

        <div class="table-title">@lang('hotel::modules.folio.charges')</div>
        @php
            $roomGroups = $folioSummary['room_groups'] ?? [];
            $typeRows = $folioSummary['type_rows'] ?? [];
            $subtotal = $folioSummary['subtotal'] ?? 0;
            $hasRows = count($roomGroups) > 0 || count($typeRows) > 0;
        @endphp
        <table class="data-table">
            <thead>
                <tr>
                    <th>@lang('hotel::modules.folio.date')</th>
                    <th>@lang('hotel::modules.folio.roomNo')</th>
                    <th class="text-center">@lang('hotel::modules.folio.noOfNights')</th>
                    <th class="text-right">@lang('hotel::modules.folio.pricePerNight')</th>
                    <th>@lang('hotel::modules.folio.otherCharges')</th>
                    <th class="text-right">@lang('hotel::modules.folio.total')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roomGroups as $group)
                    @php
                        $dateLabel = $group['start_date']->equalTo($group['end_date'])
                            ? $group['start_date']->format('d M Y')
                            : $group['start_date']->format('d M') . ' – ' . $group['end_date']->format('d M Y');
                    @endphp
                    <tr>
                        <td>{{ $dateLabel }}</td>
                        <td>{{ $group['room_number'] }}</td>
                        <td class="text-center">{{ $group['nights'] }}</td>
                        <td class="text-right">{{ currency_format($group['price_per_night'], restaurant()->currency_id) }}</td>
                        <td class="muted">—</td>
                        <td class="text-right">{{ currency_format($group['room_total'], restaurant()->currency_id) }}</td>
                    </tr>
                @endforeach

                @foreach($typeRows as $typeRow)
                    <tr>
                        <td>{{ $typeRow['date']?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $typeRow['room_number'] ?? '—' }}</td>
                        <td class="text-center muted">—</td>
                        <td class="text-right muted">—</td>
                        <td>
                            <span class="charge-label">{{ $typeRow['label'] }}</span>
                            <span class="charge-meta"> ({{ $typeRow['charge_count'] }} {{ trans_choice('hotel::modules.folio.chargeItems', $typeRow['charge_count']) }})</span>
                        </td>
                        <td class="text-right">{{ currency_format($typeRow['amount'], restaurant()->currency_id) }}</td>
                    </tr>
                @endforeach

                @if(!$hasRows)
                    <tr>
                        <td colspan="6">@lang('hotel::modules.folio.noCharges')</td>
                    </tr>
                @endif
            </tbody>
            @if($hasRows)
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right">@lang('hotel::modules.folio.subtotal')</td>
                        <td class="text-right">{{ currency_format($subtotal, restaurant()->currency_id) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>

        @if($payments->isNotEmpty())
            <div class="table-title">@lang('hotel::modules.invoice.paymentsReceived')</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>@lang('hotel::modules.folio.date')</th>
                        <th>@lang('hotel::modules.folio.method')</th>
                        <th>@lang('hotel::modules.folio.type')</th>
                        <th class="text-right">@lang('hotel::modules.folio.amount')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td>{{ $payment->created_at->format('d/m/Y') }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td>{{ ucfirst($payment->payment_type) }}</td>
                            <td class="text-right {{ $payment->payment_type === 'refund' ? 'refund' : '' }}">
                                {{ $payment->payment_type === 'refund' ? '-' : '' }}{{ currency_format($payment->amount, restaurant()->currency_id) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="totals-wrap">
            <div class="totals">
                <div class="totals-row">
                    <span>@lang('hotel::modules.folio.totalCharges')</span>
                    <span>{{ currency_format($totalCharges, restaurant()->currency_id) }}</span>
                </div>
                <div class="totals-row paid">
                    <span>@lang('hotel::modules.folio.totalPaid')</span>
                    <span>- {{ currency_format($totalPayments, restaurant()->currency_id) }}</span>
                </div>
                <div class="totals-row balance">
                    <span>@lang('hotel::modules.folio.balanceDue')</span>
                    <span>{{ currency_format($balance, restaurant()->currency_id) }}</span>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>@lang('hotel::modules.invoice.thankYou')</p>
        </div>
    </div>

    <script>
        (function () {
            function closePrintTab() {
                window.close();

                if (!window.closed && window.opener && !window.opener.closed) {
                    try {
                        window.opener.focus();
                    } catch (e) {
                        // noop
                    }
                }
            }

            window.addEventListener('afterprint', closePrintTab);

            window.onload = function () {
                window.print();
            };
        })();
    </script>
</body>

</html>
