@extends('inventory::layouts.master')

@section('content')
@php
    use Carbon\Carbon;
    $now = Carbon::now();
    $start = Carbon::parse($startDate);
    $end = Carbon::parse($endDate);
    $restaurantName = restaurant()->name ?? config('app.name');
@endphp

<style>
    :root {
        --text: #111827;
        --muted: #6b7280;
        --border: #e5e7eb;
        --soft: #f9fafb;
        --accent: #7c3aed;
        --accent-soft: #ede9fe;
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
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }

    .chip {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        background: var(--accent-soft);
        color: var(--accent);
        font-weight: 600;
        font-size: 11px;
    }

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
        @page { size: A4; margin: 14mm; }
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
            @lang('inventory::modules.consumption.report.print')
        </button>
        <button class="btn" onclick="window.close()" type="button">
            @lang('app.close')
        </button>
    </div>

    <div class="header">
        <div>
            <h1>@lang('inventory::modules.consumption.report.title')</h1>
            <div class="meta">@lang('inventory::modules.consumption.report.subtitle')</div>
        </div>
        <div class="right">
            <div class="restaurant">{{ $restaurantName }}</div>
            <div class="meta">
                @lang('inventory::modules.consumption.report.generatedAt'):
                {{ $now->format('M d, Y h:i A') }}
            </div>
        </div>
    </div>

    <dl class="filters">
        <div class="row">
            <dt>@lang('inventory::modules.consumption.report.dateRange'):</dt>
            <dd>{{ $start->format('M d, Y') }} &mdash; {{ $end->format('M d, Y') }}</dd>
        </div>
        <div class="row">
            <dt>@lang('inventory::modules.consumption.branch'):</dt>
            <dd>
                @if($branchName)
                    {{ $branchName }}
                @else
                    @lang('inventory::modules.consumption.allBranches')
                @endif
            </dd>
        </div>
        <div class="row">
            <dt>@lang('inventory::modules.consumption.item'):</dt>
            <dd>
                @if($itemName)
                    {{ $itemName }}
                @else
                    @lang('inventory::modules.consumption.allItems')
                @endif
            </dd>
        </div>
        <div class="row">
            <dt>@lang('inventory::modules.consumption.report.viewMode'):</dt>
            <dd>
                @if($viewMode === 'summary')
                    @lang('inventory::modules.consumption.report.viewSummary')
                @else
                    @lang('inventory::modules.consumption.report.viewDetail')
                @endif
            </dd>
        </div>
        @if(!empty($search))
            <div class="row">
                <dt>@lang('inventory::modules.consumption.report.searchLabel'):</dt>
                <dd>"{{ $search }}"</dd>
            </div>
        @endif
    </dl>

    <div class="stats">
        <div class="stat">
            <div class="label">@lang('inventory::modules.consumption.totalConsumed')</div>
            <div class="value">{{ number_format($totals['consumed'], 2) }}</div>
        </div>
        <div class="stat">
            <div class="label">@lang('inventory::modules.consumption.entries')</div>
            <div class="value">{{ number_format($totals['entries']) }}</div>
        </div>
        <div class="stat">
            <div class="label">@lang('inventory::modules.consumption.uniqueItems')</div>
            <div class="value">{{ number_format($totals['items']) }}</div>
        </div>
    </div>

    @if($viewMode === 'summary')
        <table class="report">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th>@lang('inventory::modules.consumption.item')</th>
                    <th class="text-right">@lang('inventory::modules.consumption.report.beforeConsumption')</th>
                    <th class="text-right">@lang('inventory::modules.consumption.report.consumed')</th>
                    <th class="text-right">@lang('inventory::modules.consumption.report.afterConsumption')</th>
                    <th class="text-right">@lang('inventory::modules.consumption.entries')</th>
                    <th>@lang('inventory::modules.consumption.report.dateRange')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summaryRows as $idx => $row)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>
                            <div><strong>{{ $row->item_name }}</strong></div>
                            @if(!empty($row->item_code))
                                <div class="muted mono" style="font-size: 10px;">{{ $row->item_code }}</div>
                            @endif
                        </td>
                        <td class="text-right">
                            {{ number_format($row->opening, 2) }}
                            <span class="muted">{{ $row->unit_symbol }}</span>
                        </td>
                        <td class="text-right">
                            <span class="chip">- {{ number_format($row->consumed, 2) }} {{ $row->unit_symbol }}</span>
                        </td>
                        <td class="text-right">
                            <strong>{{ number_format($row->closing, 2) }}</strong>
                            <span class="muted">{{ $row->unit_symbol }}</span>
                        </td>
                        <td class="text-right">{{ number_format($row->entries) }}</td>
                        <td class="muted" style="font-size: 11px;">
                            {{ optional($row->first_date)->format('M d, Y') }} &mdash;
                            {{ optional($row->last_date)->format('M d, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center muted" style="padding: 24px;">
                            @lang('inventory::modules.consumption.noEntries')
                        </td>
                    </tr>
                @endforelse

                @if($summaryRows->isNotEmpty())
                    <tr class="totals-row">
                        <td colspan="3" class="text-right">@lang('inventory::modules.consumption.report.totals')</td>
                        <td class="text-right">
                            {{ number_format($summaryRows->sum('consumed'), 2) }}
                        </td>
                        <td colspan="3"></td>
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        <table class="report">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th>@lang('inventory::modules.consumption.date')</th>
                    <th>@lang('inventory::modules.consumption.item')</th>
                    <th>@lang('inventory::modules.consumption.branch')</th>
                    <th class="text-right">@lang('inventory::modules.consumption.report.beforeConsumption')</th>
                    <th class="text-right">@lang('inventory::modules.consumption.report.consumed')</th>
                    <th class="text-right">@lang('inventory::modules.consumption.report.afterConsumption')</th>
                    <th>@lang('inventory::modules.consumption.recordedBy')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detailRows as $idx => $row)
                    @php $unitSymbol = optional($row->item?->unit)->symbol; @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ optional($row->consumption_date)->format('M d, Y') }}</td>
                        <td>
                            <div><strong>{{ $row->item->name ?? '--' }}</strong></div>
                            @if(!empty($row->item?->item_code))
                                <div class="muted mono" style="font-size: 10px;">{{ $row->item->item_code }}</div>
                            @endif
                        </td>
                        <td>{{ $row->branch->name ?? '--' }}</td>
                        <td class="text-right">
                            @if($row->stock_before !== null)
                                {{ number_format((float) $row->stock_before, 2) }}
                                <span class="muted">{{ $unitSymbol }}</span>
                            @else
                                <span class="muted">--</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <span class="chip">- {{ number_format((float) $row->quantity, 2) }} {{ $unitSymbol }}</span>
                        </td>
                        <td class="text-right">
                            @if($row->stock_after !== null)
                                <strong>{{ number_format((float) $row->stock_after, 2) }}</strong>
                                <span class="muted">{{ $unitSymbol }}</span>
                            @else
                                <span class="muted">--</span>
                            @endif
                        </td>
                        <td>{{ $row->addedBy->name ?? '--' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center muted" style="padding: 24px;">
                            @lang('inventory::modules.consumption.noEntries')
                        </td>
                    </tr>
                @endforelse

                @if($detailRows->isNotEmpty())
                    <tr class="totals-row">
                        <td colspan="5" class="text-right">@lang('inventory::modules.consumption.report.totals')</td>
                        <td class="text-right">
                            {{ number_format($detailRows->sum('quantity'), 2) }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif

    <div class="footer">
        <div>
            @lang('inventory::modules.consumption.report.printedBy'):
            {{ user()?->name ?? '--' }}
        </div>
        <div>{{ $restaurantName }} &middot; {{ $now->format('M d, Y h:i A') }}</div>
    </div>
</div>

<script>
    // Auto-launch the print dialog as soon as the layout settles.
    window.addEventListener('load', function () {
        setTimeout(function () {
            window.focus();
            window.print();
        }, 350);
    });
</script>
@endsection
