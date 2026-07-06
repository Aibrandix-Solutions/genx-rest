<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $sectionTitle }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            color: #111827;
            margin: 0;
            padding: 16px;
        }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { font-size: 9px; color: #6b7280; margin-bottom: 14px; }
        .meta span { margin-right: 12px; display: inline-block; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 700; font-size: 8px; text-transform: uppercase; }
        .empty { text-align: center; color: #6b7280; padding: 12px; }
        .footer { margin-top: 16px; font-size: 8px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .item-name { font-weight: 700; }
    </style>
</head>
<body>
    <h1>{{ $sectionTitle }}</h1>
    <div class="meta">
        <span><strong>{{ __('inventory::modules.reports.item_inventory.pdf.restaurant') }}:</strong> {{ $restaurantName }}</span>
        <span><strong>{{ __('inventory::modules.reports.item_inventory.filters.branch') }}:</strong> {{ $branchLabel }}</span>
        <span><strong>{{ __('inventory::modules.reports.common.date_range') }}:</strong> {{ $startDate }} – {{ $endDate }}</span>
        @if ($supplierName !== __('app.all'))
            <span><strong>{{ __('inventory::modules.reports.item_inventory.filters.supplier') }}:</strong> {{ $supplierName }}</span>
        @endif
        @if (!empty($purchaseStatus))
            <span><strong>{{ __('inventory::modules.reports.item_inventory.filters.purchase_status') }}:</strong> {{ trans('inventory::modules.purchaseOrder.status.' . $purchaseStatus, [], null, $purchaseStatus) }}</span>
        @endif
        @if (!empty($paymentStatus))
            <span><strong>{{ __('inventory::modules.reports.item_inventory.filters.payment_status') }}:</strong> {{ trans('inventory::modules.purchaseOrder.payment_status.' . $paymentStatus, [], null, $paymentStatus) }}</span>
        @endif
        @if (!empty($search))
            <span><strong>{{ __('inventory::modules.reports.item_inventory.filters.search_item') }}:</strong> {{ $search }}</span>
        @endif
        @if (!empty($itemLabel) && $itemLabel !== __('app.all'))
            <span><strong>{{ __('inventory::modules.reports.item_inventory.filters.inventory_item') }}:</strong> {{ $itemLabel }}</span>
        @endif
        @if (!empty($locationLabel) && $locationLabel !== __('app.all'))
            <span><strong>{{ __('inventory::modules.reports.item_inventory.filters.location') }}:</strong> {{ $locationLabel }}</span>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('inventory::modules.reports.item_inventory.summary.inventory_item') }}</th>
                <th>{{ __('inventory::modules.reports.item_inventory.summary.purchases_column') }}</th>
                <th>{{ __('inventory::modules.reports.item_inventory.summary.usage_column') }}</th>
                <th>{{ __('inventory::modules.reports.item_inventory.summary.movements_column') }}</th>
                <th>{{ __('inventory::modules.reports.item_inventory.summary.wastages_column') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($summaryRows as $row)
                <tr>
                    <td class="item-name">
                        {{ $row->item_name }}@if($row->item_code) ({{ $row->item_code }})@endif
                    </td>
                    <td>{{ $row->purchases }}</td>
                    <td>{{ $row->usage }}</td>
                    <td>{{ $row->movements }}</td>
                    <td>{{ $row->wastages }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">{{ __('inventory::modules.reports.common.no_data') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ __('inventory::modules.reports.item_inventory.pdf.printed_by') }}: {{ $printedBy }}
        | {{ __('inventory::modules.reports.item_inventory.pdf.printed_at') }}: {{ $printedAt }}
        | {{ __('inventory::modules.reports.item_inventory.total_rows') }}: {{ $summaryRows->count() }}
    </div>
</body>
</html>
