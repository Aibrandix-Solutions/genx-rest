@php
    $accent = match ($businessMode) {
        'hotel_primary' => ['bar' => 'from-indigo-500 via-violet-500 to-indigo-600', 'ring' => 'ring-indigo-500/20', 'chip' => 'bg-indigo-50 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200'],
        'equal' => ['bar' => 'from-amber-500 via-orange-500 to-amber-600', 'ring' => 'ring-amber-500/20', 'chip' => 'bg-amber-50 text-amber-900 dark:bg-amber-950/50 dark:text-amber-100'],
        default => ['bar' => 'from-emerald-500 via-teal-500 to-emerald-600', 'ring' => 'ring-emerald-500/20', 'chip' => 'bg-emerald-50 text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100'],
    };
@endphp

<div class="min-h-[calc(100vh-4rem)] bg-gradient-to-br from-stone-100 via-stone-50 to-amber-50/30 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950">
    <div class="max-w-6xl mx-auto p-4 sm:p-6 space-y-6">
        {{-- Guest ledger header --}}
        <div class="relative overflow-hidden rounded-2xl border border-stone-200/80 bg-white/90 shadow-xl shadow-stone-300/20 backdrop-blur dark:border-gray-700 dark:bg-gray-900/90 dark:shadow-none">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $accent['bar'] }}"></div>
            <div class="p-6 sm:p-8">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div class="space-y-3">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold tracking-widest uppercase {{ $accent['chip'] }}">
                            @lang('hotel::modules.folio.guestFolio')
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-stone-900 dark:text-white">
                            {{ $reservation->guest->full_name }}
                        </h1>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-stone-600 dark:text-gray-400">
                            <span class="font-medium text-stone-800 dark:text-gray-200">
                                @lang('hotel::modules.reservation.room') {{ $reservation->room->room_number }}
                            </span>
                            <span class="text-stone-300 dark:text-gray-600">|</span>
                            <span>{{ $reservation->room->roomType->name }}</span>
                            <span class="text-stone-300 dark:text-gray-600">|</span>
                            <span>{{ $reservation->check_in_date->format('d M') }} – {{ $reservation->checkout_date->format('d M Y') }}</span>
                            <span class="text-stone-300 dark:text-gray-600">|</span>
                            <span>{{ $reservation->getNumberOfNights() }} @lang('hotel::modules.folio.nights')</span>
                        </div>
                        <p class="text-xs font-mono text-stone-500 dark:text-gray-500">
                            {{ $reservation->reservation_number }}
                            <span @class([
                                'ml-2 px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wide',
                                'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' => $reservation->status === 'checked_in',
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200' => $reservation->status === 'confirmed',
                                'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200' => $reservation->status === 'checked_out',
                                'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200' => $reservation->status === 'cancelled',
                            ])>{{ str_replace('_', ' ', $reservation->status) }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if(!$hasRoomNightCharges && in_array($reservation->status, ['checked_in', 'confirmed']) && user_can('add_room_charge'))
                        <button wire:click="confirmGenerateRoomNightCharges" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-stone-800 rounded-xl hover:bg-stone-900 transition shadow-sm dark:bg-stone-700 dark:hover:bg-stone-600">
                            @lang('hotel::modules.folio.generateRoomCharges')
                        </button>
                        @endif
                        @if(user_can('add_room_charge'))
                        <button wire:click="openChargeModal" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-amber-950 bg-amber-200/80 border border-amber-300/60 rounded-xl hover:bg-amber-200 transition dark:text-amber-100 dark:bg-amber-900/30 dark:border-amber-700">
                            @lang('hotel::modules.folio.addCharge')
                        </button>
                        @endif
                        @if(user_can('process_hotel_payment') && $balance > 0)
                        <button wire:click="openPaymentModal" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-emerald-700 rounded-xl hover:bg-emerald-800 transition shadow-sm">
                            @lang('hotel::modules.folio.addPayment')
                        </button>
                        @endif
                        @if(user_can('process_hotel_payment') && $balance < 0)
                        <button wire:click="openRefundModal" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-xl hover:bg-blue-800 transition shadow-sm dark:bg-blue-600 dark:hover:bg-blue-700">
                            @lang('hotel::modules.folio.issueRefund')
                        </button>
                        @endif
                        <a href="{{ route('hotel.invoice', $reservation->id) }}" target="_blank" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-stone-700 bg-white border border-stone-200 rounded-xl hover:bg-stone-50 transition dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                            @lang('hotel::modules.folio.printInvoice')
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Balance ledger strip --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            @php
                $balanceLabel = $balance < 0
                    ? __('hotel::modules.folio.creditBalance')
                    : __('hotel::modules.folio.balanceDue');
                $balanceValue = $balance < 0
                    ? currency_format(abs($balance))
                    : currency_format(max(0, $balance));
                $balanceClass = $balance > 0
                    ? 'text-rose-600 dark:text-rose-400'
                    : ($balance < 0 ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400');
                $balanceHint = $balance == 0 ? __('hotel::modules.folio.paidInFull') : null;
            @endphp
            @foreach([
                ['label' => __('hotel::modules.folio.totalCharges'), 'value' => currency_format($totalCharges), 'class' => 'text-stone-900 dark:text-white'],
                ['label' => __('hotel::modules.folio.totalPaid'), 'value' => currency_format($totalPayments), 'class' => 'text-emerald-700 dark:text-emerald-400'],
                ['label' => $balanceLabel, 'value' => $balanceValue, 'class' => $balanceClass],
                ['label' => __('hotel::modules.folio.nights'), 'value' => $reservation->getNumberOfNights(), 'class' => 'text-stone-900 dark:text-white'],
            ] as $card)
            <div class="rounded-xl border border-stone-200/80 bg-white/80 p-4 shadow-sm ring-1 {{ $accent['ring'] }} dark:border-gray-700 dark:bg-gray-900/60">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-stone-500 dark:text-gray-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-xl font-semibold tabular-nums {{ $card['class'] }}">{{ $card['value'] }}</p>
                @if(($card['label'] ?? '') === $balanceLabel && $balanceHint)
                    <p class="mt-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">{{ $balanceHint }}</p>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Charges ledger --}}
        <div class="rounded-2xl border border-stone-200/80 bg-white/90 shadow-lg overflow-hidden dark:border-gray-700 dark:bg-gray-900/90">
            <div class="px-6 py-4 border-b border-stone-100 dark:border-gray-800 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-stone-900 dark:text-white">@lang('hotel::modules.folio.charges')</h2>
                <div class="flex items-center gap-3">
                    @if(($folioSummary['other_charges'] ?? collect())->isNotEmpty())
                        <button
                            type="button"
                            wire:click="openOtherChargesModal"
                            class="text-xs font-medium text-amber-800 hover:text-amber-950 underline decoration-amber-300/60 underline-offset-2 transition dark:text-amber-200 dark:hover:text-amber-100"
                        >
                            @lang('hotel::modules.folio.viewAllOtherCharges')
                        </button>
                    @endif
                    <span class="text-xs text-stone-500 dark:text-gray-500">
                        {{ count($folioSummary['room_groups'] ?? []) + count($folioSummary['type_rows'] ?? []) }} @lang('app.items')
                    </span>
                </div>
            </div>
            @include('hotel::livewire.folio.partials.folio-charges-summary', [
                'folioSummary' => $folioSummary,
                'reservation' => $reservation,
                'interactive' => true,
                'expandedRoomGroups' => $expandedRoomGroups,
                'editingChargeId' => $editingChargeId,
                'editingRoomGroupKey' => $editingRoomGroupKey,
            ])
        </div>

        {{-- Payments ledger --}}
        <div class="rounded-2xl border border-stone-200/80 bg-white/90 shadow-lg overflow-hidden dark:border-gray-700 dark:bg-gray-900/90">
            <div class="px-6 py-4 border-b border-stone-100 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-stone-900 dark:text-white">@lang('hotel::modules.folio.payments')</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-stone-50/80 dark:bg-gray-800/80 text-[10px] font-bold uppercase tracking-widest text-stone-500 dark:text-gray-400">
                            <th class="px-4 py-3">@lang('hotel::modules.folio.date')</th>
                            <th class="px-4 py-3">@lang('hotel::modules.folio.type')</th>
                            <th class="px-4 py-3">@lang('hotel::modules.folio.method')</th>
                            <th class="px-4 py-3">@lang('hotel::modules.folio.reference')</th>
                            <th class="px-4 py-3">@lang('hotel::modules.folio.receivedBy')</th>
                            <th class="px-4 py-3 text-right">@lang('hotel::modules.folio.amount')</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-gray-800">
                        @forelse($payments as $payment)
                        <tr class="hover:bg-stone-50/80 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="px-4 py-3.5 text-sm tabular-nums text-stone-600 dark:text-gray-300 whitespace-nowrap">{{ $payment->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span @class([
                                    'inline-flex px-2 py-0.5 rounded-md text-[11px] font-semibold uppercase',
                                    'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-200' => $payment->payment_type === 'advance',
                                    'bg-purple-100 text-purple-800 dark:bg-purple-950/50 dark:text-purple-200' => $payment->payment_type === 'deposit',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200' => $payment->payment_type === 'settlement',
                                    'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-200' => $payment->payment_type === 'refund',
                                ])>{{ ucfirst($payment->payment_type) }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-stone-700 dark:text-gray-300">{{ str_replace('_', ' ', $payment->payment_method) }}</td>
                            <td class="px-4 py-3.5 text-sm text-stone-500 dark:text-gray-400">{{ $payment->reference_number ?? '—' }}</td>
                            <td class="px-4 py-3.5 text-sm text-stone-500 dark:text-gray-400">{{ $payment->receivedBy?->name ?? '—' }}</td>
                            <td class="px-4 py-3.5 text-sm text-right font-semibold tabular-nums whitespace-nowrap {{ $payment->payment_type === 'refund' ? 'text-rose-600' : 'text-emerald-700 dark:text-emerald-400' }}">
                                {{ $payment->payment_type === 'refund' ? '−' : '' }}{{ currency_format($payment->amount) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-stone-500 dark:text-gray-400">@lang('hotel::modules.folio.noPayments')</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($payments->isNotEmpty())
                    <tfoot class="bg-stone-50/90 dark:bg-gray-800/50 border-t-2 border-stone-200 dark:border-gray-700">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right text-sm font-bold text-stone-800 dark:text-white">@lang('hotel::modules.folio.totalPaid')</td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums text-emerald-700 dark:text-emerald-400">{{ currency_format($totalPayments) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Payment Modal --}}
    <x-right-modal wire:model.live="showPaymentModal">
        <x-slot name="title">@lang('hotel::modules.folio.recordPayment')</x-slot>
        <x-slot name="content">
            @php $currencySymbol = restaurant()->currency->currency_symbol ?? 'Rs'; @endphp
            <form wire:submit.prevent="savePayment">
                <div class="space-y-4">
                    @php
                        $modalBalanceLabel = $balance < 0
                            ? __('hotel::modules.folio.creditBalance')
                            : __('hotel::modules.folio.balanceDue');
                        $modalBalanceValue = $balance < 0
                            ? currency_format(abs($balance))
                            : currency_format(max(0, $balance));
                        $modalBalanceClass = $balance > 0
                            ? 'text-rose-600'
                            : ($balance < 0 ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600');
                    @endphp
                    <div class="bg-stone-50 dark:bg-gray-800 p-4 rounded-xl border border-stone-200 dark:border-gray-700">
                        <div class="flex justify-between text-sm">
                            <span class="text-stone-600 dark:text-gray-400">{{ $modalBalanceLabel }}</span>
                            <span class="font-bold tabular-nums {{ $modalBalanceClass }}">{{ $modalBalanceValue }}</span>
                        </div>
                        @if($balance == 0)
                            <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">@lang('hotel::modules.folio.paidInFull')</p>
                        @endif
                    </div>

                    @if($paymentSurchargeEnabled)
                    <div
                        class="space-y-4"
                        x-data="{
                            amount: @entangle('paymentAmount'),
                            method: @entangle('paymentMethod'),
                            rate: @entangle('paymentProcessingRate'),
                            paymentType: @entangle('paymentType'),
                            currencySymbol: @js($currencySymbol),
                            get showSurchargeFields() {
                                if (this.paymentType === 'refund') return false;
                                return ['card', 'bank_transfer'].includes(this.method);
                            },
                            get surchargeAmount() {
                                const amt = parseFloat(this.amount) || 0;
                                const rt = parseFloat(this.rate) || 0;
                                if (!this.showSurchargeFields || amt <= 0 || rt <= 0) return 0;
                                return Math.round((amt * rt / 100) * 100) / 100;
                            },
                            get totalCollected() {
                                const amt = parseFloat(this.amount) || 0;
                                return Math.round((amt + this.surchargeAmount) * 100) / 100;
                            }
                        }"
                    >
                        <div>
                            <x-label for="paymentType" value="{{ __('hotel::modules.folio.paymentType') }}" />
                            <select id="paymentType" x-model="paymentType" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                @if($balance < 0)
                                <option value="refund">@lang('hotel::modules.folio.refund')</option>
                                @else
                                <option value="advance">@lang('hotel::modules.folio.advance')</option>
                                <option value="deposit">@lang('hotel::modules.folio.deposit')</option>
                                <option value="settlement">@lang('hotel::modules.folio.settlement')</option>
                                @endif
                            </select>
                            <x-input-error for="paymentType" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="paymentAmount" value="{{ __('hotel::modules.folio.amount') }}" />
                            <input id="paymentAmount" type="number" step="0.01" min="0.01" x-model="amount" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" />
                            <x-input-error for="paymentAmount" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="paymentMethod" value="{{ __('hotel::modules.folio.paymentMethod') }}" />
                            <select id="paymentMethod" x-model="method" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                <option value="cash">@lang('hotel::modules.folio.cash')</option>
                                <option value="card">@lang('hotel::modules.folio.card')</option>
                                <option value="bank_transfer">@lang('hotel::modules.folio.bankTransfer')</option>
                                <option value="upi">@lang('hotel::modules.folio.upi')</option>
                                <option value="other">@lang('hotel::modules.folio.otherMethod')</option>
                            </select>
                            <x-input-error for="paymentMethod" class="mt-2" />
                        </div>

                        @include('hotel::partials.payment-surcharge-fields', ['rateInputId' => 'paymentProcessingRate'])

                        <div>
                            <x-label for="paymentReference" value="{{ __('hotel::modules.folio.reference') }}" />
                            <x-input id="paymentReference" type="text" class="block w-full mt-1" wire:model="paymentReference" placeholder="Transaction ID / Receipt #" />
                            <x-input-error for="paymentReference" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="paymentNotes" value="{{ __('hotel::modules.folio.notes') }}" />
                            <textarea id="paymentNotes" wire:model="paymentNotes" rows="2" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                            <x-input-error for="paymentNotes" class="mt-2" />
                        </div>
                    </div>
                    @else
                    <div class="space-y-4">
                        <div>
                            <x-label for="paymentType" value="{{ __('hotel::modules.folio.paymentType') }}" />
                            <select id="paymentType" wire:model="paymentType" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                @if($balance < 0)
                                <option value="refund">@lang('hotel::modules.folio.refund')</option>
                                @else
                                <option value="advance">@lang('hotel::modules.folio.advance')</option>
                                <option value="deposit">@lang('hotel::modules.folio.deposit')</option>
                                <option value="settlement">@lang('hotel::modules.folio.settlement')</option>
                                @endif
                            </select>
                            <x-input-error for="paymentType" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="paymentAmount" value="{{ __('hotel::modules.folio.amount') }}" />
                            <input id="paymentAmount" type="number" step="0.01" min="0.01" wire:model="paymentAmount" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" />
                            <x-input-error for="paymentAmount" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="paymentMethod" value="{{ __('hotel::modules.folio.paymentMethod') }}" />
                            <select id="paymentMethod" wire:model="paymentMethod" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                <option value="cash">@lang('hotel::modules.folio.cash')</option>
                                <option value="card">@lang('hotel::modules.folio.card')</option>
                                <option value="bank_transfer">@lang('hotel::modules.folio.bankTransfer')</option>
                                <option value="upi">@lang('hotel::modules.folio.upi')</option>
                                <option value="other">@lang('hotel::modules.folio.otherMethod')</option>
                            </select>
                            <x-input-error for="paymentMethod" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="paymentReference" value="{{ __('hotel::modules.folio.reference') }}" />
                            <x-input id="paymentReference" type="text" class="block w-full mt-1" wire:model="paymentReference" placeholder="Transaction ID / Receipt #" />
                            <x-input-error for="paymentReference" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="paymentNotes" value="{{ __('hotel::modules.folio.notes') }}" />
                            <textarea id="paymentNotes" wire:model="paymentNotes" rows="2" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                            <x-input-error for="paymentNotes" class="mt-2" />
                        </div>
                    </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showPaymentModal', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        @lang('app.cancel')
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled" class="bg-emerald-700 hover:bg-emerald-800">
                        @lang('hotel::modules.folio.recordPayment')
                    </x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>

    {{-- Add Charge Modal --}}
    <x-right-modal wire:model.live="showChargeModal">
        <x-slot name="title">@lang('hotel::modules.folio.addCharge')</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveCharge">
                <div class="space-y-4" x-data="{ showCustomType: @js($chargeType === 'other') }">
                    <div>
                        <x-label for="chargeType" value="{{ __('hotel::modules.folio.chargeType') }}" />
                        <select id="chargeType" wire:model="chargeType"
                            x-on:change="showCustomType = ($event.target.value === 'other')"
                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <option value="minibar">@lang('hotel::modules.folio.minibar')</option>
                            <option value="laundry">@lang('hotel::modules.folio.laundry')</option>
                            <option value="service">@lang('hotel::modules.folio.service')</option>
                            <option value="room_night">@lang('hotel::modules.folio.roomNight')</option>
                            <option value="tax">@lang('hotel::modules.folio.tax')</option>
                            <option value="other">@lang('hotel::modules.folio.other')</option>
                        </select>
                        <x-input-error for="chargeType" class="mt-2" />
                    </div>

                    <div x-show="showCustomType" x-cloak x-transition.opacity.duration.150ms>
                        <x-label for="chargeTypeCustom" value="{{ __('hotel::modules.folio.customChargeType') }}" />
                        <x-input id="chargeTypeCustom" type="text" class="block w-full mt-1" wire:model="chargeTypeCustom" placeholder="{{ __('hotel::modules.folio.customChargeTypePlaceholder') }}" />
                        <x-input-error for="chargeTypeCustom" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="chargeDescription" value="{{ __('hotel::modules.folio.description') }}" />
                        <x-input id="chargeDescription" type="text" class="block w-full mt-1" wire:model="chargeDescription" required placeholder="e.g. Minibar - 2x Water, 1x Soda" />
                        <x-input-error for="chargeDescription" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="chargeAmount" value="{{ __('hotel::modules.folio.amount') }}" />
                        <x-input id="chargeAmount" type="number" step="0.01" min="0.01" class="block w-full mt-1" wire:model="chargeAmount" required />
                        <x-input-error for="chargeAmount" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showChargeModal', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        @lang('app.cancel')
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled" class="bg-amber-600 hover:bg-amber-700">
                        @lang('hotel::modules.folio.addCharge')
                    </x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>

    {{-- Edit Tax Rate Modal --}}
    <x-right-modal wire:model.live="showTaxRateModal">
        <x-slot name="title">@lang('hotel::modules.folio.editTaxRate')</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="saveTaxRate">
                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        @lang('hotel::modules.folio.editTaxRateHint', ['rate' => number_format($defaultTaxRate, 2)])
                    </p>

                    <div>
                        <x-label for="editTaxRate" value="{{ __('hotel::modules.folio.taxRate') }}" />
                        <div class="flex items-center gap-2 mt-1">
                            <x-input id="editTaxRate" type="number" step="0.01" min="0" max="100" class="block w-full" wire:model="editTaxRate" required />
                            <span class="text-gray-500 dark:text-gray-400 font-medium">%</span>
                        </div>
                        <x-input-error for="editTaxRate" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showTaxRateModal', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        @lang('app.cancel')
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-700">
                        @lang('app.save')
                    </x-button>
                </div>
            </form>
        </x-slot>
    </x-right-modal>

    @include('hotel::livewire.folio.partials.other-charges-modal')
</div>
