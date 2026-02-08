<div class="max-w-5xl mx-auto p-4 space-y-6">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                    @lang('hotel::modules.folio.guestFolio'): {{ $reservation->guest->full_name }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    @lang('hotel::modules.reservation.room') {{ $reservation->room->room_number }}
                    ({{ $reservation->room->roomType->name }})
                    &middot; {{ $reservation->check_in_date->format('d M') }} - {{ $reservation->checkout_date->format('d M Y') }}
                    &middot; {{ $reservation->getNumberOfNights() }} @lang('hotel::modules.folio.nights')
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                    {{ $reservation->reservation_number }}
                    &middot;
                    <span @class([
                        'px-1.5 py-0.5 rounded text-xs font-medium',
                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $reservation->status === 'checked_in',
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $reservation->status === 'confirmed',
                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $reservation->status === 'checked_out',
                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $reservation->status === 'cancelled',
                    ])>{{ ucfirst(str_replace('_', ' ', $reservation->status)) }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if(!$hasRoomNightCharges && in_array($reservation->status, ['checked_in', 'confirmed']) && user_can('add_room_charge'))
                <button wire:click="generateRoomNightCharges" wire:confirm="This will generate room night charges for all {{ $reservation->getNumberOfNights() }} nights. Continue?" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Generate Room Charges
                </button>
                @endif
                @if(user_can('add_room_charge'))
                <button wire:click="openChargeModal" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    @lang('hotel::modules.folio.addCharge')
                </button>
                @endif
                @if(user_can('process_hotel_payment'))
                <button wire:click="openPaymentModal" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    @lang('hotel::modules.folio.addPayment')
                </button>
                @endif
                <a href="{{ route('hotel.invoice', $reservation->id) }}" target="_blank" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    @lang('hotel::modules.folio.printInvoice')
                </a>
            </div>
        </div>
    </div>

    {{-- Balance Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 text-center">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">@lang('hotel::modules.folio.totalCharges')</p>
            <p class="text-lg font-bold text-gray-800 dark:text-white mt-1">{{ currency_format($totalCharges + $totalOrders) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 text-center">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">@lang('hotel::modules.folio.totalPaid')</p>
            <p class="text-lg font-bold text-green-600 mt-1">{{ currency_format($totalPayments) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 text-center">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">@lang('hotel::modules.folio.balanceDue')</p>
            <p class="text-lg font-bold {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }} mt-1">{{ currency_format($balance) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 text-center">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">@lang('hotel::modules.folio.nights')</p>
            <p class="text-lg font-bold text-gray-800 dark:text-white mt-1">{{ $reservation->getNumberOfNights() }}</p>
        </div>
    </div>

    {{-- Charges Table --}}
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">@lang('hotel::modules.folio.charges')</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="p-3 text-xs font-medium text-gray-500 uppercase dark:text-gray-400">@lang('hotel::modules.folio.date')</th>
                        <th class="p-3 text-xs font-medium text-gray-500 uppercase dark:text-gray-400">@lang('hotel::modules.folio.type')</th>
                        <th class="p-3 text-xs font-medium text-gray-500 uppercase dark:text-gray-400">@lang('hotel::modules.folio.description')</th>
                        <th class="p-3 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">@lang('hotel::modules.folio.amount')</th>
                        <th class="p-3 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-center w-16"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($charges as $charge)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="p-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $charge->charge_date->format('d M Y') }}
                            </td>
                            <td class="p-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                <span @class([
                                    'px-2 py-0.5 rounded text-xs font-medium',
                                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' => $charge->charge_type === 'room_night',
                                    'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300' => $charge->charge_type === 'restaurant',
                                    'bg-pink-100 text-pink-700 dark:bg-pink-900 dark:text-pink-300' => $charge->charge_type === 'minibar',
                                    'bg-cyan-100 text-cyan-700 dark:bg-cyan-900 dark:text-cyan-300' => $charge->charge_type === 'laundry',
                                    'bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-300' => $charge->charge_type === 'service',
                                    'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300' => !in_array($charge->charge_type, ['room_night','restaurant','minibar','laundry','service']),
                                ])>
                                    {{ ucfirst(str_replace('_', ' ', $charge->charge_type)) }}
                                </span>
                            </td>
                            <td class="p-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $charge->description }}
                            </td>
                            <td class="p-3 text-sm text-right font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ currency_format($charge->amount) }}
                            </td>
                            <td class="p-3 text-center">
                                @if(!$charge->order_id && user_can('delete_room_charge'))
                                    <button wire:click="deleteCharge({{ $charge->id }})" wire:confirm="@lang('hotel::modules.folio.confirmDeleteCharge')" class="text-red-500 hover:text-red-700 transition" title="@lang('app.delete')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @if($orders->isEmpty())
                        <tr>
                            <td colspan="5" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                @lang('hotel::modules.folio.noCharges')
                            </td>
                        </tr>
                        @endif
                    @endforelse

                    {{-- Pending Orders --}}
                    @if($orders->isNotEmpty())
                        <tr class="bg-yellow-50 dark:bg-yellow-900/20">
                            <td colspan="5" class="p-2 text-xs font-semibold text-yellow-700 dark:text-yellow-400 uppercase tracking-wide">
                                @lang('hotel::modules.folio.pendingOrders')
                            </td>
                        </tr>
                    @endif
                    @foreach($orders as $order)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 bg-yellow-50/30 dark:bg-yellow-900/10">
                            <td class="p-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $order->created_at->format('d M Y') }}
                            </td>
                            <td class="p-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                                    Room Service
                                </span>
                            </td>
                            <td class="p-3 text-sm text-gray-700 dark:text-gray-300">
                                Order #{{ $order->order_number }}
                                <span class="text-xs px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-300 ml-1">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="p-3 text-sm text-right font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ currency_format($order->total) }}
                            </td>
                            <td class="p-3"></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="border-t-2 border-gray-200 dark:border-gray-600">
                        <td colspan="3" class="p-3 text-right text-sm font-bold text-gray-800 dark:text-white">@lang('hotel::modules.folio.totalCharges')</td>
                        <td class="p-3 text-right font-bold text-gray-800 dark:text-white whitespace-nowrap">{{ currency_format($totalCharges + $totalOrders) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Payments Table --}}
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">@lang('hotel::modules.folio.payments')</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="p-3 text-xs font-medium text-gray-500 uppercase dark:text-gray-400">@lang('hotel::modules.folio.date')</th>
                        <th class="p-3 text-xs font-medium text-gray-500 uppercase dark:text-gray-400">@lang('hotel::modules.folio.type')</th>
                        <th class="p-3 text-xs font-medium text-gray-500 uppercase dark:text-gray-400">@lang('hotel::modules.folio.method')</th>
                        <th class="p-3 text-xs font-medium text-gray-500 uppercase dark:text-gray-400">@lang('hotel::modules.folio.reference')</th>
                        <th class="p-3 text-xs font-medium text-gray-500 uppercase dark:text-gray-400">@lang('hotel::modules.folio.receivedBy')</th>
                        <th class="p-3 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">@lang('hotel::modules.folio.amount')</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="p-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $payment->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td class="p-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                <span @class([
                                    'px-2 py-0.5 rounded text-xs font-medium',
                                    'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' => $payment->payment_type === 'advance',
                                    'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' => $payment->payment_type === 'deposit',
                                    'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' => $payment->payment_type === 'settlement',
                                    'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' => $payment->payment_type === 'refund',
                                ])>
                                    {{ ucfirst($payment->payment_type) }}
                                </span>
                            </td>
                            <td class="p-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                            </td>
                            <td class="p-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $payment->reference_number ?? '-' }}
                            </td>
                            <td class="p-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $payment->receivedBy?->name ?? '-' }}
                            </td>
                            <td class="p-3 text-sm text-right font-medium whitespace-nowrap {{ $payment->payment_type === 'refund' ? 'text-red-600' : 'text-green-600' }}">
                                {{ $payment->payment_type === 'refund' ? '-' : '' }}{{ currency_format($payment->amount) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                @lang('hotel::modules.folio.noPayments')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($payments->isNotEmpty())
                <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="border-t-2 border-gray-200 dark:border-gray-600">
                        <td colspan="5" class="p-3 text-right text-sm font-bold text-gray-800 dark:text-white">@lang('hotel::modules.folio.totalPaid')</td>
                        <td class="p-3 text-right font-bold text-green-600 whitespace-nowrap">{{ currency_format($totalPayments) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Payment Modal --}}
    <x-right-modal wire:model.live="showPaymentModal">
        <x-slot name="title">@lang('hotel::modules.folio.recordPayment')</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="savePayment">
                <div class="space-y-4">
                    {{-- Balance Info --}}
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">@lang('hotel::modules.folio.currentBalance')</span>
                            <span class="font-bold {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }}">{{ currency_format($balance) }}</span>
                        </div>
                    </div>

                    <div>
                        <x-label for="paymentType" value="{{ __('hotel::modules.folio.paymentType') }}" />
                        <select id="paymentType" wire:model="paymentType" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <option value="advance">@lang('hotel::modules.folio.advance')</option>
                            <option value="deposit">@lang('hotel::modules.folio.deposit')</option>
                            <option value="settlement">@lang('hotel::modules.folio.settlement')</option>
                            <option value="refund">@lang('hotel::modules.folio.refund')</option>
                        </select>
                        <x-input-error for="paymentType" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="paymentAmount" value="{{ __('hotel::modules.folio.amount') }}" />
                        <x-input id="paymentAmount" type="number" step="0.01" min="0.01" class="block w-full mt-1" wire:model="paymentAmount" required />
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

                <div class="mt-6 flex justify-end gap-3">
                    <x-button type="button" wire:click="$set('showPaymentModal', false)" class="bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        @lang('app.cancel')
                    </x-button>
                    <x-button type="submit" wire:loading.attr="disabled" class="bg-green-600 hover:bg-green-700">
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
                <div class="space-y-4">
                    <div>
                        <x-label for="chargeType" value="{{ __('hotel::modules.folio.chargeType') }}" />
                        <select id="chargeType" wire:model="chargeType" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <option value="minibar">@lang('hotel::modules.folio.minibar')</option>
                            <option value="laundry">@lang('hotel::modules.folio.laundry')</option>
                            <option value="service">@lang('hotel::modules.folio.service')</option>
                            <option value="room_night">@lang('hotel::modules.folio.roomNight')</option>
                            <option value="tax">@lang('hotel::modules.folio.tax')</option>
                            <option value="other">@lang('hotel::modules.folio.other')</option>
                        </select>
                        <x-input-error for="chargeType" class="mt-2" />
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
</div>
