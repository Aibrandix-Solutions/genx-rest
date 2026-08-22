@php
    use Modules\Hotel\Services\FolioChargePresenter;

    $filteredCharges = FolioChargePresenter::filterOtherCharges(
        collect($folioSummary['other_charges'] ?? []),
        $otherChargesFilterType,
    );
    $filterLabel = $otherChargesFilterType
        ? FolioChargePresenter::typeLabel(
            $otherChargesFilterType,
            $filteredCharges->first(),
        )
        : null;
@endphp

<x-right-modal wire:model.live="showOtherChargesModal">
    <x-slot name="title">
        @if($filterLabel)
            {{ $filterLabel }} — @lang('hotel::modules.folio.otherCharges')
        @else
            @lang('hotel::modules.folio.otherCharges')
        @endif
    </x-slot>
    <x-slot name="content">
        <div class="space-y-4">
            @if($otherChargesFilterType)
                <button
                    type="button"
                    wire:click="$set('otherChargesFilterType', null)"
                    class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                >
                    @lang('hotel::modules.folio.showAllOtherCharges')
                </button>
            @endif

            <div class="overflow-x-auto rounded-xl border border-stone-200 dark:border-gray-700">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-stone-50 dark:bg-gray-800 text-[10px] font-bold uppercase tracking-widest text-stone-500 dark:text-gray-400">
                            <th class="px-3 py-2.5">@lang('hotel::modules.folio.date')</th>
                            <th class="px-3 py-2.5">@lang('hotel::modules.folio.roomNo')</th>
                            <th class="px-3 py-2.5">@lang('hotel::modules.folio.type')</th>
                            <th class="px-3 py-2.5">@lang('hotel::modules.folio.description')</th>
                            <th class="px-3 py-2.5 text-right">@lang('hotel::modules.folio.amount')</th>
                            <th class="px-3 py-2.5 w-20"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-gray-800">
                        @forelse($filteredCharges as $charge)
                            <tr wire:key="other-charge-{{ $charge->id }}" @class([
                                'bg-blue-50/60 dark:bg-blue-950/20' => $editingChargeId === $charge->id,
                                'hover:bg-stone-50/80 dark:hover:bg-gray-800/50' => ! $charge->order_id && $editingChargeId !== $charge->id,
                                'hover:bg-orange-50/50 dark:hover:bg-orange-950/20' => $charge->order_id,
                            ])>
                                <td class="px-3 py-3 text-sm tabular-nums text-stone-600 dark:text-gray-300 whitespace-nowrap">
                                    {{ $charge->charge_date->format('d M Y') }}
                                </td>
                                <td class="px-3 py-3 text-sm font-medium text-stone-800 dark:text-gray-200 whitespace-nowrap">
                                    {{ $charge->reservation?->room?->room_number ?? '—' }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span @class([
                                        'inline-flex px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wide',
                                        'bg-orange-100 text-orange-800 dark:bg-orange-950/60 dark:text-orange-200' => $charge->charge_type === 'restaurant',
                                        'bg-pink-100 text-pink-800 dark:bg-pink-950/60 dark:text-pink-200' => $charge->charge_type === 'minibar',
                                        'bg-cyan-100 text-cyan-800 dark:bg-cyan-950/60 dark:text-cyan-200' => $charge->charge_type === 'laundry',
                                        'bg-teal-100 text-teal-800 dark:bg-teal-950/60 dark:text-teal-200' => $charge->charge_type === 'service',
                                        'bg-stone-100 text-stone-700 dark:bg-gray-800 dark:text-gray-300' => $charge->charge_type === 'tax',
                                        'bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-200' => $charge->charge_type === 'other',
                                    ])>{{ $charge->getCustomTypeLabel() ?? ucfirst(str_replace('_', ' ', $charge->charge_type)) }}</span>
                                </td>
                                <td class="px-3 py-3 text-sm text-stone-800 dark:text-gray-200">
                                    @if($charge->charge_type === 'tax')
                                        <span class="inline-flex items-center gap-1.5 flex-wrap">
                                            <span>Tax ({{ number_format($reservation->getEffectiveTaxRate(), 2) }}%)</span>
                                            @if(user_can('add_room_charge'))
                                                <button
                                                    type="button"
                                                    wire:click="openTaxRateModal"
                                                    class="inline-flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition"
                                                    title="@lang('hotel::modules.folio.editTaxRate')"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </span>
                                    @elseif($charge->order_id && $charge->order && user_can('Show Order'))
                                        <button type="button"
                                           wire:click="viewLinkedOrder({{ $charge->order->id }})"
                                           class="inline-flex items-center gap-1 font-medium text-orange-800 hover:text-orange-950 underline decoration-orange-300/60 underline-offset-2 transition dark:text-orange-300 dark:hover:text-orange-100 text-left">
                                            <span>{{ $charge->getDisplayDescription() }}</span>
                                        </button>
                                    @else
                                        {{ $charge->getDisplayDescription() }}
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-sm text-right font-semibold tabular-nums text-stone-900 dark:text-white whitespace-nowrap">
                                    @if($editingChargeId === $charge->id)
                                        <div class="inline-flex items-center justify-end gap-1" x-data x-init="$nextTick(() => $refs.chargeAmountInput?.focus())">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                wire:model="editChargeAmount"
                                                wire:keydown.enter.prevent="saveEditCharge"
                                                wire:keydown.escape="cancelEditCharge"
                                                x-ref="chargeAmountInput"
                                                class="w-24 rounded-lg border-blue-300 bg-white px-2 py-1 text-right text-sm font-semibold text-stone-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 dark:border-blue-700 dark:bg-gray-800 dark:text-white"
                                            />
                                            <button type="button" wire:click="saveEditCharge" wire:loading.attr="disabled" wire:target="saveEditCharge" class="p-1 rounded-md text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/30">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            </button>
                                            <button type="button" wire:click="cancelEditCharge" class="p-1 rounded-md text-stone-400 hover:text-stone-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                        @error('editChargeAmount')
                                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                        @enderror
                                    @else
                                        {{ currency_format($charge->amount) }}
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if(!$charge->order_id)
                                        <div class="inline-flex items-center justify-center gap-0.5">
                                            @if(user_can('edit_room_charge'))
                                                <button
                                                    type="button"
                                                    wire:click="startEditCharge({{ $charge->id }})"
                                                    @disabled($editingChargeId === $charge->id)
                                                    class="p-1.5 rounded-lg text-stone-400 hover:text-blue-600 hover:bg-blue-50 transition disabled:opacity-40 dark:hover:bg-blue-950/30"
                                                    title="@lang('app.edit')"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                                </button>
                                            @endif
                                            @if(user_can('delete_room_charge'))
                                                <button wire:click="confirmDeleteCharge({{ $charge->id }})" class="p-1.5 rounded-lg text-stone-400 hover:text-rose-600 hover:bg-rose-50 transition dark:hover:bg-rose-950/30" title="@lang('app.delete')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-stone-500 dark:text-gray-400">
                                    @lang('hotel::modules.folio.noCharges')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($filteredCharges->isNotEmpty())
                        <tfoot class="bg-stone-50 dark:bg-gray-800 border-t border-stone-200 dark:border-gray-700">
                             <tr>
                                <td colspan="4" class="px-3 py-2.5 text-right text-sm font-bold text-stone-800 dark:text-white">
                                    @lang('hotel::modules.folio.subtotal')
                                </td>
                                <td class="px-3 py-2.5 text-right font-bold tabular-nums text-stone-900 dark:text-white">
                                    {{ currency_format($filteredCharges->sum('amount')) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="flex justify-end">
                <x-button type="button" wire:click="closeOtherChargesModal" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    @lang('app.close')
                </x-button>
            </div>
        </div>
    </x-slot>
</x-right-modal>
