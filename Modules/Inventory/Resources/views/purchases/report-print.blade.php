@extends('inventory::layouts.master')

@section('content')
@php
    use Carbon\Carbon;
    $now = Carbon::now();
    $restaurantName = restaurant()->name ?? config('app.name');
    $totalAmount = $purchaseOrders->sum('total_amount');
    $totalDue = $purchaseOrders->sum(fn ($order) => $order->due_amount);
@endphp

<style>
    :root {
        --text: #111827;
        --muted: #6b7280;
        --border: #e5e7eb;
        --soft: #f9fafb;
        --accent: #7c3aed;
    }

    html, body {
        background: #fff;
        color: var(--text);
        margin: 0;
        padding: 0;
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 12px;
    }

    .page {
        max-width: 980px;
        margin: 0 auto;
        padding: 24px 24px 48px;
    }

    .toolbar {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-bottom: 16px;
    }

    .btn {
        appearance: none;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--text);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
    }
    .btn-primary {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }
    .btn:hover { filter: brightness(0.97); }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding-bottom: 14px;
        border-bottom: 2px solid var(--border);
        margin-bottom: 18px;
    }
    .header h1 {
        font-size: 18px;
        margin: 0 0 4px;
        font-weight: 700;
    }
    .header .meta {
        color: var(--muted);
        font-size: 11px;
    }
    .header .right {
        text-align: right;
    }
    .header .restaurant {
        font-size: 14px;
        font-weight: 600;
    }

    .filters {
        background: var(--soft);
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 10px 14px;
        margin-bottom: 16px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px 24px;
    }
    .filters dt {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--muted);
        margin-right: 6px;
        display: inline;
    }
    .filters dd {
        margin: 0;
        display: inline;
        font-weight: 500;
    }
    .filters .row { padding: 2px 0; }

    .stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 18px;
    }
    .stat {
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 10px 14px;
        background: #fff;
    }
    .stat .label {
        color: var(--muted);
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .stat .value {
        font-size: 18px;
        font-weight: 700;
        margin-top: 2px;
    }

    table.report {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
    }
    table.report thead th {
        background: var(--soft);
        text-align: left;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--muted);
        padding: 8px 10px;
        border: 1px solid var(--border);
    }
    table.report tbody td {
        padding: 8px 10px;
        border: 1px solid var(--border);
        vertical-align: top;
    }
    table.report tbody tr:nth-child(even) td { background: #fcfcfd; }

    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .muted { color: var(--muted); }
    .due { color: #dc2626; font-weight: 600; }
    .paid { color: #16a34a; font-weight: 600; }

    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 10px;
        text-transform: capitalize;
    }
    .badge-received { background: #dcfce7; color: #166534; }
    .badge-pending, .badge-ordered { background: #fef9c3; color: #854d0e; }
    .badge-cancelled { background: #fee2e2; color: #991b1b; }
    .badge-paid { background: #dcfce7; color: #166534; }
    .badge-partial { background: #fef9c3; color: #854d0e; }
    .badge-due { background: #fee2e2; color: #991b1b; }

    .footer {
        margin-top: 24px;
        padding-top: 12px;
        border-top: 1px solid var(--border);
        color: var(--muted);
        font-size: 10px;
        display: flex;
        justify-content: space-between;
    }

    .totals-row td {
        font-weight: 700;
        background: #fafafa;
    }

    @media print {
        @page { size: A4 landscape; margin: 12mm; }
        .no-print { display: none !important; }
        body { font-size: 11px; }
        .stat .value { font-size: 16px; }
        table.report thead { display: table-header-group; }
        table.report tr { page-break-inside: avoid; }
    }
</style>

<div class="page">
    <div class="toolbar no-print">
        <button class="btn btn-primary" onclick="window.print()" type="button">
            @lang('inventory::modules.purchaseOrder.print_report')
        </button>
        <button class="btn" onclick="window.close()" type="button">
            @lang('app.close')
        </button>
    </div>

    <div class="header">
        <div>
            <h1>@lang('inventory::modules.purchaseOrder.report_title')</h1>
            <div class="meta">@lang('inventory::modules.purchaseOrder.report_subtitle')</div>
        </div>
        <div class="right">
            <div class="restaurant">{{ $restaurantName }}</div>
            <div class="meta">
                @lang('inventory::modules.purchaseOrder.generated_at'):
                {{ $now->format('M d, Y h:i A') }}
            </div>
        </div>
    </div>

    <dl class="filters">
        @if($showAdminView)
            <div class="row">
                <dt>@lang('app.branch'):</dt>
                <dd>{{ $branchName }}</dd>
            </div>
        @endif
        <div class="row">
            <dt>@lang('inventory::modules.purchaseOrder.supplier'):</dt>
            <dd>{{ $supplierName }}</dd>
        </div>
        <div class="row">
            <dt>@lang('inventory::modules.purchaseOrder.status_label'):</dt>
            <dd>{{ $statusFilter }}</dd>
        </div>
        <div class="row">
            <dt>@lang('app.date'):</dt>
            <dd>
                @if($startDate && $endDate)
                    {{ Carbon::parse($startDate)->format('M d, Y') }} &mdash; {{ Carbon::parse($endDate)->format('M d, Y') }}
                @else
                    @lang('app.all')
                @endif
            </dd>
        </div>
        @if($search !== '')
            <div class="row">
                <dt>@lang('inventory::modules.purchaseOrder.search_placeholder'):</dt>
                <dd>"{{ $search }}"</dd>
            </div>
        @endif
    </dl>

    <div class="stats">
        <div class="stat">
            <div class="label">@lang('inventory::modules.purchaseOrder.total_orders')</div>
            <div class="value">{{ number_format($stats['total_orders']) }}</div>
        </div>
        <div class="stat">
            <div class="label">@lang('inventory::modules.purchaseOrder.pending_orders')</div>
            <div class="value">{{ number_format($stats['pending_orders']) }}</div>
        </div>
        <div class="stat">
            <div class="label">@lang('inventory::modules.purchaseOrder.completed_orders')</div>
            <div class="value">{{ number_format($stats['completed_orders']) }}</div>
        </div>
    </div>

    <table class="report">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th>@lang('inventory::modules.purchaseOrder.po_number')</th>
                <th>@lang('inventory::modules.purchaseOrder.invoice_no')</th>
                <th>@lang('inventory::modules.purchaseOrder.supplier')</th>
                <th>@lang('inventory::modules.purchaseOrder.order_date')</th>
                <th>@lang('inventory::modules.purchaseOrder.expected_delivery_date')</th>
                <th class="text-right">@lang('inventory::modules.purchaseOrder.total_amount')</th>
                <th class="text-right">@lang('inventory::modules.purchaseOrder.due_amount')</th>
                <th>@lang('app.status')</th>
                <th>@lang('inventory::modules.purchaseOrder.payment_status_label')</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseOrders as $index => $purchaseOrder)
                @php
                    $paymentStatus = $purchaseOrder->payment_status;
                @endphp
                <tr>
                    <td class="text-center muted">{{ $index + 1 }}</td>
                    <td>{{ $purchaseOrder->po_number }}</td>
                    <td>{{ $purchaseOrder->invoice_no ?: '-' }}</td>
                    <td>{{ $purchaseOrder->supplier->name ?? '--' }}</td>
                    <td>{{ $purchaseOrder->order_date?->format('M d, Y') ?? '--' }}</td>
                    <td>{{ $purchaseOrder->expected_delivery_date?->format('M d, Y') ?? '-' }}</td>
                    <td class="text-right">{{ currency_format($purchaseOrder->total_amount, restaurant()->currency_id) }}</td>
                    <td class="text-right {{ $purchaseOrder->due_amount > 0 ? 'due' : 'paid' }}">
                        {{ currency_format($purchaseOrder->due_amount, restaurant()->currency_id) }}
                    </td>
                    <td>
                        <span class="badge badge-{{ $purchaseOrder->status }}">
                            {{ $statuses[$purchaseOrder->status] ?? ucfirst(str_replace('_', ' ', $purchaseOrder->status)) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $paymentStatus }}">
                            @if($paymentStatus === 'paid')
                                @lang('inventory::modules.purchaseOrder.payment_status.paid')
                            @elseif($paymentStatus === 'partial')
                                @lang('inventory::modules.purchaseOrder.payment_status.partial')
                            @else
                                @lang('inventory::modules.purchaseOrder.payment_status.due')
                            @endif
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center muted">
                        @lang('inventory::modules.purchaseOrder.no_records')
                    </td>
                </tr>
            @endforelse
            @if($purchaseOrders->isNotEmpty())
                <tr class="totals-row">
                    <td colspan="6" class="text-right">@lang('inventory::modules.purchaseOrder.totals')</td>
                    <td class="text-right">{{ currency_format($totalAmount, restaurant()->currency_id) }}</td>
                    <td class="text-right due">{{ currency_format($totalDue, restaurant()->currency_id) }}</td>
                    <td colspan="2"></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <div>
            @lang('inventory::modules.purchaseOrder.printed_by'):
            {{ user()?->name ?? '--' }}
        </div>
        <div>{{ $restaurantName }} &middot; {{ $now->format('M d, Y h:i A') }}</div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        setTimeout(function () {
            window.focus();
            window.print();
        }, 350);
    });
</script>
@endsection
