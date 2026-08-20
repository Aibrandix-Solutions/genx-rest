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
        }

        body {
            display: block;
            padding: 4mm;
        }

        .receipt {
            width: {{ ($width ?? 80) - 5 }}mm;
            max-width: 100%;
            padding: {{ ($thermal ?? true) ? '3mm' : '6.35mm' }};
            margin: 0 auto;
            page-break-after: always;
        }

        .header {
            text-align: center;
            margin-bottom: 3mm;
        }

        .hotel-logo {
            max-width: 18mm;
            max-height: 12mm;
            margin: 0 auto 1mm;
            object-fit: contain;
            display: block;
        }

        .hotel-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .hotel-info {
            font-size: 8pt;
            margin-bottom: 0.5mm;
            line-height: 1.3;
        }

        .doc-title {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 2mm;
        }

        .section {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 2mm 1mm;
            margin-bottom: 3mm;
            font-size: 9pt;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 2mm;
            margin-bottom: 1mm;
            line-height: 1.35;
        }

        .summary-row .label {
            flex: 1 1 auto;
            min-width: 0;
            word-break: break-word;
        }

        .summary-row .value {
            flex: 0 0 auto;
            white-space: nowrap;
            text-align: right;
        }

        [dir="rtl"] .summary-row .value {
            text-align: left;
        }

        .section-title {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 1.5mm;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
            font-size: 8pt;
            table-layout: fixed;
        }

        .items-table th {
            padding: 1mm;
            border-bottom: 1px solid #000;
            vertical-align: bottom;
        }

        .items-table td {
            padding: 1mm;
            vertical-align: top;
            word-break: break-word;
        }

        .col-desc {
            width: 58%;
        }

        .col-amt {
            width: 42%;
            text-align: right;
        }

        [dir="rtl"] .col-amt {
            text-align: left;
        }

        .item-meta {
            font-size: 7pt;
            color: #000;
            line-height: 1.25;
        }

        .summary {
            font-size: 9pt;
            margin-top: 2mm;
        }

        .summary-row.secondary {
            font-size: 8pt;
            margin-bottom: 0.5mm;
        }

        .total {
            font-weight: bold;
            font-size: 11pt;
            border-top: 1px solid #000;
            padding-top: 1mm;
            margin-top: 1mm;
        }

        .footer {
            text-align: center;
            margin-top: 3mm;
            font-size: 9pt;
            padding-top: 2mm;
            border-top: 1px dashed #000;
        }

        .refund {
            color: #000;
        }

        .receipt,
        .receipt *:not(img):not(svg) {
            color: #000 !important;
        }

        @media print {
            html,
            body {
                margin: 0;
                padding: 0;
                background: #fff;
            }

            .receipt {
                margin: 0 auto;
                padding: 3mm;
            }

            @page {
                margin: 2mm;
                size: {{ $width ?? 80 }}mm auto;
            }
        }
    </style>
</head>

