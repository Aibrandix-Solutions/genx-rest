@php
    $roomGroups = $folioSummary['room_groups'] ?? [];
    $typeRows = $folioSummary['type_rows'] ?? [];
    $subtotal = $folioSummary['subtotal'] ?? 0;
    $hasRows = count($roomGroups) > 0 || count($typeRows) > 0;
    $interactive = $interactive ?? false;
    $expandedRoomGroups = $expandedRoomGroups ?? [];
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-stone-50/80 dark:bg-gray-800/80 text-[10px] font-bold uppercase tracking-widest text-stone-500 dark:text-gray-400">
                <th class="px-4 py-3">@lang('hotel::modules.folio.date')</th>
                <th class="px-4 py-3">@lang('hotel::modules.folio.roomNo')</th>
                <th class="px-4 py-3 text-center">@lang('hotel::modules.folio.noOfNights')</th>
                <th class="px-4 py-3 text-right">@lang('hotel::modules.folio.pricePerNight')</th>
                <th class="px-4 py-3">@lang('hotel::modules.folio.otherCharges')</th>
                <th class="px-4 py-3 text-right">@lang('hotel::modules.folio.total')</th>
                @if($interactive)
                    <th class="px-4 py-3 w-20"></th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-100 dark:divide-gray-800">
            @foreach($roomGroups as $group)
                @php
                    $isExpanded = in_array($group['key'], $expandedRoomGroups, true);
                    $dateLabel = $group['start_date']->equalTo($group['end_date'])
                        ? $group['start_date']->format('d M Y')
                        : $group['start_date']->format('d M') . ' – ' . $group['end_date']->format('d M Y');
                @endphp
                <tr wire:key="folio-room-group-{{ $group['key'] }}" @class([
                    'group transition-colors',
                    'hover:bg-stone-50/80 dark:hover:bg-gray-800/50' => ($editingRoomGroupKey ?? null) !== $group['key'],
                    'bg-blue-50/60 dark:bg-blue-950/20' => ($editingRoomGroupKey ?? null) === $group['key'],
                ])>
                    <td class="px-4 py-3.5 text-sm tabular-nums text-stone-600 dark:text-gray-300 whitespace-nowrap">
                        {{ $dateLabel }}
                    </td>
                    <td class="px-4 py-3.5 text-sm font-medium text-stone-800 dark:text-gray-200 whitespace-nowrap">
                        {{ $group['room_number'] }}
                    </td>
                    <td class="px-4 py-3.5 text-sm text-center tabular-nums text-stone-800 dark:text-gray-200">
                        {{ $group['nights'] }}
                    </td>
                    <td class="px-4 py-3.5 text-sm text-right tabular-nums text-stone-800 dark:text-gray-200 whitespace-nowrap">
                        @if($interactive && ($editingRoomGroupKey ?? null) === $group['key'])
                            <div class="inline-flex items-center justify-end gap-1 max-w-full" x-data x-init="$nextTick(() => $refs.groupPriceInput?.focus())">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    wire:model="editGroupPricePerNight"
                                    wire:keydown.enter.prevent="saveEditRoomGroupPrice"
                                    wire:keydown.escape="cancelEditRoomGroupPrice"
                                    x-ref="groupPriceInput"
                                    class="w-28 rounded-lg border-blue-300 bg-white px-2 py-1 text-right text-sm font-semibold text-stone-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 dark:border-blue-700 dark:bg-gray-800 dark:text-white"
                                />
                                <button type="button" wire:click="saveEditRoomGroupPrice" wire:loading.attr="disabled" wire:target="saveEditRoomGroupPrice" class="p-1 rounded-md text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/30" title="@lang('app.save')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                </button>
                                <button type="button" wire:click="cancelEditRoomGroupPrice" class="p-1 rounded-md text-stone-400 hover:text-stone-600 hover:bg-stone-100 dark:hover:bg-gray-800" title="@lang('app.cancel')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            @error('editGroupPricePerNight')
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        @else
                            <span class="inline-flex items-center justify-end gap-1.5">
                                <span>{{ currency_format($group['price_per_night']) }}</span>
                                @if($interactive && user_can('edit_room_charge'))
                                    <button
                                        type="button"
                                        wire:click="startEditRoomGroupPrice('{{ $group['key'] }}')"
                                        @disabled(($editingRoomGroupKey ?? null) !== null)
                                        class="p-1 rounded-lg text-stone-400 hover:text-blue-600 hover:bg-blue-50 transition disabled:opacity-40 dark:hover:bg-blue-950/30"
                                        title="@lang('hotel::modules.folio.editPricePerNight')"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                @endif
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-sm text-stone-400 dark:text-gray-500">—</td>
                    <td class="px-4 py-3.5 text-sm text-right font-semibold tabular-nums text-stone-900 dark:text-white whitespace-nowrap">
                        {{ currency_format($group['room_total']) }}
                    </td>
                    @if($interactive)
                        <td class="px-4 py-3.5 text-center">
                            <button
                                type="button"
                                wire:click="toggleRoomGroupExpand('{{ $group['key'] }}')"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition dark:text-indigo-200 dark:bg-indigo-950/50 dark:hover:bg-indigo-950"
                                title="@lang('hotel::modules.folio.viewNightlyBreakdown')"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform {{ $isExpanded ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                </svg>
                            </button>
                        </td>
                    @endif
                </tr>
                @if($interactive && $isExpanded)
                    @foreach($group['charges'] as $nightCharge)
                        @include('hotel::livewire.folio.partials.nightly-charge-row', [
                            'charge' => $nightCharge,
                        ])
                    @endforeach
                @endif
            @endforeach

            @foreach($typeRows as $typeRow)
                <tr wire:key="folio-type-row-{{ $typeRow['key'] }}" class="hover:bg-stone-50/80 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="px-4 py-3.5 text-sm tabular-nums text-stone-600 dark:text-gray-300 whitespace-nowrap">
                        {{ $typeRow['date']?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3.5 text-sm text-stone-400 dark:text-gray-500">—</td>
                    <td class="px-4 py-3.5 text-sm text-center text-stone-400 dark:text-gray-500">—</td>
                    <td class="px-4 py-3.5 text-sm text-right text-stone-400 dark:text-gray-500">—</td>
                    <td class="px-4 py-3.5 text-sm text-stone-800 dark:text-gray-200">
                        @if($interactive)
                            <button
                                type="button"
                                wire:click="openOtherChargesModal('{{ $typeRow['type'] }}')"
                                class="inline-flex items-center gap-2 font-medium text-left text-amber-900 hover:text-amber-950 underline decoration-amber-300/60 underline-offset-2 transition dark:text-amber-200 dark:hover:text-amber-100"
                            >
                                <span>{{ $typeRow['label'] }}</span>
                                <span class="text-[11px] font-normal text-stone-500 dark:text-gray-400 no-underline">
                                    ({{ $typeRow['charge_count'] }} {{ trans_choice('hotel::modules.folio.chargeItems', $typeRow['charge_count']) }})
                                </span>
                            </button>
                        @else
                            <span class="font-medium">{{ $typeRow['label'] }}</span>
                            <span class="text-[11px] text-stone-500 dark:text-gray-400">
                                ({{ $typeRow['charge_count'] }} {{ trans_choice('hotel::modules.folio.chargeItems', $typeRow['charge_count']) }})
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-sm text-right font-semibold tabular-nums text-stone-900 dark:text-white whitespace-nowrap">
                        {{ currency_format($typeRow['amount']) }}
                    </td>
                    @if($interactive)
                        <td class="px-4 py-3.5 text-center">
                            <button
                                type="button"
                                wire:click="openOtherChargesModal('{{ $typeRow['type'] }}')"
                                class="p-1.5 rounded-lg text-stone-400 hover:text-amber-700 hover:bg-amber-50 transition dark:hover:bg-amber-950/30"
                                title="@lang('hotel::modules.folio.viewOtherCharges')"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </button>
                        </td>
                    @endif
                </tr>
            @endforeach

            @if(!$hasRows)
                <tr>
                    <td colspan="{{ $interactive ? 7 : 6 }}" class="px-6 py-16 text-center">
                        <p class="text-stone-500 dark:text-gray-400">@lang('hotel::modules.folio.noCharges')</p>
                    </td>
                </tr>
            @endif
        </tbody>
        @if($hasRows)
            <tfoot class="bg-stone-50/90 dark:bg-gray-800/50 border-t-2 border-stone-200 dark:border-gray-700">
                <tr>
                    <td colspan="{{ $interactive ? 5 : 5 }}" class="px-4 py-3 text-right text-sm font-bold text-stone-800 dark:text-white">
                        @lang('hotel::modules.folio.subtotal')
                    </td>
                    <td class="px-4 py-3 text-right font-bold tabular-nums text-stone-900 dark:text-white">
                        {{ currency_format($subtotal) }}
                    </td>
                    @if($interactive)
                        <td></td>
                    @endif
                </tr>
            </tfoot>
        @endif
    </table>
</div>
