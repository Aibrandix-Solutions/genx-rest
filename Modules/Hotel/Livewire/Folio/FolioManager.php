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
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FolioManager extends Component
{
    use LivewireAlert;
    public $reservationNumber;
    public $reservation;
    public $charges;
    public $orders;
    public $payments;
    public $balance = 0;
    public $totalCharges = 0;
    public $totalOrders = 0;
    public $totalPayments = 0;
    public $hasRoomNightCharges = false;
    public $hotelName = '';
    public $paymentSurchargeEnabled = false;
    public $paymentProcessingRate = 0;

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

    // Tax rate override modal
    public $showTaxRateModal = false;
    public $editTaxRate = 0;
    public $defaultTaxRate = 0;

    public function mount($reservationNumber)
    {
        abort_unless(user_can('view_hotel_billing'), 403);
        $this->reservationNumber = $reservationNumber;
        $this->loadData();
    }

    public function loadData()
    {
        $this->reservation = Reservation::with(['guest', 'room', 'room.roomType', 'orders', 'payments.receivedBy'])
            ->where('reservation_number', $this->reservationNumber)
            ->firstOrFail();

        // Load hotel name from settings
        $settings = HotelSetting::where('restaurant_id', restaurant()->id)->first();
        $this->hotelName = $settings->hotel_name ?? restaurant()->name ?? '';
        $this->paymentSurchargeEnabled = (bool) ($settings->enable_payment_surcharge ?? false);

        // All posted charges (room nights, restaurant/room-service, minibar, etc.)
        $this->charges = RoomCharge::where('reservation_id', $this->reservation->id)
            ->orderBy('charge_date', 'asc')
            ->get();

        // Pending room-service orders not yet posted as charges (still in kitchen / kot)
        $postedOrderIds = $this->charges->whereNotNull('order_id')->pluck('order_id')->toArray();
        $this->orders = $this->reservation->orders()
            ->where('status', '!=', 'canceled')
            ->whereNotIn('id', $postedOrderIds)
            ->orderBy('created_at', 'asc')
            ->get();

        // Payments
        $this->payments = $this->reservation->payments()
            ->orderBy('created_at', 'asc')
            ->get();

        $this->totalCharges = $this->charges->sum('amount');
        $this->totalOrders = $this->orders->sum('total'); // pending orders not yet posted

        $totalPaid = $this->payments->where('payment_type', '!=', HotelPayment::TYPE_REFUND)->sum('amount');
        $totalRefunds = $this->payments->where('payment_type', HotelPayment::TYPE_REFUND)->sum('amount');
        $this->totalPayments = $totalPaid - $totalRefunds;

        // Check if room night charges exist
        $this->hasRoomNightCharges = $this->charges->where('charge_type', RoomCharge::TYPE_ROOM_NIGHT)->isNotEmpty();

        // Balance = all posted charges + pending order totals - payments
        $this->balance = ($this->totalCharges + $this->totalOrders) - $this->totalPayments;
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
            $settings = HotelSetting::where('restaurant_id', $this->reservation->restaurant_id)->first();

            $roomChargesTotal = 0;
            $currentDate = $checkIn->copy();
            while ($currentDate->lt($checkOut)) {
                $nightlyRate = $roomType->getPriceForDate($currentDate);

                RoomCharge::create([
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
        $this->paymentAmount = $this->balance > 0 ? $this->balance : 0;
        $this->paymentMethod = 'cash';
        $this->paymentType = 'settlement';
        $this->paymentReference = '';
        $this->paymentNotes = '';
        $this->paymentProcessingRate = 0;
        $this->showPaymentModal = true;
    }

    public function paymentAppliesSurcharge(): bool
    {
        return $this->paymentSurchargeEnabled
            && $this->paymentType !== HotelPayment::TYPE_REFUND
            && in_array($this->paymentMethod, ['card', 'bank_transfer'], true);
    }

    public function calculatedPaymentSurcharge(): float
    {
        if (
            !$this->paymentSurchargeEnabled
            || $this->paymentType === HotelPayment::TYPE_REFUND
            || !in_array($this->paymentMethod, ['card', 'bank_transfer'], true)
        ) {
            return 0;
        }

        return HotelPaymentRecorder::calculateSurcharge(
            (float) $this->paymentAmount,
            (float) $this->paymentProcessingRate
        );
    }

    public function calculatedPaymentTotal(): float
    {
        return round((float) $this->paymentAmount + $this->calculatedPaymentSurcharge(), 2);
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

        $totalCollected = $this->paymentAmount;

        DB::transaction(function () use (&$totalCollected) {
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
        });

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

        DB::transaction(function () {
            RoomCharge::create([
                'reservation_id' => $this->reservation->id,
                'charge_type' => $this->chargeType,
                'description' => $description,
                'amount' => $this->chargeAmount,
                'charge_date' => now()->toDateString(),
            ]);

            $this->reservation->calculateTotal();
        });

        $this->showChargeModal = false;
        $this->loadData();

        $this->alert('success', 'Charge added successfully.');
    }

    // --- Delete Charge ---

    public $pendingDeleteChargeId = null;

    public function confirmDeleteCharge($chargeId)
    {
        $this->pendingDeleteChargeId = $chargeId;
        $this->alert('warning', __('hotel::modules.folio.confirmDeleteCharge'), [
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

        $settings = HotelSetting::where('restaurant_id', $this->reservation->restaurant_id)->first();
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
            $settings = HotelSetting::where('restaurant_id', $this->reservation->restaurant_id)->first();
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

    public function render()
    {
        return view('hotel::livewire.folio.folio-manager')->layout('layouts.app');
    }
}
