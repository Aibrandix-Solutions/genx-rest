<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ isRtl() ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ restaurant()->name }} - {{ $order->show_formatted_order_number }}</title>
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
            color: #000;
        }

        .receipt {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .restaurant-logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 10px;
            display: block;
        }

        .restaurant-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .restaurant-info {
            font-size: 12px;
            margin-bottom: 3px;
            color: #000;
        }

        .order-info {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }

        .order-info h3 {
            margin-bottom: 10px;
            color: #000;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
        }

        .info-label {
            font-weight: bold;
            color: #000;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }

        .items-table th {
            background-color: #f5f5f5;
            padding: 10px;
            border: 1px solid #ddd;
            font-weight: bold;
            text-align: left;
        }

        .items-table td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .qty {
            width: 8%;
            text-align: center;
        }

        .description {
            width: 50%;
        }

        .price {
            width: 20%;
            text-align: right;
        }

        .amount {
            width: 22%;
            text-align: right;
        }

        .modifiers {
            font-size: 10px;
            color: #000;
            margin-top: 3px;
        }
        .combo-component {
            font-size: 10px;
            color: #000;
            margin-top: 2px;
            padding-left: 8px;
        }

        .combo-items {
            margin-top: 4px;
            font-size: 10px;
            color: #444;
        }

        .combo-items div {
            margin-top: 2px;
            padding-left: 8px;
        }

        .summary {
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f9f9f9;
            text-align: right;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 3px 0;
        }

        .summary-row.secondary {
            font-size: 10px;
            color: #000;
            margin-bottom: 3px;
            padding-left: 20px;
        }

        .total {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #000;
        }

        .qr_code {
            margin: 15px 0;
            text-align: center;
        }

        .qr_code img {
            max-width: 150px;
            height: auto;
        }

        .payment-details {
            margin-top: 15px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .payment-details h4 {
            margin-bottom: 10px;
            color: #000;
        }

        .receipt,
        .receipt *:not(img):not(svg) {
            color: #000 !important;
        }

        @media print {
            body {
                font-size: 11px;
            }

            .receipt {
                max-width: none;
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="receipt">
        <div class="header">
            @if ($receiptSettings->show_restaurant_logo)
                <img src="{{ restaurant()->logo_url }}" alt="{{ restaurant()->name }}" class="restaurant-logo">
            @endif
            <div class="restaurant-name">{{ restaurant()->name }}</div>
            <div class="restaurant-info">{{ branch()->address }}</div>
            <div class="restaurant-info">@lang('modules.customer.phone'): {{ restaurant()->phone_number }}</div>
            @if ($receiptSettings->show_tax)
                @foreach ($taxDetails as $taxDetail)
                    <div class="restaurant-info">{{ $taxDetail->tax_name }}: {{ $taxDetail->tax_id }}</div>
                @endforeach
            @endif
        </div>

        <div class="order-info">
            <h3>@lang('modules.settings.orderDetails')</h3>
            <div class="info-grid">
                <div class="info-item">

                    <span>
                        @if(!isOrderPrefixEnabled())
                            <span class="info-label">@lang('modules.order.orderNumber'):</span>
                            <span>#{{ $order->order_number }}</span>
                        @else
                            {{ $order->formatted_order_number }}
                        @endif
                    </span>
                </div>
                @php
                    $tokenNumber = $order->kot->whereNotNull('token_number')->first()?->token_number;
                @endphp
                @if ($tokenNumber)
                    <div class="info-item">
                        <span class="info-label">@lang('modules.order.tokenNumber'):</span>
                        <span>{{ $tokenNumber }}</span>
                    </div>
                @endif
                <div class="info-item">
                    <span class="info-label">@lang('app.dateTime'):</span>
                    <span>{{ $order->date_time->timezone(timezone())->translatedFormat('d M Y H:i') }}</span>
                </div>
                @if ($receiptSettings->show_table_number && $order->table && $order->table->table_code)
                    <div class="info-item">
                        <span class="info-label">@lang('modules.settings.tableNumber'):</span>
                        <span>{{ $order->table->table_code }}</span>
                    </div>
                @endif
                @if ($receiptSettings->show_total_guest && $order->number_of_pax)
                    <div class="info-item">
                        <span class="info-label">@lang('modules.order.noOfPax'):</span>
                        <span>{{ $order->number_of_pax }}</span>
                    </div>
                @endif
                @if ($receiptSettings->show_waiter && $order->waiter && $order->waiter->name)
                    <div class="info-item">
                        <span class="info-label">@lang('modules.order.waiter'):</span>
                        <span>{{ $order->waiter->name }}</span>
                    </div>
                @endif
                @if ($receiptSettings->show_customer_name && $order->customer && $order->customer->name)
                    <div class="info-item">
                        <span class="info-label">@lang('modules.customer.customer'):</span>
                        <span>{{ $order->customer->name }}</span>
                    </div>
                @endif
                @if ($receiptSettings->show_customer_address && $order->customer && $order->customer->delivery_address)
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <span class="info-label">@lang('modules.customer.customerAddress'):</span>
                        <span>{{ $order->customer->delivery_address }}</span>
                    </div>
                @endif
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th class="qty">@lang('modules.order.qty')</th>
                    <th class="description">@lang('modules.menu.itemName')</th>
                    <th class="price">
                        @lang('modules.order.price')
                        @if($receiptSettings->show_currency_prefix)
                            ({{ restaurant()->currency->currency_code }})
                        @endif
                    </th>
                    <th class="amount">
                        @lang('modules.order.amount')
                        @if($receiptSettings->show_currency_prefix)
                            ({{ restaurant()->currency->currency_code }})
                        @endif
                    </th>
                </tr>
            </thead>
            <tbody>
                @php
                    $renderedComboGroups = [];
                    $comboInstanceItems = [];
                    $comboInstanceToStructure = [];
                    $comboStructureMeta = [];

                    foreach ($order->items as $comboCandidate) {
                        $candidateIsCombo = (bool) ($comboCandidate->is_combo_item ?? false) && !is_null($comboCandidate->combo_pack_id);
                        if (!$candidateIsCombo) {
                            continue;
                        }

                        $candidateInstanceKey = null;
                        if (preg_match('/\[COMBO_INSTANCE:([^\]]+)\]/', (string) $comboCandidate->note, $matches) && !empty($matches[1])) {
                            $candidateInstanceKey = trim((string) $matches[1]);
                        }

                        $instanceBucketKey = ((int) $comboCandidate->combo_pack_id) . '::' . ($candidateInstanceKey ?: ('legacy-item-' . (int) $comboCandidate->id));
                        if (!isset($comboInstanceItems[$instanceBucketKey])) {
                            $comboInstanceItems[$instanceBucketKey] = collect();
                        }
                        $comboInstanceItems[$instanceBucketKey]->push($comboCandidate);
                    }

                    foreach ($comboInstanceItems as $instanceBucketKey => $bucketItems) {
                        $firstComboItem = $bucketItems->first();
                        if (!$firstComboItem) {
                            continue;
                        }

                        $structureParts = $bucketItems->map(function ($entry) {
                            return implode('|', [
                                (int) ($entry->menu_item_id ?? 0),
                                (int) ($entry->menu_item_variation_id ?? 0),
                                (int) ($entry->quantity ?? 1),
                            ]);
                        })->sort()->values()->all();

                        $structureKey = ((int) $firstComboItem->combo_pack_id) . '::' . md5(json_encode($structureParts));
                        $comboInstanceToStructure[$instanceBucketKey] = $structureKey;

                        if (!isset($comboStructureMeta[$structureKey])) {
                            $comboStructureMeta[$structureKey] = [
                                'pack_count' => 0,
                                'total_amount' => 0,
                                'display_items' => $bucketItems,
                            ];
                        }

                        $comboStructureMeta[$structureKey]['pack_count']++;
                        $comboStructureMeta[$structureKey]['total_amount'] += (float) $bucketItems->sum('amount');
                    }
                @endphp

                @foreach ($order->items as $item)
                    @php
                        $isComboItem = (bool) ($item->is_combo_item ?? false) && !is_null($item->combo_pack_id);
                        $instanceKey = null;
                        if ($isComboItem && preg_match('/\[COMBO_INSTANCE:([^\]]+)\]/', (string) $item->note, $matches) && !empty($matches[1])) {
                            $instanceKey = trim((string) $matches[1]);
                        }
                        $instanceBucketKey = $isComboItem
                            ? (((int) ($item->combo_pack_id ?? 0)) . '::' . ($instanceKey ?: ('legacy-item-' . (int) $item->id)))
                            : null;
                        $comboGroupKey = $isComboItem
                            ? ($comboInstanceToStructure[$instanceBucketKey] ?? $instanceBucketKey)
                            : null;
                    @endphp

                    @if ($isComboItem)
                        @continue(in_array($comboGroupKey, $renderedComboGroups, true))
                        @php
                            $renderedComboGroups[] = $comboGroupKey;
                            $comboMeta = $comboStructureMeta[$comboGroupKey] ?? null;
                            $comboPackCount = (int) ($comboMeta['pack_count'] ?? 1);
                            $comboAmount = (float) ($comboMeta['total_amount'] ?? $item->amount);
                            $comboUnitPrice = $comboPackCount > 0 ? ($comboAmount / $comboPackCount) : $comboAmount;
                            $comboDisplayItems = $comboMeta['display_items'] ?? collect([$item]);
                            $comboPackName = optional($item->comboPack)->getTranslation('name', app()->getLocale()) ?? optional($item->comboPack)->name ?? __('modules.combo.comboPack');
                        @endphp
                        <tr>
                            <td class="qty">{{ $comboPackCount }}</td>
                            <td class="description">
                                <strong>{{ $comboPackName }}</strong>
                                <div class="combo-items">
                                    @foreach ($comboDisplayItems as $comboItem)
                                        @php
                                            $baseQty = (int) ($comboItem->quantity ?? 1);
                                            $displayQty = $baseQty * max(1, $comboPackCount);
                                        @endphp
                                        <div>
                                            - {{ $displayQty }}
                                            {{ $comboItem->menuItem->item_name ?? __('app.item') }}
                                            @if ($comboItem->menuItemVariation)
                                                ({{ $comboItem->menuItemVariation->variation }})
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="price">{{ currency_format_for_receipt_item($comboUnitPrice, restaurant()->currency_id) }}</td>
                            <td class="amount">{{ currency_format_for_receipt_item($comboAmount, restaurant()->currency_id) }}</td>
                        </tr>
                        @continue
                    @endif

                    <tr>
                        <td class="qty">{{ $item->quantity }}</td>
                        <td class="description">
                            <strong>{{ $item->menuItem->item_name }}</strong>
                            @if (isset($item->menuItemVariation))
                                <br><small>({{ $item->menuItemVariation->variation }})</small>
                            @endif
                            @foreach ($item->modifierOptions as $modifier)
                                @php
                                    $modifierQty = (int) ($modifier->pivot->quantity ?? 1);
                                    $modifierLinePrice = ($modifier->price ?? 0) * max(1, $modifierQty);
                                @endphp
                                <div class="modifiers">• {{ $modifier->name }}
                                    @if($modifierQty > 1)
                                        ×{{ $modifierQty }}
                                    @endif
                                    @if($modifierLinePrice > 0)
                                        (+{{ currency_format($modifierLinePrice, restaurant()->currency_id, false, true) }})
                                    @endif
                                </div>
                            @endforeach
                            @if($item->note)
                                <div class="modifiers"><em>@lang('modules.order.note'): {{ $item->note }}</em></div>
                            @endif
                        </td>
                    <td class="price">{{ currency_format_for_receipt_item($item->price, restaurant()->currency_id) }}</td>
                    <td class="amount">{{ currency_format_for_receipt_item($item->amount, restaurant()->currency_id) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            @php
                $extrasTotal = (float) ($order->extras?->sum('amount') ?? 0);
                $chargeTaxBase = max(0, $order->sub_total + $extrasTotal - ($order->discount_amount ?? 0));
            @endphp
            <div class="summary-row">
                <span>@lang('modules.order.subTotal'):</span>
                <span>  {{ currency_format($order->sub_total, restaurant()->currency_id, false, true) }} </span>
            </div>

            @if(($order->extras?->count() ?? 0) > 0)
                @foreach ($order->extras as $extra)
                    @if(($extra->amount ?? 0) > 0 || $extra->note)
                        <div class="summary-row">
                            <span>{{ $extra->note ?: 'Extra' }}:</span>
                            <span>{{ currency_format($extra->amount, restaurant()->currency_id, false, true) }}</span>
                        </div>
                    @endif
                @endforeach
            @endif

            @if (!is_null($order->discount_amount))
                <div class="summary-row">
                    <span>@lang('modules.order.discount')
                        @if ($order->discount_type == 'percent')
                            ({{ rtrim(rtrim($order->discount_value, '0'), '.') }}%)
                        @endif:
                    </span>
                    <span>-{{ currency_format($order->discount_amount, restaurant()->currency_id, false, true) }}</span>
                </div>
            @endif

            @if ($order->reward_point_discount > 0 && in_array('Reward Point', restaurant_modules()))
                <div class="summary-row">
                    <span>@lang('modules.reward.discountFromPoints') ({{ $order->reward_points_redeemed }} pts):</span>
                    <span>-{{ currency_format($order->reward_point_discount, restaurant()->currency_id, false, true) }}</span>
                </div>
            @endif

            @foreach ($order->charges as $item)
                <div class="summary-row">
                    <span>{{ $item->charge->charge_name }}
                        @if ($item->charge->charge_type == 'percent')
                            ({{ $item->charge->charge_value }}%)
                        @endif:
                    </span>
                    <span>{{ currency_format(($item->charge->getAmount($chargeTaxBase)), restaurant()->currency_id, true, true) }}</span>
                </div>
            @endforeach

            @if ($order->tip_amount > 0)
                <div class="summary-row">
                    <span>@lang('modules.order.tip'):</span>
                    <span>{{ currency_format($order->tip_amount, restaurant()->currency_id, false, true) }}</span>
                </div>
            @endif

            @if ($order->order_type === 'delivery' && !is_null($order->delivery_fee))
                <div class="summary-row">
                    <span>@lang('modules.delivery.deliveryFee'):</span>
                    <span>
                        @if($order->delivery_fee > 0)
                            {{ currency_format($order->delivery_fee, restaurant()->currency_id, false, true) }}
                        @else
                            @lang('modules.delivery.freeDelivery')
                        @endif
                    </span>
                </div>
            @endif

            @if ($taxMode == 'order')
                @foreach ($order->taxes as $item)
                    <div class="summary-row">
                        <span>{{ $item->tax->tax_name }} ({{ $item->tax->tax_percent }}%):</span>
                        <span>{{ currency_format(($item->tax->tax_percent / 100) * ($chargeTaxBase), restaurant()->currency_id, false, true) }}</span>
                    </div>
                @endforeach
            @else
                @if($order->total_tax_amount > 0)
                    @php
                        $taxTotals = [];
                        $totalTax = 0;
                        foreach ($order->items as $item) {
                            $qty = $item->quantity ?? 1;
                            $taxBreakdown = is_array($item->tax_breakup) ? $item->tax_breakup : (json_decode($item->tax_breakup, true) ?? []);
                            foreach ($taxBreakdown as $taxName => $taxInfo) {
                                if (!isset($taxTotals[$taxName])) {
                                    $taxTotals[$taxName] = [
                                        'percent' => $taxInfo['percent'] ?? 0,
                                        'amount' => ($taxInfo['amount'] ?? 0) * $qty
                                    ];
                                } else {
                                    $taxTotals[$taxName]['amount'] += ($taxInfo['amount'] ?? 0) * $qty;
                                }
                            }
                            $totalTax += $item->tax_amount ?? 0;
                        }
                    @endphp
                    @foreach ($taxTotals as $taxName => $taxInfo)
                        <div class="summary-row secondary">
                            <span>{{ $taxName }} ({{ $taxInfo['percent'] }}%)</span>
                            <span>{{ currency_format($taxInfo['amount'], restaurant()->currency_id, false, true) }}</span>
                        </div>
                    @endforeach
                    <div class="summary-row">
                        <span>@lang('modules.order.totalTax'):</span>
                        <span>{{ currency_format($totalTax, restaurant()->currency_id, false, true) }}</span>
                    </div>
                @endif
            @endif

            @if ($payment)
                <div class="summary-row">
                    <span>@lang('modules.order.balanceReturn'):</span>
                    <span>{{ currency_format($payment->balance, restaurant()->currency_id, false, true) }}</span>
                </div>
            @endif

            <div class="summary-row total">
                <span>@lang('modules.order.total'):</span>
                <span>{{ currency_format($order->total, restaurant()->currency_id, false, true) }}</span>
            </div>

            @if ($order->reward_points_earned > 0 && in_array('Reward Point', restaurant_modules()))
                <div class="summary-row">
                    <span>@lang('modules.reward.pointsAwarded'):</span>
                    <span>+{{ $order->reward_points_earned }} pts</span>
                </div>
            @endif
        </div>

        @if ($receiptSettings->show_payment_details && $order->payments->count())
            <div class="payment-details">
                <h4>@lang('modules.order.paymentDetails')</h4>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="qty">@lang('modules.order.amount')</th>
                            <th class="description">@lang('modules.order.paymentMethod')</th>
                            <th class="price">@lang('app.dateTime')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->payments as $payment)
                            <tr>
                                <td class="qty">{{ currency_format($payment->amount, restaurant()->currency_id, false, true) }}</td>
                                <td class="description">@lang('modules.order.' . $payment->payment_method)</td>
                                <td class="price">
                                    @if($payment->payment_method != 'due')
                                        {{ $payment->created_at->timezone(timezone())->translatedFormat('d M Y H:i') }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="footer">
            <p>@lang('messages.thankYouVisit')</p>

            @if ($order->status != 'paid' && $receiptSettings->show_payment_qr_code)
                <div class="qr_code">
                    <p>@lang('modules.settings.payFromYourPhone')</p>
                    <img src="{{ $receiptSettings->payment_qr_code_url }}" alt="QR Code">
                    <p>@lang('modules.settings.scanQrCode')</p>
                </div>
            @endif
        </div>
    </div>
</body>

</html>
