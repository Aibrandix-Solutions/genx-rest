<?php

namespace Modules\Hotel\Livewire\Folio;

use Livewire\Component;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;
use Modules\Hotel\Entities\HotelPayment;
use Modules\Hotel\Entities\HotelSetting;
use Modules\Hotel\Support\HotelPaymentRecorder;
use Modules\Hotel\Services\FolioChargePresenter;
use Modules\Hotel\Services\FolioOrderChargeSync;
use App\Models\Order;
use App\Enums\ActivityEvent;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FolioManager extends Component
{
    use LivewireAlert;
    public $reservationNumber;
    public $reservation;
    public $charges;
    public $folioSummary = [];
    public $payments;
    public $balance = 0;
    public $totalCharges = 0;
    public $totalPayments = 0;
    public $hasRoomNightCharges = false;
    public $hotelName = '';
    public $paymentSurchargeEnabled = false;
    public $paymentProcessingRate = 0;
    public $businessMode = 'restaurant_primary';
    public $viewMode = 'single'; // single | consolidated | roomwise
    public $chargeReservationId;

    // Payment modal
    public $showPaymentModal = false;
    public $paymentAmount = 0;
    public $paymentMethod = 'cash';
    public $paymentType = 'settlement';
    public $paymentReference = '';
    public $paymentNotes = '';

    // Add charge modal
    public $showChargeModal = false;
    public $chargeType = 'minibar';
    public $chargeTypeCustom = '';
    public $chargeDescription = '';
    public $chargeAmount = 0;

    // Inline edit charge
    public $editingChargeId = null;
    public $editChargeAmount = 0;

    // Tax rate override modal
    public $showTaxRateModal = false;

    // Grouped folio display
    public $expandedRoomGroups = [];
    public $showOtherChargesModal = false;
    public $otherChargesFilterType = null;
    public $editingRoomGroupKey = null;
    public $editGroupPricePerNight = 0;
    public $editTaxRate = 0;
    public $defaultTaxRate = 0;

    public function mount($reservationNumber)
    {
        abort_unless(user_can('view_hotel_billing'), 403);
        $this->reservationNumber = $reservationNumber;
        $this->businessMode = function_exists('hotel_business_mode') ? hotel_business_mode() : 'restaurant_primary';
        
        $res = Reservation::where('reservation_number', $reservationNumber)->first();
        if ($res && $res->group_booking_id) {
            $this->viewMode = 'consolidated';
        }

        $this->loadData();
    }

    public function loadData()
    {
        $this->reservation = Reservation::with(['guest', 'room', 'room.roomType', 'orders', 'payments.receivedBy'])
            ->where('reservation_number', $this->reservationNumber)
            ->firstOrFail();

        // Load hotel name from settings
        $settings = HotelSetting::first();
        $this->hotelName = $settings->hotel_name ?? restaurant()->name ?? '';
        $this->paymentSurchargeEnabled = (bool) ($settings->enable_payment_surcharge ?? false);

        // Keep restaurant folio lines aligned with linked order totals (fixes stale Rs0.00 rows).
        foreach ($this->reservation->orders as $order) {
            if (FolioOrderChargeSync::shouldSync($order)) {
                FolioOrderChargeSync::sync($order);
            }
        }

        $this->reservation->refresh();
        $this->reservation->calculateTotal();

        if ($this->reservation->group_booking_id) {
            $groupReservations = Reservation::where('group_booking_id', $this->reservation->group_booking_id)->get();
            $resIds = $groupReservations->pluck('id');

            // All posted charges for all reservations in the group
            $this->charges = RoomCharge::with(['order', 'reservation.room'])
                ->whereIn('reservation_id', $resIds)
                ->orderBy('charge_date', 'asc')
                ->get();

            // Payments for all reservations in the group
            $this->payments = HotelPayment::with('receivedBy')
                ->whereIn('reservation_id', $resIds)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($this->viewMode === 'roomwise') {
                $this->folioSummary = FolioChargePresenter::summarize($this->reservation, $this->charges, '');
            } else {
                $this->folioSummary = FolioChargePresenter::summarize($this->reservation, $this->charges, 'consolidated');
            }
        } else {
            // All posted charges for this reservation
            $this->charges = RoomCharge::with('order')
                ->where('reservation_id', $this->reservation->id)
                ->orderBy('charge_date', 'asc')
                ->get();

            // Payments for this reservation
            $this->payments = $this->reservation->payments()
                ->orderBy('created_at', 'asc')
                ->get();

            $this->folioSummary = FolioChargePresenter::summarize($this->reservation, $this->charges);
        }

        $this->totalCharges = $this->folioSummary['subtotal'];

        $totalPaid = $this->payments->where('payment_type', '!=', HotelPayment::TYPE_REFUND)->sum('amount');
        $totalRefunds = $this->payments->where('payment_type', HotelPayment::TYPE_REFUND)->sum('amount');
        $this->totalPayments = $totalPaid - $totalRefunds;

        // Check if room night charges exist
        $this->hasRoomNightCharges = $this->charges->where('charge_type', RoomCharge::TYPE_ROOM_NIGHT)->isNotEmpty();

        if ($this->reservation->group_booking_id) {
            $this->balance = $this->totalCharges - $this->totalPayments;
        } else {
            $this->balance = (float) $this->reservation->balance_due;
        }
    }

    /**
     * Generate room night charges for checked-in reservations that are missing them
     */
    public function confirmGenerateRoomNightCharges()
    {
        $nights = $this->reservation->getNumberOfNights();
        $this->alert('warning', "This will generate room night charges for all {$nights} nights. Continue?", [
            'showConfirmButton' => true,
            'showCancelButton' => true,
            'confirmButtonText' => 'Yes, Generate',
            'cancelButtonText' => 'Cancel',
            'onConfirmed' => 'generateRoomNightChargesConfirmed',
        ]);
    }

    #[On('generateRoomNightChargesConfirmed')]
    public function generateRoomNightCharges()
    {
        abort_unless(user_can('add_room_charge'), 403);

        if ($this->hasRoomNightCharges) {
            $this->alert('error', 'Room night charges already exist for this reservation.');
            return;
        }

        if (!in_array($this->reservation->status, [Reservation::STATUS_CHECKED_IN, Reservation::STATUS_CONFIRMED])) {
            return;
        }

        DB::transaction(function () {
            $roomType = $this->reservation->room->roomType;
            $checkIn = Carbon::parse($this->reservation->check_in_date);
            $checkOut = Carbon::parse($this->reservation->checkout_date);
            $settings = HotelSetting::first();

            $roomChargesTotal = 0;
            $currentDate = $checkIn->copy();
            while ($currentDate->lt($checkOut)) {
                $nightlyRate = $this->reservation->getNightlyRateForDate($currentDate);

                RoomCharge::create([
                    'branch_id'      => $this->reservation->branch_id,
                    'reservation_id' => $this->reservation->id,
                    'charge_type' => RoomCharge::TYPE_ROOM_NIGHT,
                    'description' => 'Room ' . $this->reservation->room->room_number . ' - ' . $currentDate->format('d M Y'),
                    'amount' => $nightlyRate,
                    'charge_date' => $currentDate->toDateString(),
                ]);

                $roomChargesTotal += $nightlyRate;
                $currentDate->addDay();
            }

            // Apply extra occupancy charges per night
            $nights = $this->reservation->getNumberOfNights();
            $extraOccupancyPerNight = $roomType->calculateExtraOccupancyCharges(
                $this->reservation->adults,
                $this->reservation->children,
                0 // assuming no extra beds by default
            );

            if ($extraOccupancyPerNight > 0 && $nights > 0) {
                RoomCharge::create([
                    'branch_id'      => $this->reservation->branch_id,
                    'reservation_id' => $this->reservation->id,
                    'charge_type' => RoomCharge::TYPE_OTHER,
                    'description' => 'Extra occupancy charges (' . $nights . ' nights)',
                    'amount' => $extraOccupancyPerNight * $nights,
                    'charge_date' => $checkIn->toDateString(),
                ]);
                $roomChargesTotal += $extraOccupancyPerNight * $nights;
            }

            // Apply tax on room charges
            $taxRate = $this->reservation->getEffectiveTaxRate();
            if ($taxRate > 0) {
                $taxAmount = round($roomChargesTotal * ($taxRate / 100), 2);
                if ($taxAmount > 0) {
                    RoomCharge::create([
                        'branch_id'      => $this->reservation->branch_id,
                        'reservation_id' => $this->reservation->id,
                        'charge_type' => RoomCharge::TYPE_TAX,
                        'description' => 'Tax (' . number_format($taxRate, 2, '.', '') . '%)',
                        'amount' => $taxAmount,
                        'charge_date' => $checkIn->toDateString(),
                    ]);
                    $roomChargesTotal += $taxAmount;
                }
            }

            // Apply service charge on room charges
            if ($settings && $settings->service_charge_rate > 0) {
                $serviceAmount = $settings->calculateServiceCharge($roomChargesTotal);
                if ($serviceAmount > 0) {
                    RoomCharge::create([
                        'branch_id'      => $this->reservation->branch_id,
                        'reservation_id' => $this->reservation->id,
                        'charge_type' => RoomCharge::TYPE_SERVICE,
                        'description' => 'Service charge (' . $settings->service_charge_rate . '%)',
                        'amount' => $serviceAmount,
                        'charge_date' => $checkIn->toDateString(),
                    ]);
                }
            }

            $this->reservation->calculateTotal();
        });

        $this->loadData();

        $this->alert('success', 'Room night charges generated successfully.');
    }

    // --- Payment Methods ---

    public function openPaymentModal()
    {
        abort_unless(user_can('process_hotel_payment'), 403);

        if ($this->balance <= 0) {
            $this->alert('info', $this->balance < 0
                ? __('hotel::modules.folio.useRefundForCredit')
                : __('hotel::modules.folio.folioAlreadySettled'), [
                'toast' => true,
                'position' => 'top-end',
            ]);

            return;
        }

        $this->paymentAmount = $this->balance;
        $this->paymentMethod = 'cash';
        $this->paymentType = 'settlement';
        $this->paymentReference = '';
        $this->paymentNotes = '';
        $this->paymentProcessingRate = 0;
        $this->showPaymentModal = true;
    }

    public function openRefundModal()
    {
        abort_unless(user_can('process_hotel_payment'), 403);

        if ($this->balance >= 0) {
            $this->alert('info', __('hotel::modules.folio.noCreditToRefund'), [
                'toast' => true,
                'position' => 'top-end',
            ]);

            return;
        }

        $this->paymentAmount = round(abs($this->balance), 2);
        $this->paymentMethod = 'cash';
        $this->paymentType = 'refund';
        $this->paymentReference = '';
        $this->paymentNotes = '';
        $this->paymentProcessingRate = 0;
        $this->showPaymentModal = true;
    }

    public function savePayment()
    {
        abort_unless(user_can('process_hotel_payment'), 403);

        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentMethod' => 'required|in:cash,card,bank_transfer,upi,other',
            'paymentType' => 'required|in:advance,deposit,settlement,refund',
            'paymentProcessingRate' => 'nullable|numeric|min:0|max:100',
            'paymentReference' => 'nullable|string|max:255',
            'paymentNotes' => 'nullable|string|max:1000',
        ]);

        $balanceBeforePayment = (float) $this->balance;
        $totalCollected = (float) $this->paymentAmount;

        if ($this->paymentType === 'refund') {
            if ($balanceBeforePayment >= 0) {
                $this->addError('paymentAmount', __('hotel::modules.folio.noCreditToRefund'));

                return;
            }

            $maxRefund = round(abs($balanceBeforePayment), 2);
            if ($this->paymentAmount > $maxRefund) {
                $this->addError('paymentAmount', __('hotel::modules.folio.refundExceedsCredit', [
                    'amount' => currency_format($maxRefund),
                ]));

                return;
            }
        } elseif ($this->paymentType === 'settlement') {
            if ($balanceBeforePayment <= 0) {
                $this->addError('paymentAmount', __('hotel::modules.folio.folioAlreadySettled'));

                return;
            }

            if ($this->paymentAmount > $balanceBeforePayment) {
                $this->addError('paymentAmount', __('hotel::modules.folio.paymentExceedsBalance', [
                    'amount' => currency_format($balanceBeforePayment),
                ]));

                return;
            }
        }

        DB::transaction(function () use (&$totalCollected) {
            if ($this->reservation->group_booking_id) {
                $groupReservations = Reservation::where('group_booking_id', $this->reservation->group_booking_id)->get();
                $remainingPayment = (float) $this->paymentAmount;

                if ($this->paymentType === HotelPayment::TYPE_REFUND) {
                    foreach ($groupReservations as $res) {
                        if ($remainingPayment <= 0) break;
                        $credit = (float) $res->balance_due;
                        if ($credit < 0) {
                            $refundAmount = min($remainingPayment, abs($credit));
                            HotelPayment::create([
                                'branch_id' => $res->branch_id,
                                'restaurant_id' => $res->restaurant_id,
                                'reservation_id' => $res->id,
                                'amount' => $refundAmount,
                                'payment_method' => $this->paymentMethod,
                                'payment_type' => 'refund',
                                'reference_number' => $this->paymentReference ?: null,
                                'notes' => $this->paymentNotes ?: null,
                                'received_by_user_id' => auth()->id(),
                            ]);
                            $res->calculateTotal();
                            $remainingPayment -= $refundAmount;
                        }
                    }
                    if ($remainingPayment > 0) {
                        HotelPayment::create([
                            'branch_id' => $this->reservation->branch_id,
                            'restaurant_id' => $this->reservation->restaurant_id,
                            'reservation_id' => $this->reservation->id,
                            'amount' => $remainingPayment,
                            'payment_method' => $this->paymentMethod,
                            'payment_type' => 'refund',
                            'reference_number' => $this->paymentReference ?: null,
                            'notes' => $this->paymentNotes ?: null,
                            'received_by_user_id' => auth()->id(),
                        ]);
                        $this->reservation->calculateTotal();
                    }
                    $totalCollected = $this->paymentAmount;
                } else {
                    foreach ($groupReservations as $res) {
                        if ($remainingPayment <= 0) break;
                        $due = (float) $res->balance_due;
                        if ($due > 0) {
                            $allocatedPayment = min($remainingPayment, $due);
                            $result = HotelPaymentRecorder::record(
                                $res,
                                $allocatedPayment,
                                $this->paymentMethod,
                                $this->paymentType,
                                $this->paymentReference ?: null,
                                $this->paymentNotes ?: null,
                                auth()->id(),
                                (float) $this->paymentProcessingRate,
                                $this->paymentSurchargeEnabled,
                            );
                            $res->calculateTotal();
                            $remainingPayment -= $allocatedPayment;
                            $totalCollected += $result['total_collected'];
                        }
                    }
                    if ($remainingPayment > 0) {
                        $result = HotelPaymentRecorder::record(
                            $this->reservation,
                            $remainingPayment,
                            $this->paymentMethod,
                            $this->paymentType,
                            $this->paymentReference ?: null,
                            $this->paymentNotes ?: null,
                            auth()->id(),
                            (float) $this->paymentProcessingRate,
                            $this->paymentSurchargeEnabled,
                        );
                        $this->reservation->calculateTotal();
                        $totalCollected += $result['total_collected'];
                    }
                }
            } else {
                if ($this->paymentType === HotelPayment::TYPE_REFUND) {
                    HotelPayment::create([
                        'restaurant_id' => $this->reservation->restaurant_id,
                        'reservation_id' => $this->reservation->id,
                        'amount' => $this->paymentAmount,
                        'payment_method' => $this->paymentMethod,
                        'payment_type' => $this->paymentType,
                        'reference_number' => $this->paymentReference ?: null,
                        'notes' => $this->paymentNotes ?: null,
                        'received_by_user_id' => auth()->id(),
                    ]);
                    $totalCollected = $this->paymentAmount;
                } else {
                    $result = HotelPaymentRecorder::record(
                        $this->reservation,
                        (float) $this->paymentAmount,
                        $this->paymentMethod,
                        $this->paymentType,
                        $this->paymentReference ?: null,
                        $this->paymentNotes ?: null,
                        auth()->id(),
                        (float) $this->paymentProcessingRate,
                        $this->paymentSurchargeEnabled,
                    );
                    $totalCollected = $result['total_collected'];
                }
                $this->reservation->calculateTotal();
            }
        });

        if ($this->paymentType === HotelPayment::TYPE_SETTLEMENT) {
            ActivityLogger::recordEvent(
                activityEvent: ActivityEvent::FolioSettled,
                description: 'Folio settlement payment recorded for reservation ' . ($this->reservation->reservation_number ?? $this->reservation->id),
                subject: $this->reservation,
                properties: [
                    'reservation_id' => $this->reservation->id,
                    'reservation_number' => $this->reservation->reservation_number ?? null,
                    'amount' => $totalCollected,
                    'payment_method' => $this->paymentMethod,
                ],
                restaurantId: $this->reservation->restaurant_id ? (int) $this->reservation->restaurant_id : null,
            );
        }

        $this->showPaymentModal = false;
        $this->loadData();

        $this->alert('success', 'Payment of ' . currency_format($totalCollected) . ' recorded successfully.');
    }

    // --- Add Manual Charge Methods ---

    public function openChargeModal()
    {
        abort_unless(user_can('add_room_charge'), 403);
        $this->chargeType = 'minibar';
        $this->chargeTypeCustom = '';
        $this->chargeDescription = '';
        $this->chargeAmount = 0;
        $this->chargeReservationId = $this->reservation->id;
        $this->showChargeModal = true;
    }

    public function saveCharge()
    {
        abort_unless(user_can('add_room_charge'), 403);

        $rules = [
            'chargeType' => 'required|in:room_night,minibar,laundry,service,tax,other',
            'chargeDescription' => 'required|string|max:500',
            'chargeAmount' => 'required|numeric|min:0.01',
        ];

        if ($this->chargeType === 'other') {
            $rules['chargeTypeCustom'] = ['required', 'string', 'max:50', 'regex:/^[^:]+$/'];
        }

        $this->validate($rules);

        $description = $this->chargeType === 'other'
            ? RoomCharge::encodeCustomTypeDescription($this->chargeTypeCustom, $this->chargeDescription)
            : $this->chargeDescription;

        $targetReservationId = $this->reservation->group_booking_id
            ? $this->chargeReservationId
            : $this->reservation->id;

        DB::transaction(function () use ($description, $targetReservationId) {
            $charge = RoomCharge::create([
                'reservation_id' => $targetReservationId,
                'charge_type' => $this->chargeType,
                'description' => $description,
                'amount' => $this->chargeAmount,
                'charge_date' => now()->toDateString(),
            ]);

            $res = Reservation::find($targetReservationId);
            if ($res) {
                $res->calculateTotal();
            }

            ActivityLogger::recordEvent(
                activityEvent: ActivityEvent::FolioChargeAdded,
                description: "Folio charge added: {$description}",
                subject: $res,
                properties: [
                    'reservation_id' => $targetReservationId,
                    'reservation_number' => $res->reservation_number ?? null,
                    'charge_id' => $charge->id,
                    'charge_type' => $this->chargeType,
                    'amount' => $this->chargeAmount,
                    'description' => $description,
                ],
                restaurantId: $this->reservation->restaurant_id ? (int) $this->reservation->restaurant_id : null,
            );
        });

        $this->showChargeModal = false;
        $this->loadData();

        $this->alert('success', 'Charge added successfully.');
    }

    // --- Edit Charge (inline) ---

    public function startEditCharge($chargeId)
    {
        abort_unless(user_can('edit_room_charge'), 403);

        $charge = RoomCharge::where('reservation_id', $this->reservation->id)->find($chargeId);

        if (!$charge) {
            return;
        }

        if ($charge->order_id) {
            $this->alert('error', __('hotel::modules.folio.cannotEditOrderCharge'), [
                'toast' => true,
                'position' => 'top-end',
            ]);

            return;
        }

        $this->cancelEditRoomGroupPrice();
        $this->editingChargeId = $charge->id;
        $this->editChargeAmount = (float) $charge->amount;
    }

    public function cancelEditCharge()
    {
        $this->editingChargeId = null;
        $this->editChargeAmount = 0;
        $this->resetErrorBag('editChargeAmount');
    }

    public function saveEditCharge()
    {
        abort_unless(user_can('edit_room_charge'), 403);

        $this->validate([
            'editChargeAmount' => 'required|numeric|min:0.01',
        ]);

        $charge = RoomCharge::where('reservation_id', $this->reservation->id)
            ->find($this->editingChargeId);

        if (!$charge) {
            $this->cancelEditCharge();

            return;
        }

        if ($charge->order_id) {
            $this->alert('error', __('hotel::modules.folio.cannotEditOrderCharge'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            $this->cancelEditCharge();

            return;
        }

        $newAmount = round((float) $this->editChargeAmount, 2);

        if ((float) $charge->amount === $newAmount) {
            $this->cancelEditCharge();

            return;
        }

        $isPaymentSurcharge = $charge->isPaymentSurcharge();
        $oldAmount = (float) $charge->amount;

        DB::transaction(function () use ($charge, $newAmount) {
            if (in_array($charge->charge_type, [RoomCharge::TYPE_ROOM_NIGHT, RoomCharge::TYPE_OTHER], true)) {
                $charge->update(['amount' => $newAmount]);
                $this->reservation->refresh();
                $this->reservation->recalculateTaxCharge();
                $this->reservation->recalculateLinkedServiceCharge();
            } elseif ($charge->charge_type === RoomCharge::TYPE_TAX) {
                $this->reservation->syncTaxRateFromAmount($newAmount);
                $this->reservation->recalculateLinkedServiceCharge();
            } else {
                $charge->update(['amount' => $newAmount]);
            }

            $this->reservation->calculateTotal();
        });

        ActivityLogger::recordEvent(
            activityEvent: ActivityEvent::FolioChargeUpdated,
            description: "Folio charge updated: {$charge->description}",
            subject: $this->reservation,
            properties: [
                'reservation_id' => $this->reservation->id,
                'charge_id' => $charge->id,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'description' => $charge->description,
            ],
            restaurantId: $this->reservation->restaurant_id ? (int) $this->reservation->restaurant_id : null,
        );

        $this->cancelEditCharge();
        $this->loadData();

        $message = __('hotel::modules.folio.chargeUpdated');
        if ($isPaymentSurcharge) {
            $message .= ' ' . __('hotel::modules.folio.paymentSurchargeEditHint');
        }

        $this->alert('success', $message, [
            'toast' => true,
            'position' => 'top-end',
            'timer' => 2000,
        ]);
    }

    // --- Delete Charge ---

    public $pendingDeleteChargeId = null;

    public function confirmDeleteCharge($chargeId)
    {
        $this->pendingDeleteChargeId = $chargeId;

        $charge = RoomCharge::where('reservation_id', $this->reservation->id)->find($chargeId);
        $message = __('hotel::modules.folio.confirmDeleteCharge');

        if ($charge) {
            $balanceAfterDelete = $this->totalCharges - (float) $charge->amount - $this->totalPayments;
            if ($balanceAfterDelete < 0) {
                $message .= ' ' . __('hotel::modules.folio.deleteChargeCreatesCredit', [
                    'amount' => currency_format(abs($balanceAfterDelete)),
                ]);
            }
        }

        $this->alert('warning', $message, [
            'showConfirmButton' => true,
            'showCancelButton' => true,
            'confirmButtonText' => 'Yes, Delete',
            'cancelButtonText' => 'Cancel',
            'onConfirmed' => 'deleteChargeConfirmed',
        ]);
    }

    #[On('deleteChargeConfirmed')]
    public function deleteCharge($chargeId = null)
    {
        $chargeId = $chargeId ?? $this->pendingDeleteChargeId;
        abort_unless(user_can('delete_room_charge'), 403);
        $charge = RoomCharge::where('reservation_id', $this->reservation->id)->find($chargeId);

        if (!$charge) {
            return;
        }

        // Don't allow deleting auto-posted restaurant charges (tied to orders)
        if ($charge->order_id) {
            $this->alert('error', 'Cannot delete charges linked to orders. Cancel the order instead.');
            return;
        }

        DB::transaction(function () use ($charge) {
            ActivityLogger::recordEvent(
                activityEvent: ActivityEvent::FolioChargeDeleted,
                description: "Folio charge deleted: {$charge->description}",
                subject: $this->reservation,
                properties: [
                    'reservation_id' => $this->reservation->id,
                    'charge_id' => $charge->id,
                    'charge_type' => $charge->charge_type,
                    'amount' => $charge->amount,
                    'description' => $charge->description,
                ],
                restaurantId: $this->reservation->restaurant_id ? (int) $this->reservation->restaurant_id : null,
            );

            $charge->delete();
            $this->reservation->calculateTotal();
        });

        $this->loadData();

        $this->alert('success', 'Charge removed.');
    }

    // --- Tax Rate Override ---

    public function openTaxRateModal()
    {
        abort_unless(user_can('add_room_charge'), 403);

        $settings = HotelSetting::where('branch_id', $this->reservation->branch_id)->first();
        $this->defaultTaxRate = $settings ? (float) $settings->tax_rate : 0;
        $this->editTaxRate = $this->reservation->getEffectiveTaxRate();
        $this->showTaxRateModal = true;
    }

    public function saveTaxRate()
    {
        abort_unless(user_can('add_room_charge'), 403);

        $this->validate([
            'editTaxRate' => 'required|numeric|min:0|max:100',
        ]);

        DB::transaction(function () {
            $settings = HotelSetting::where('branch_id', $this->reservation->branch_id)->first();
            $defaultRate = $settings ? (float) $settings->tax_rate : 0.0;
            $newRate = round((float) $this->editTaxRate, 2);

            $this->reservation->update([
                'tax_rate_override' => $newRate === $defaultRate ? null : $newRate,
            ]);

            $this->reservation->refresh();
            $this->reservation->recalculateTaxCharge();
            $this->reservation->recalculateLinkedServiceCharge();
            $this->reservation->calculateTotal();
        });

        $this->showTaxRateModal = false;
        $this->loadData();

        $this->alert('success', __('hotel::modules.folio.taxRateUpdated'));
    }

    public function toggleRoomGroupExpand(string $groupKey): void
    {
        if (in_array($groupKey, $this->expandedRoomGroups, true)) {
            $this->expandedRoomGroups = array_values(array_filter(
                $this->expandedRoomGroups,
                fn (string $key) => $key !== $groupKey,
            ));
        } else {
            $this->expandedRoomGroups[] = $groupKey;
        }
    }

    public function startEditRoomGroupPrice(string $groupKey): void
    {
        abort_unless(user_can('edit_room_charge'), 403);

        $this->cancelEditCharge();

        $group = collect($this->folioSummary['room_groups'] ?? [])->firstWhere('key', $groupKey);

        if (! $group) {
            return;
        }

        $this->editingRoomGroupKey = $groupKey;
        $this->editGroupPricePerNight = (float) $group['price_per_night'];
    }

    public function cancelEditRoomGroupPrice(): void
    {
        $this->editingRoomGroupKey = null;
        $this->editGroupPricePerNight = 0;
        $this->resetErrorBag('editGroupPricePerNight');
    }

    public function saveEditRoomGroupPrice(): void
    {
        abort_unless(user_can('edit_room_charge'), 403);

        $this->validate([
            'editGroupPricePerNight' => 'required|numeric|min:0.01',
        ]);

        $group = collect($this->folioSummary['room_groups'] ?? [])
            ->firstWhere('key', $this->editingRoomGroupKey);

        if (! $group) {
            $this->cancelEditRoomGroupPrice();

            return;
        }

        $newPrice = round((float) $this->editGroupPricePerNight, 2);

        if ($newPrice === (float) $group['price_per_night']) {
            $this->cancelEditRoomGroupPrice();

            return;
        }

        $chargeIds = $group['charges']->pluck('id')->all();

        DB::transaction(function () use ($chargeIds, $newPrice) {
            RoomCharge::query()
                ->where('reservation_id', $this->reservation->id)
                ->whereIn('id', $chargeIds)
                ->where('charge_type', RoomCharge::TYPE_ROOM_NIGHT)
                ->update(['amount' => $newPrice]);

            $this->reservation->refresh();
            $this->reservation->recalculateTaxCharge();
            $this->reservation->recalculateLinkedServiceCharge();
            $this->reservation->calculateTotal();
        });

        $this->cancelEditRoomGroupPrice();
        $this->loadData();

        $this->alert('success', __('hotel::modules.folio.groupPriceUpdated', [
            'nights' => $group['nights'],
        ]), [
            'toast' => true,
            'position' => 'top-end',
            'timer' => 2000,
        ]);
    }

    public function openOtherChargesModal(?string $typeFilter = null): void
    {
        $this->otherChargesFilterType = $typeFilter;
        $this->showOtherChargesModal = true;
    }

    public function closeOtherChargesModal(): void
    {
        $this->showOtherChargesModal = false;
        $this->otherChargesFilterType = null;
    }

    public function viewLinkedOrder(int $orderId): void
    {
        abort_unless(user_can('Show Order'), 403);

        $order = Order::query()->find($orderId);

        if (! $order) {
            $this->alert('error', __('messages.orderNotFound'), [
                'toast' => true,
                'position' => 'top-end',
            ]);

            return;
        }

        if ($order->status === 'kot') {
            $this->redirect($order->staffDetailUrl(), navigate: true);

            return;
        }

        $this->dispatch('showOrderDetail', id: $order->id);
    }

    public function getRoomNumbersListProperty()
    {
        if ($this->reservation->group_booking_id) {
            $resIds = Reservation::where('group_booking_id', $this->reservation->group_booking_id)->pluck('id');
            $rooms = \Modules\Hotel\Entities\Room::whereIn('id', function($q) use ($resIds) {
                $q->select('room_id')->from('hotel_reservations')->whereIn('id', $resIds);
            })->pluck('room_number');
            return $rooms->implode(', ');
        }
        return $this->reservation->room?->room_number;
    }

    public function getGroupReservationsListProperty()
    {
        if ($this->reservation->group_booking_id) {
            return Reservation::with(['room', 'room.roomType'])
                ->where('group_booking_id', $this->reservation->group_booking_id)
                ->get();
        }
        return collect([$this->reservation]);
    }

    public function render()
    {
        return view('hotel::livewire.folio.folio-manager')->layout('layouts.app');
    }
}
