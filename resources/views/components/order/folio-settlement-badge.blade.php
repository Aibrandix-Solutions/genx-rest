@props(['order'])

@php
    $badge = class_exists(\Modules\Hotel\Services\OrderFolioSettlement::class)
        ? \Modules\Hotel\Services\OrderFolioSettlement::settlementBadge($order)
        : null;
@endphp

@if($badge)
    <span @class([
        'text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide whitespace-nowrap inline-flex items-center gap-1',
        'bg-violet-100 text-violet-800 border border-violet-300 dark:bg-violet-900/40 dark:text-violet-200 dark:border-violet-700' => $badge['tone'] === 'folio',
        'bg-teal-100 text-teal-800 border border-teal-300 dark:bg-teal-900/40 dark:text-teal-200 dark:border-teal-700' => $badge['tone'] === 'settled',
    ])>
        @if($badge['tone'] === 'folio')
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V9l7-4 7 4v12M9 21v-6h6v6"/></svg>
        @else
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @endif
        {{ $badge['label'] }}
    </span>
@endif
