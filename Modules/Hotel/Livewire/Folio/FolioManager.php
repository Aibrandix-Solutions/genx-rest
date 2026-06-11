<?php

namespace Modules\Hotel\Livewire\Folio;

use Livewire\Component;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;
use Modules\Hotel\Entities\HotelPayment;
use Modules\Hotel\Entities\HotelSetting;
use Modules\Hotel\Services\FolioOrderChargeSync;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FolioManager extends Component
{
    use LivewireAlert;
    public $reservationNumber;
    public $reservation;
    public $charges;
    public $payments;
    public $balance = 0;
    public $totalCharges = 0;
    public $totalPayments = 0;
    public $hasRoomNightCharges = false;
    public $hotelName = '';
    public $businessMode = 'restaurant_primary';

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
    public $chargeDescription = '';
    public $chargeAmount = 0;

    public function mount($reservationNumber)
    {
        abort_unless(user_can('view_hotel_billing'), 403);
        $this->reservationNumber = $reservationNumber;
        $this->businessMode = function_exists('hotel_business_mode') ? hotel_business_mode() : 'restaurant_primary';
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

        // Keep restaurant folio lines aligned with linked order totals (fixes stale Rs0.00 rows).
        foreach ($this->reservation->orders as $order) {
            if (FolioOrderChargeSync::shouldSync($order)) {
                FolioOrderChargeSync::sync($order);
            }
        }

        $this->reservation->refresh();
        $this->reservation->calculateTotal();

        // All posted charges (room nights, restaurant/room-service, minibar, etc.)
        $this->charges = RoomCharge::with('order')
            ->where('reservation_id', $this->reservation->id)
            ->orderBy('charge_date', 'asc')
            ->get();

        // Payments
        $this->payments = $this->reservation->payments()
            ->orderBy('created_at', 'asc')
            ->get();

        $this->totalCharges = $this->charges->sum('amount');

        $totalPaid = $this->payments->where('payment_type', '!=', HotelPayment::TYPE_REFUND)->sum('amount');
        $totalRefunds = $this->payments->where('payment_type', HotelPayment::TYPE_REFUND)->sum('amount');
        $this->totalPayments = $totalPaid - $totalRefunds;

        // Check if room night charges exist
        $this->hasRoomNightCharges = $this->charges->where('charge_type', RoomCharge::TYPE_ROOM_NIGHT)->isNotEmpty();

        $this->balance = (float) $this->reservation->balance_due;
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
            if ($settings && $settings->tax_rate > 0) {
                $taxAmount = $settings->calculateTax($roomChargesTotal);
                if ($taxAmount > 0) {
                    RoomCharge::create([
                        'reservation_id' => $this->reservation->id,
                        'charge_type' => RoomCharge::TYPE_TAX,
                        'description' => 'Tax (' . $settings->tax_rate . '%)',
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
        $this->showPaymentModal = true;
    }

    public function savePayment()
    {
        abort_unless(user_can('process_hotel_payment'), 403);

        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentMethod' => 'required|in:cash,card,bank_transfer,upi,other',
            'paymentType' => 'required|in:advance,deposit,settlement,refund',
            'paymentReference' => 'nullable|string|max:255',
            'paymentNotes' => 'nullable|string|max:1000',
        ]);

        $balanceBeforePayment = (float) $this->balance;

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

        DB::transaction(function () {
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

            $this->reservation->calculateTotal();
        });

        $this->showPaymentModal = false;
        $this->loadData();

        $this->alert('success', 'Payment of ' . currency_format($this->paymentAmount) . ' recorded successfully.');
    }

    // --- Add Manual Charge Methods ---

    public function openChargeModal()
    {
        abort_unless(user_can('add_room_charge'), 403);
        $this->chargeType = 'minibar';
        $this->chargeDescription = '';
        $this->chargeAmount = 0;
        $this->showChargeModal = true;
    }

    public function saveCharge()
    {
        abort_unless(user_can('add_room_charge'), 403);

        $this->validate([
            'chargeType' => 'required|in:room_night,minibar,laundry,service,tax,other',
            'chargeDescription' => 'required|string|max:500',
            'chargeAmount' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () {
            RoomCharge::create([
                'reservation_id' => $this->reservation->id,
                'charge_type' => $this->chargeType,
                'description' => $this->chargeDescription,
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
            $charge->delete();
            $this->reservation->calculateTotal();
        });

        $this->loadData();

        $this->alert('success', 'Charge removed.');
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

    public function render()
    {
        return view('hotel::livewire.folio.folio-manager')->layout('layouts.app');
    }
}