<body>
    <div class="receipt">
        <div class="header">
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
            <div class="doc-title">@lang('hotel::modules.invoice.title')</div>
        </div>

        <div class="section">
            <div class="summary-row">
                <span class="label">@lang('hotel::modules.invoice.resNumber')</span>
                <span class="value">{{ $reservation->reservation_number }}</span>
            </div>
            <div class="summary-row">
                <span class="label">@lang('hotel::modules.folio.date')</span>
                <span class="value">{{ now()->format('d M Y') }}</span>
            </div>
            <div class="summary-row">
                <span class="label">@lang('hotel::modules.invoice.guestDetails')</span>
                <span class="value">{{ $reservation->guest->full_name }}</span>
            </div>
            @if($reservation->guest->phone)
                <div class="summary-row">
                    <span class="label">@lang('modules.customer.phone')</span>
                    <span class="value" dir="ltr" style="unicode-bidi: embed;">{{ $reservation->guest->phone }}</span>
                </div>
            @endif
            @if($reservation->group_booking_id && in_array($viewMode, ['consolidated', 'roomwise']))
                @php
                    $resIds = \Modules\Hotel\Entities\Reservation::where('group_booking_id', $reservation->group_booking_id)->pluck('id');
                    $rooms = \Modules\Hotel\Entities\Room::whereIn('id', function ($q) use ($resIds) {
                        $q->select('room_id')->from('hotel_reservations')->whereIn('id', $resIds);
                    })->with('roomType')->get();
                @endphp
                <div class="summary-row">
                    <span class="label">@lang('hotel::modules.reservation.room')</span>
                    <span class="value">{{ $rooms->map(fn ($r) => $r->room_number . ' (' . ($r->roomType->name ?? '') . ')')->implode(', ') }}</span>
                </div>
            @else
                <div class="summary-row">
                    <span class="label">@lang('hotel::modules.reservation.room')</span>
                    <span class="value">{{ $reservation->room->room_number ?? '—' }} ({{ $reservation->room->roomType->name ?? '' }})</span>
                </div>
            @endif
            <div class="summary-row">
                <span class="label">@lang('hotel::modules.reservation.checkIn')</span>
                <span class="value">{{ $reservation->check_in_date->format('d M Y') }}</span>
            </div>
            <div class="summary-row">
                <span class="label">@lang('hotel::modules.reservation.checkOut')</span>
                <span class="value">{{ $reservation->checkout_date->format('d M Y') }}</span>
            </div>
            <div class="summary-row">
                <span class="label">@lang('hotel::modules.folio.nights')</span>
                <span class="value">{{ $reservation->getNumberOfNights() }}</span>
            </div>
        </div>

        <div class="section-title">@lang('hotel::modules.folio.charges')</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-desc">@lang('hotel::modules.folio.description')</th>
                    <th class="col-amt">@lang('hotel::modules.folio.amount')</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $roomGroups = $folioSummary['room_groups'] ?? [];
                    $typeRows = $folioSummary['type_rows'] ?? [];
                @endphp

                @foreach($roomGroups as $group)
                    @php
                        $dateLabel = $group['start_date']->equalTo($group['end_date'])
                            ? $group['start_date']->format('d M Y')
                            : $group['start_date']->format('d M') . ' – ' . $group['end_date']->format('d M Y');
                    @endphp
                    <tr>
                        <td class="col-desc">
                            @lang('hotel::modules.folio.roomNo') {{ $group['room_number'] }}
                            <div class="item-meta">
                                {{ $dateLabel }} · {{ $group['nights'] }} {{ Str::plural('night', $group['nights']) }}
                                @ {{ currency_format($group['price_per_night'], restaurant()->currency_id) }}/night
                            </div>
                        </td>
                        <td class="col-amt">{{ currency_format($group['room_total'], restaurant()->currency_id) }}</td>
                    </tr>
                @endforeach

                @foreach($typeRows as $typeRow)
                    <tr>
                        <td class="col-desc">
                            {{ $typeRow['label'] }}
                            @if(!empty($typeRow['date']))
                                <div class="item-meta">{{ $typeRow['date']->format('d M Y') }}</div>
                            @endif
                        </td>
                        <td class="col-amt">{{ currency_format($typeRow['amount'], restaurant()->currency_id) }}</td>
                    </tr>
                @endforeach

                @if(empty($roomGroups) && empty($typeRows))
                    <tr>
                        <td colspan="2" class="col-desc">@lang('hotel::modules.folio.noCharges')</td>
                    </tr>
                @endif
            </tbody>
        </table>

        @if($payments->isNotEmpty())
            <div class="section-title">@lang('hotel::modules.invoice.paymentsReceived')</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="col-desc">@lang('hotel::modules.folio.method')</th>
                        <th class="col-amt">@lang('hotel::modules.folio.amount')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td class="col-desc">
                                {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                <div class="item-meta">
                                    {{ $payment->created_at->format('d M Y') }} · {{ ucfirst($payment->payment_type) }}
                                </div>
                            </td>
                            <td class="col-amt {{ $payment->payment_type === 'refund' ? 'refund' : '' }}">
                                {{ $payment->payment_type === 'refund' ? '-' : '' }}{{ currency_format($payment->amount, restaurant()->currency_id) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="summary">
            <div class="summary-row secondary">
                <span>@lang('hotel::modules.folio.totalCharges')</span>
                <span>{{ currency_format($totalCharges, restaurant()->currency_id) }}</span>
            </div>
            <div class="summary-row secondary">
                <span>@lang('hotel::modules.folio.totalPaid')</span>
                <span>- {{ currency_format($totalPayments, restaurant()->currency_id) }}</span>
            </div>
            <div class="summary-row total">
                <span>@lang('hotel::modules.folio.balanceDue')</span>
                <span>{{ currency_format($balance, restaurant()->currency_id) }}</span>
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
