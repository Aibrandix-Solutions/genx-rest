<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: 'NotoSans';
            src: url('{{ public_path('fonts/NotoSans-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'NotoSans';
            src: url('{{ public_path('fonts/NotoSans-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @page {
            margin: 1.2cm 1.2cm;
        }
        body {
            font-family: 'NotoSans', sans-serif;
            font-size: 9pt;
            line-height: 1.35;
            color: #2d3748;
            margin: 0;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* ============ HEADER ============ */
        .header {
            position: relative;
            border-bottom: 1.5px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .logo {
            float: left;
            margin-right: 14px;
        }
        .logo img {
            max-height: 48px;
            width: auto;
        }
        .company-info {
            float: left;
            margin-top: 2px;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 2px;
        }
        .company-details {
            font-size: 8pt;
            color: #4a5568;
            line-height: 1.3;
        }
        .document-info {
            float: right;
            text-align: right;
        }
        .document-title {
            font-size: 13pt;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 3px;
        }
        .document-number {
            font-size: 9pt;
            color: #4a5568;
            margin-bottom: 4px;
        }

        /* ============ STATUS BADGES ============ */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: bold;
        }
        .status-draft { background-color: #edf2f7; color: #2d3748; }
        .status-sent, .status-ordered { background-color: #ebf8ff; color: #2b6cb0; }
        .status-pending, .status-partially_received { background-color: #fffff0; color: #975a16; }
        .status-received { background-color: #f0fff4; color: #2f855a; }
        .status-cancelled { background-color: #fff5f5; color: #c53030; }
        .pay-paid { background-color: #f0fff4; color: #2f855a; }
        .pay-partial { background-color: #fffff0; color: #975a16; }
        .pay-due { background-color: #fff5f5; color: #c53030; }

        /* ============ META GRID ============ */
        .meta-grid {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .meta-cell {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 0;
        }
        .meta-cell + .meta-cell { padding-left: 8px; }
        .meta-cell:first-child { padding-right: 8px; }
        .box {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            border-radius: 4px;
            background-color: #f8fafc;
        }
        .box-title {
            font-size: 8pt;
            font-weight: bold;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }
        .label {
            font-size: 7.5pt;
            color: #718096;
            margin-bottom: 1px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .value {
            font-size: 9pt;
            color: #1a202c;
        }

        /* Two-column meta inside a box, compact */
        .kv {
            width: 100%;
            border-collapse: collapse;
        }
        .kv th, .kv td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 8.5pt;
            vertical-align: top;
        }
        .kv th {
            background-color: #f1f5f9;
            color: #4a5568;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            font-size: 7.5pt;
            width: 38%;
            font-weight: bold;
        }
        .kv td {
            color: #1a202c;
            font-weight: bold;
            background-color: #ffffff;
        }

        /* ============ NOTES ============ */
        .notes-box {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 6px 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 8.5pt;
        }

        /* ============ ITEMS TABLE ============ */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 8px;
        }
        table.items th {
            background-color: #f1f5f9;
            border-top: 1px solid #cbd5e0;
            border-bottom: 1px solid #cbd5e0;
            padding: 6px 8px;
            font-size: 8pt;
            font-weight: bold;
            color: #4a5568;
            text-align: left;
            text-transform: uppercase;
        }
        table.items td {
            padding: 6px 8px;
            border-bottom: 1px solid #edf2f7;
            font-size: 8.5pt;
            color: #2d3748;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .muted { color: #718096; font-size: 7.5pt; }

        /* ============ TOTALS PANEL ============ */
        .totals-wrap {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .totals-wrap td.spacer {
            width: 55%;
        }
        .totals-wrap td.totals-cell {
            width: 45%;
            padding: 0;
            vertical-align: top;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e0;
        }
        .totals-table th, .totals-table td {
            padding: 5px 10px;
            font-size: 8.5pt;
            border: 1px solid #e2e8f0;
        }
        .totals-table th {
            background-color: #f1f5f9;
            color: #4a5568;
            text-transform: uppercase;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: left;
            width: 55%;
        }
        .totals-table td {
            background: #ffffff;
            color: #1a202c;
            font-weight: bold;
            text-align: right;
        }
        .totals-table tr.totals-divider th,
        .totals-table tr.totals-divider td {
            background-color: #edf2f7;
            font-size: 10pt;
            color: #1a202c;
            font-weight: bold;
        }
        .totals-table .total-paid { color: #2f855a; }
        .totals-table .total-due { color: #c53030; font-weight: bold; }

        /* ============ SECTIONS ============ */
        .section { margin-bottom: 10px; }
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #2d3748;
            margin: 0 0 5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table.sub {
            width: 100%;
            border-collapse: collapse;
        }
        table.sub th {
            background-color: #f1f5f9;
            padding: 5px 8px;
            font-size: 7.5pt;
            font-weight: bold;
            color: #4a5568;
            text-align: left;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e0;
        }
        table.sub td {
            padding: 5px 8px;
            font-size: 8pt;
            border-bottom: 1px solid #edf2f7;
        }

        /* ============ FOOTER ============ */
        .footer {
            position: fixed;
            bottom: -0.6cm;
            left: 0;
            right: 0;
            font-size: 7.5pt;
            color: #a0aec0;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        // DomPDF cannot reliably load file:// paths in img src; use data URI for local files or logo_url (HTTP/S3).
        $restaurantLogoSrc = restaurant()->logo_url;
        if (!empty(restaurant()->logo)) {
            $candidate = public_path('user-uploads/logo/' . restaurant()->logo);
            if (is_file($candidate)) {
                $data = base64_encode(file_get_contents($candidate));
                $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'png' => 'png',
                    'gif' => 'gif',
                    'webp' => 'webp',
                    'svg' => 'svg+xml',
                    default => 'jpeg',
                };
                $restaurantLogoSrc = 'data:image/' . $mime . ';base64,' . $data;
            }
        }

        $purchaseLocation = $purchaseOrder->location;
        $locationName = $purchaseLocation?->display_name ?? '-';
        $locationAddress = $purchaseLocation?->address ?? ($purchaseLocation?->branch?->address ?? branch()->address);
        $locationPhone = $purchaseLocation?->branch?->phone ?? branch()->phone;

        // Compute totals consistently with the on-screen view
        $itemsSubtotal = 0;
        foreach ($purchaseOrder->items as $line) {
            $lineTotal = (float) $line->quantity * (float) $line->unit_price;
            $lineDiscount = ($line->discount_type ?? 'fixed') === 'percentage'
                ? $lineTotal * (((float) ($line->discount ?? 0)) / 100)
                : (float) ($line->discount ?? 0);
            $itemsSubtotal += max(0, $lineTotal - $lineDiscount);
        }
        $orderDiscountAmount = ($purchaseOrder->discount_type ?? 'fixed') === 'percentage'
            ? ($itemsSubtotal * (float) ($purchaseOrder->discount ?? 0) / 100)
            : (float) ($purchaseOrder->discount ?? 0);
        $finalTotal = max(0, $itemsSubtotal - $orderDiscountAmount);

        // Status label (translated, with a humanised fallback)
        $statusKey = 'inventory::modules.purchaseOrder.status.' . $purchaseOrder->status;
        $statusLabel = trans($statusKey);
        if ($statusLabel === $statusKey) {
            $statusLabel = ucfirst(str_replace('_', ' ', (string) $purchaseOrder->status));
        }

        $paymentStatus = $purchaseOrder->payment_status;
    @endphp

    {{-- ============ HEADER ============ --}}
    <div class="header clearfix">
        <div class="logo">
            <img src="{{ $restaurantLogoSrc }}" alt="{{ restaurant()->name }}">
        </div>
        <div class="company-info">
            <div class="company-name">{{ restaurant()->name }}</div>
            <div class="company-details">
                {{ $locationName }}<br>
                {{ $locationAddress }}@if($locationPhone) &nbsp;·&nbsp; {{ $locationPhone }}@endif
            </div>
        </div>
        <div class="document-info">
            <div class="document-title">{{ trans('inventory::modules.purchaseOrder.purchase_order') }}</div>
            <div class="document-number">{{ $purchaseOrder->po_number }}</div>
            @if($purchaseOrder->invoice_no)
                <div class="document-number">{{ trans('inventory::modules.purchaseOrder.invoice_no') }}: {{ $purchaseOrder->invoice_no }}</div>
            @endif
            <div class="status-badge status-{{ $purchaseOrder->status }}">{{ $statusLabel }}</div>
        </div>
    </div>

    {{-- ============ META: Supplier + Order details ============ --}}
    <div class="meta-grid">
        <div class="meta-cell" style="width: 100%;">
            <div class="box">
                <div class="box-title">{{ trans('inventory::modules.purchaseOrder.supplier') }}</div>
                <div class="value" style="font-weight: bold; margin-bottom: 3px;">{{ $purchaseOrder->supplier->name ?? '-' }}</div>
                <div style="font-size: 8pt; color: #4a5568;">
                    @if(!empty($purchaseOrder->supplier->address)){{ $purchaseOrder->supplier->address }}<br>@endif
                    @if(!empty($purchaseOrder->supplier->phone)){{ $purchaseOrder->supplier->phone }}@endif
                    @if(!empty($purchaseOrder->supplier->phone) && !empty($purchaseOrder->supplier->email)) &nbsp;·&nbsp; @endif
                    @if(!empty($purchaseOrder->supplier->email)){{ $purchaseOrder->supplier->email }}@endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============ ORDER DETAILS (column-wise table) ============ --}}
    <table class="sub" style="margin-bottom: 12px;">
        <thead>
            <tr>
                <th>{{ trans('inventory::modules.purchaseOrder.order_date') }}</th>
                <th>{{ trans('inventory::modules.purchaseOrder.expected_delivery_date') }}</th>
                <th>Location</th>
                <th>{{ trans('inventory::modules.purchaseOrder.status_label') }}</th>
                <th>{{ trans('inventory::modules.purchaseOrder.created_by') }}</th>
                <th>{{ trans('inventory::modules.purchaseOrder.created_at') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $purchaseOrder->order_date?->format('M d, Y') ?? '-' }}</td>
                <td>{{ $purchaseOrder->expected_delivery_date?->format('M d, Y') ?? '-' }}</td>
                <td>{{ $locationName }}</td>
                <td>
                    <span class="status-badge status-{{ $purchaseOrder->status }}">{{ $statusLabel }}</span>
                </td>
                <td>{{ $purchaseOrder->creator->name ?? '-' }}</td>
                <td>{{ $purchaseOrder->created_at?->format('M d, Y H:i') ?? '-' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ============ NOTES ============ --}}
    @if($purchaseOrder->notes)
        <div class="notes-box">
            <div class="label" style="margin-bottom: 2px;">{{ trans('inventory::modules.purchaseOrder.notes') }}</div>
            <div style="white-space: pre-line;">{{ $purchaseOrder->notes }}</div>
        </div>
    @endif

    {{-- ============ ITEMS ============ --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 32%;">{{ trans('inventory::modules.inventoryItem.name') }}</th>
                <th style="width: 8%;">{{ trans('inventory::modules.inventoryItem.unit') }}</th>
                <th style="width: 12%;" class="text-right">{{ trans('inventory::modules.purchaseOrder.unit_price') }}</th>
                <th style="width: 12%;" class="text-right">{{ trans('inventory::modules.purchaseOrder.ordered_quantity') }}</th>
                <th style="width: 12%;" class="text-right">Discount</th>
                <th style="width: 20%;" class="text-right">{{ trans('inventory::modules.purchaseOrder.subtotal') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $index => $item)
                @php
                    $line = (float) $item->quantity * (float) $item->unit_price;
                    $lineDiscount = ($item->discount_type ?? 'fixed') === 'percentage'
                        ? $line * (((float) ($item->discount ?? 0)) / 100)
                        : (float) ($item->discount ?? 0);
                    $lineSubtotal = max(0, $line - $lineDiscount);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        @if(!empty(optional($item->inventoryItem)->item_code))
                            <span class="muted" style="font-family: monospace;">[{{ $item->inventoryItem->item_code }}]</span>
                        @endif
                        {{ $item->inventoryItem->name ?? 'Item Deleted' }}
                        @if(optional($item->inventoryItem)->category)
                            <div class="muted">{{ $item->inventoryItem->category->name }}</div>
                        @endif
                    </td>
                    <td>{{ $item->displayUnitSymbol() }}</td>
                    <td class="text-right">{{ currency_format($item->unit_price, restaurant()->currency_id) }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">
                        @if((float) ($item->discount ?? 0) > 0)
                            @if(($item->discount_type ?? 'fixed') === 'percentage')
                                {{ number_format((float) $item->discount, 2) }}%
                                <div class="muted">-{{ currency_format($lineDiscount, restaurant()->currency_id) }}</div>
                            @else
                                -{{ currency_format($item->discount, restaurant()->currency_id) }}
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right" style="font-weight: bold;">{{ currency_format($lineSubtotal, restaurant()->currency_id) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ============ TOTALS (column-wise table) ============ --}}
    <table class="sub" style="margin-bottom: 12px;">
        <thead>
            <tr>
                <th class="text-right">Items Subtotal</th>
                <th class="text-right">
                    Order Discount
                    @if((float) ($purchaseOrder->discount ?? 0) > 0)
                        <span class="muted">
                            ({{ ($purchaseOrder->discount_type ?? 'fixed') === 'percentage'
                                ? number_format((float) $purchaseOrder->discount, 2) . '%'
                                : 'Fixed' }})
                        </span>
                    @endif
                </th>
                <th class="text-right">Final Total</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Due</th>
                <th class="text-right">Payment Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-right">{{ currency_format($itemsSubtotal, restaurant()->currency_id) }}</td>
                <td class="text-right">-{{ currency_format($orderDiscountAmount, restaurant()->currency_id) }}</td>
                <td class="text-right" style="font-weight: bold;">{{ currency_format($finalTotal, restaurant()->currency_id) }}</td>
                <td class="text-right" style="color: #2f855a; font-weight: bold;">{{ currency_format($purchaseOrder->paid_amount, restaurant()->currency_id) }}</td>
                <td class="text-right" style="{{ $purchaseOrder->due_amount > 0 ? 'color: #c53030;' : '' }} font-weight: bold;">{{ currency_format($purchaseOrder->due_amount, restaurant()->currency_id) }}</td>
                <td class="text-right">
                    <span class="status-badge pay-{{ $paymentStatus }}">
                        {{ trans('inventory::modules.purchaseOrder.payment_status.' . $paymentStatus, [], ucfirst($paymentStatus)) }}
                    </span>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ============ PAYMENT HISTORY ============ --}}
    @if($purchaseOrder->payments && $purchaseOrder->payments->count() > 0)
        <div class="section">
            <div class="section-title">Payment History</div>
            <table class="sub">
                <thead>
                    <tr>
                        <th style="width: 18%;">Date</th>
                        <th style="width: 14%;">Method</th>
                        <th style="width: 18%;">Account</th>
                        <th style="width: 18%;">Transaction</th>
                        <th style="width: 17%;">Note</th>
                        <th style="width: 15%;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_on?->format('M d, Y H:i') ?? '-' }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', (string) $payment->payment_method)) }}</td>
                            <td>{{ $payment->account->name ?? '-' }}</td>
                            <td>{{ $payment->transaction_id ?? '-' }}</td>
                            <td>{{ $payment->note ?? '-' }}</td>
                            <td class="text-right" style="font-weight: bold;">{{ currency_format($payment->amount, restaurant()->currency_id) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ============ ATTACHMENTS LIST ============ --}}
    @if($purchaseOrder->attachments && $purchaseOrder->attachments->count() > 0)
        <div class="section">
            <div class="section-title">Attachments</div>
            <table class="sub">
                <thead>
                    <tr>
                        <th style="width: 60%;">File</th>
                        <th style="width: 20%;">Type</th>
                        <th style="width: 20%;">Uploaded</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->attachments as $att)
                        <tr>
                            <td>{{ $att->original_name }}</td>
                            <td>{{ ucfirst($att->file_type ?? '-') }}</td>
                            <td>{{ $att->created_at?->format('M d, Y H:i') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="footer">Generated on {{ now(timezone())->format('F d, Y \a\t H:i:s') }}</div>
</body>
</html>
