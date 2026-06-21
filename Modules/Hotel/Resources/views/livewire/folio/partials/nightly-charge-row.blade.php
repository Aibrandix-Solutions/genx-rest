<tr wire:key="folio-night-{{ $charge->id }}" class="bg-indigo-50/40 dark:bg-indigo-950/20">
    <td class="px-4 py-2.5 pl-8 text-xs tabular-nums text-stone-500 dark:text-gray-400 whitespace-nowrap border-l-2 border-indigo-300 dark:border-indigo-700">
        {{ $charge->charge_date->format('d M Y') }}
    </td>
    <td class="px-4 py-2.5 text-xs text-stone-500 dark:text-gray-400" colspan="3">
        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-200">
            @lang('hotel::modules.folio.roomNight')
        </span>
        <span class="ml-2">{{ $charge->getDisplayDescription() }}</span>
    </td>
    <td class="px-4 py-2.5 text-xs text-stone-400 dark:text-gray-500">—</td>
    <td class="px-4 py-2.5 text-xs text-right font-medium tabular-nums text-stone-800 dark:text-gray-200 whitespace-nowrap">
        {{ currency_format($charge->amount) }}
    </td>
    <td class="px-4 py-2.5"></td>
</tr>
