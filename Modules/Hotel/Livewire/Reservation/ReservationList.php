<?php

namespace Modules\Hotel\Livewire\Reservation;

use Livewire\Component;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;
use Modules\Hotel\Services\OrderFolioSettlement;
use Modules\Hotel\Entities\HotelPayment;
use Modules\Hotel\Entities\HotelSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationList extends Component
{
    use LivewireAlert;
    public $showCreateReservation = false;
    public $showEditReservation = false;
    public $editingReservationId = null;
    public $search = '';
    public $statusFilter = 'all';
    public $dateFilter = 'all';

    public function mount()
    {
        abort_unless(user_can('view_hotel_reservations'), 403);
    }

    #[On('reservation-saved')]
    public function reservationSaved()
    {
        $this->showCreateReservation = false;
        $this->showEditReservation = false;
        $this->editingReservationId = null;
        $this->dispatch('$refresh');
    }

    // --- Check-in with advance payment + room night charges ---

    public $showCheckInModal = false;
    public $checkInReservation = null;
    public $checkInAdvanceAmount = 0;
    public $checkInPaymentMethod = 'cash';
    public $checkInNotes = '';
    public $checkInTotalAmount = 0;

    public function openCheckIn($id)
    {
        abort_unless(user_can('check_in_guest'), 403);
        $this->checkInReservation = Reservation::with(['guest', 'room.roomType'])->find($id);

        if (!$this->checkInReservation || $this->checkInReservation->status !== Reservation::STATUS_CONFIRMED) {
            return;
        }

        // Calculate total using dynamic pricing
        $this->checkInTotalAmount = $this->calculateStayTotal($this->checkInReservation);

        // Determine suggested advance based on hotel settings
        $settings = HotelSetting::where('restaurant_id', restaurant()->id)->first();
        $this->checkInAdvanceAmount = $settings ? $settings->calculateDeposit($this->checkInTotalAmount) : 0;
        $this->checkInPaymentMethod = 'cash';
        $this->checkInNotes = '';
        $this->showCheckInModal = true;
    }

    public function processCheckIn()
    {
        abort_unless(user_can('check_in_guest'), 403);

        $this->validate([
            'checkInAdvanceAmount' => 'required|numeric|min:0',
            'checkInPaymentMethod' => 'required|string',
        ]);

        if (!$this->checkInReservation) {
            return;
        }

        DB::transaction(function () {
            $reservation = $this->checkInReservation;
            $settings = HotelSetting::where('restaurant_id', restaurant()->id)->first();

            // 1. Generate room night charges
            $this->generateRoomNightCharges($reservation);

            // 1b. Early check-in surcharge
            if ($settings && (float) $settings->early_checkin_charge_per_hour > 0) {
                $defaultCheckIn = Carbon::parse(
                    $reservation->check_in_date->toDateString() . ' ' . ($settings->default_check_in_time ?? '14:00')
                );
                $actualCheckIn = now();

                if ($actualCheckIn->lt($defaultCheckIn)) {
                    $hoursEarly = max(1, (int) ceil($actualCheckIn->floatDiffInHours($defaultCheckIn)));
                    $earlyCharge = $hoursEarly * (float) $settings->early_checkin_charge_per_hour;

                    RoomCharge::create([
                        'reservation_id' => $reservation->id,
                        'charge_type'    => RoomCharge::TYPE_SERVICE,
                        'description'    => "Early check-in surcharge ({$hoursEarly}h before " . ($settings->default_check_in_time ?? '14:00') . ')',
                        'amount'         => $earlyCharge,
                        'charge_date'    => now()->toDateString(),
                    ]);
                }
            }

            // 2. Record advance payment if amount > 0
            if ($this->checkInAdvanceAmount > 0) {
                HotelPayment::create([
                    'restaurant_id' => restaurant()->id,
                    'reservation_id' => $reservation->id,
                    'amount' => $this->checkInAdvanceAmount,
                    'payment_method' => $this->checkInPaymentMethod,
                    'payment_type' => HotelPayment::TYPE_ADVANCE,
                    'notes' => $this->checkInNotes ?: 'Advance payment at check-in',
                    'received_by_user_id' => auth()->id(),
                ]);
            }

            // 3. Update reservation status
            $reservation->update([
                'status' => Reservation::STATUS_CHECKED_IN,
                'actual_check_in' => now(),
                'created_by_user_id' => $reservation->created_by_user_id ?? auth()->id(),
            ]);

            // 4. Recalculate totals (charges + payments)
            $reservation->calculateTotal();

            // 5. Update room status
            if ($reservation->room) {
                $reservation->room->update(['status' => 'occupied']);
            }
        });

        $this->showCheckInModal = false;
        $this->checkInReservation = null;

        $this->alert('success', 'Guest checked in successfully.');
        $this->dispatch('$refresh');
    }

    /**
     * Generate room night charges for the entire stay using dynamic pricing
     */
    protected function generateRoomNightCharges(Reservation $reservation)
    {
        $roomType = $reservation->room->roomType;
        $checkIn  = $reservation->check_in_date->copy();
        $checkOut = $reservation->checkout_date->copy();
        $settings = HotelSetting::where('restaurant_id', restaurant()->id)->first();

        $roomChargesTotal = 0;
        $currentDate = $checkIn->copy();
        while ($currentDate->lt($checkOut)) {
            $nightlyRate = $roomType->getPriceForDate($currentDate);

            RoomCharge::create([
                'reservation_id' => $reservation->id,
                'charge_type'    => RoomCharge::TYPE_ROOM_NIGHT,
                'description'    => 'Room ' . $reservation->room->room_number . ' - ' . $currentDate->format('d M Y'),
                'amount'         => $nightlyRate,
                'charge_date'    => $currentDate->toDateString(),
            ]);

            $roomChargesTotal += $nightlyRate;
            $currentDate->addDay();
        }

        // Apply tax on room charges (if configured in hotel settings)
        if ($settings && $settings->tax_rate > 0) {
            $taxAmount = $settings->calculateTax($roomChargesTotal);
            if ($taxAmount > 0) {
                RoomCharge::create([
                    'reservation_id' => $reservation->id,
                    'charge_type'    => RoomCharge::TYPE_TAX,
                    'description'    => 'Tax (' . $settings->tax_rate . '%)',
                    'amount'         => $taxAmount,
                    'charge_date'    => $checkIn->toDateString(),
                ]);
            }
        }

        // Apply service charge on room charges (if configured in hotel settings)
        if ($settings && $settings->service_charge_rate > 0) {
            $serviceAmount = $settings->calculateServiceCharge($roomChargesTotal);
            if ($serviceAmount > 0) {
                RoomCharge::create([
                    'reservation_id' => $reservation->id,
                    'charge_type'    => RoomCharge::TYPE_SERVICE,
                    'description'    => 'Service charge (' . $settings->service_charge_rate . '%)',
                    'amount'         => $serviceAmount,
                    'charge_date'    => $checkIn->toDateString(),
                ]);
            }
        }
    }

    /**
     * Calculate stay total using dynamic pricing per night
     */
    protected function calculateStayTotal(Reservation $reservation): float
    {
        $roomType = $reservation->room->roomType;
        $checkIn = Carbon::parse($reservation->check_in_date);
        $checkOut = Carbon::parse($reservation->checkout_date);

        $total = 0;
        $current = $checkIn->copy();
        while ($current->lt($checkOut)) {
            $total += (float)$roomType->getPriceForDate($current);
            $current->addDay();
        }

        return $total;
    }

    // --- Existing simple checkIn kept for backward compat (used by old blade) ---

    public function checkIn($id)
    {
        // Redirect to the modal-based check-in
        $this->openCheckIn($id);
    }

    // --- Create reservation ---

    public $create_guest_id = '';
    public $create_room_id = ''; // kept for backward compat / single-room shortcut
    public $create_check_in_date = '';
    public $create_check_out_date = '';
    public $create_room_type_id = '';
    public $create_adults = 1;
    public $create_children = 0;
    public $create_notes = '';
    public $create_booking_source = 'walk-in';
    
    public $available_rooms = [];

    /**
     * Multi-room selection: array of selected rooms with per-room occupancy
     * Format: [ ['room_id' => int, 'adults' => int, 'children' => int], ... ]
     */
    public $selected_rooms = [];
    public $maxRoomsPerBooking = 10;

    protected function rules()
    {
        return [
            'create_guest_id' => 'required|exists:hotel_guests,id',
            'create_check_in_date' => 'required|date|after_or_equal:today',
            'create_check_out_date' => 'required|date|after:create_check_in_date',
            'selected_rooms' => 'required|array|min:1',
            'selected_rooms.*.room_id' => 'required|exists:hotel_rooms,id',
            'selected_rooms.*.adults' => 'required|integer|min:1',
            'selected_rooms.*.children' => 'integer|min:0',
            'create_notes' => 'nullable|string',
            'create_booking_source' => 'nullable|string|max:100',
        ];
    }

    public function updatedCreateRoomTypeId()
    {
        $this->findAvailableRooms();
    }

    public function updatedCreateCheckInDate()
    {
        $this->findAvailableRooms();
    }

    public function updatedCreateCheckOutDate()
    {
        $this->findAvailableRooms();
    }

    public $showCreateGuest = false;
    public $new_guest_first_name = '';
    public $new_guest_last_name = '';
    public $new_guest_email = '';
    public $new_guest_phone = '';

    public function saveGuest()
    {
        abort_unless(user_can('create_guest'), 403);

        $this->validate([
            'new_guest_first_name' => 'required|string|max:255',
            'new_guest_last_name' => 'required|string|max:255',
            'new_guest_email' => 'nullable|email|max:255',
            'new_guest_phone' => 'nullable|string|max:20',
        ]);

        $guest = \Modules\Hotel\Entities\Guest::create([
            'restaurant_id' => restaurant()->id,
            'first_name' => $this->new_guest_first_name,
            'last_name' => $this->new_guest_last_name,
            'email' => $this->new_guest_email,
            'phone' => $this->new_guest_phone,
        ]);

        // Attempt to link to existing customer
        $guest->linkToCustomer();

        $this->create_guest_id = $guest->id;
        $this->showCreateGuest = false;
        
        // Reset guest form
        $this->new_guest_first_name = '';
        $this->new_guest_last_name = '';
        $this->new_guest_email = '';
        $this->new_guest_phone = '';

        $this->alert('success', 'Guest added successfully');
    }

    public function findAvailableRooms()
    {
        if (!$this->create_check_in_date || !$this->create_check_out_date) {
            $this->available_rooms = [];
            return;
        }

        $checkIn = Carbon::parse($this->create_check_in_date);
        $checkOut = Carbon::parse($this->create_check_out_date);

        $query = \Modules\Hotel\Entities\Room::where('status', '!=', 'maintenance')
            ->where('status', '!=', 'blocked')
            ->where('status', '!=', 'reserved')
            ->where('status', '!=', 'occupied')
            ->where('status', '!=', 'cleaning');

        if ($this->create_room_type_id) {
            $query->where('room_type_id', $this->create_room_type_id);
        }

        // Exclude rooms that have confirmed reservations intersecting with the selected dates
        // Half-open interval overlap: existing.check_in_date < new.checkout_date
        // AND existing.checkout_date > new.check_in_date
        // This allows same-day turnover (checkout Jan 5, new check-in Jan 5 = no conflict)
        $query->whereDoesntHave('reservations', function ($q) use ($checkIn, $checkOut) {
            $q->whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN])
              ->where('check_in_date', '<', $checkOut)
              ->where('checkout_date', '>', $checkIn);
        });

        // Attach effective nightly rate (considering pricing overrides) to each room
        $rooms = $query->with(['roomType.prices'])->get();
        foreach ($rooms as $room) {
            $basePrice  = $room->roomType->base_price ?? 0;
            $effective  = $room->roomType->getPriceForDate($checkIn->toDateString());
            $room->effective_nightly_rate = $effective;
            $room->has_price_override     = (float)$effective !== (float)$basePrice;
        }
        $this->available_rooms = $rooms;
    }

    public function createNewReservation()
    {
        abort_unless(user_can('create_reservation'), 403);
        $this->resetForm();
        $settings = HotelSetting::where('restaurant_id', restaurant()->id)->first();
        $this->create_check_in_date = Carbon::today()->format('Y-m-d');
        $this->create_check_out_date = Carbon::tomorrow()->format('Y-m-d');

        // Load max rooms setting
        $settings = HotelSetting::where('restaurant_id', restaurant()->id)->first();
        $this->maxRoomsPerBooking = $settings->max_rooms_per_booking ?? 10;

        $this->showCreateReservation = true;
    }

    /**
     * Toggle a room in/out of the selected rooms list
     */
    public function toggleRoom($roomId)
    {
        $roomId = (int) $roomId;
        $existingIndex = null;

        foreach ($this->selected_rooms as $index => $entry) {
            if ((int) $entry['room_id'] === $roomId) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            // Remove room
            array_splice($this->selected_rooms, $existingIndex, 1);
            $this->selected_rooms = array_values($this->selected_rooms);
        } else {
            // Add room if under limit
            if (count($this->selected_rooms) >= $this->maxRoomsPerBooking) {
                $this->alert('warning', "Maximum {$this->maxRoomsPerBooking} rooms per booking.");
                return;
            }
            $this->selected_rooms[] = [
                'room_id' => $roomId,
                'adults' => (int) ($this->create_adults ?? 1),
                'children' => (int) ($this->create_children ?? 0),
            ];
        }
    }

    /**
     * Update occupancy for a specific selected room
     */
    public function updateRoomOccupancy($index, $field, $value)
    {
        if (isset($this->selected_rooms[$index])) {
            $this->selected_rooms[$index][$field] = max($field === 'adults' ? 1 : 0, (int) $value);
        }
    }

    /**
     * Remove a room from the selection
     */
    public function removeRoom($index)
    {
        if (isset($this->selected_rooms[$index])) {
            array_splice($this->selected_rooms, $index, 1);
            $this->selected_rooms = array_values($this->selected_rooms);
        }
    }

    /**
     * @param  object|null  $room  Room model (needs roomType for max_occupancy)
     * @param  array<string, mixed>|null  $roomEntry
     * @return array{max: int, total: int, is_over: bool, adults: int, children: int}
     */
    public function getRoomCapacityInfo($room, ?array $roomEntry = null): array
    {
        $max = (int) ($room->roomType->max_occupancy ?? 99);
        $adults = (int) ($roomEntry['adults'] ?? $this->create_adults ?? 1);
        $children = (int) ($roomEntry['children'] ?? $this->create_children ?? 0);
        $total = $adults + $children;

        return [
            'max' => $max,
            'total' => $total,
            'is_over' => $total > $max,
            'adults' => $adults,
            'children' => $children,
        ];
    }

    private function roomHasReservationConflict(Room $room, Carbon $checkIn, Carbon $checkOut): bool
    {
        return $room->reservations()
            ->whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN])
            ->where('check_in_date', '<', $checkOut)
            ->where('checkout_date', '>', $checkIn)
            ->exists();
    }

    public function saveReservation()
    {
        abort_unless(user_can('create_reservation'), 403);

        $this->validate();

        if (empty($this->selected_rooms)) {
            $this->addError('selected_rooms', 'Please select at least one room.');
            return;
        }

        $checkIn = Carbon::parse($this->create_check_in_date);
        $checkOut = Carbon::parse($this->create_check_out_date);
        $settings = HotelSetting::where('restaurant_id', restaurant()->id)->first();

        // Generate group booking ID only if multiple rooms
        $groupBookingId = count($this->selected_rooms) > 1
            ? Reservation::generateGroupBookingId()
            : null;

        $createdCount = 0;

        try {
            DB::transaction(function () use ($checkIn, $checkOut, $groupBookingId, $settings, &$createdCount) {
            foreach ($this->selected_rooms as $entry) {
                $room = \Modules\Hotel\Entities\Room::with('roomType')
                    ->where('id', $entry['room_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$room) {
                    throw new \RuntimeException('One or more selected rooms are no longer available.');
                }

                if ($this->roomHasReservationConflict($room, $checkIn, $checkOut)) {
                    throw new \RuntimeException(
                        "Room {$room->room_number} is no longer available for the selected dates."
                    );
                }

                // Calculate total using dynamic pricing per night
                $totalAmount = 0;
                $current = $checkIn->copy();
                while ($current->lt($checkOut)) {
                    $totalAmount += (float) $room->roomType->getPriceForDate($current);
                    $current->addDay();
                }

                // Apply check-in time from settings
                $checkInTime = $settings ? $settings->default_check_in_time : '14:00';
                $checkOutTime = $settings ? $settings->default_checkout_time : '12:00';

                Reservation::create([
                    'guest_id' => $this->create_guest_id,
                    'room_id' => $entry['room_id'],
                    'group_booking_id' => $groupBookingId,
                    'check_in_date' => $this->create_check_in_date,
                    'check_in_time' => $checkInTime,
                    'checkout_date' => $this->create_check_out_date,
                    'checkout_time' => $checkOutTime,
                    'adults' => $entry['adults'] ?? 1,
                    'children' => $entry['children'] ?? 0,
                    'special_requests' => $this->create_notes,
                    'booking_source' => $this->create_booking_source ?: 'walk-in',
                    'status' => Reservation::STATUS_CONFIRMED,
                    'total_amount' => $totalAmount,
                    'balance_due' => $totalAmount,
                    'created_by_user_id' => auth()->id(),
                ]);

                // Update room status to 'reserved' when reservation is created
                $room->update(['status' => 'reserved']);
                $createdCount++;
            }
            });
        } catch (\RuntimeException $e) {
            $this->alert('error', $e->getMessage(), ['toast' => true, 'position' => 'top-end']);

            return;
        }

        if ($createdCount === 0) {
            $this->alert('error', 'No reservations could be created. Please refresh and try again.', [
                'toast' => true,
                'position' => 'top-end',
            ]);

            return;
        }

        $roomCount = $createdCount;
        $message = $roomCount > 1
            ? "{$roomCount} room reservations created (Group: {$groupBookingId})"
            : 'Reservation created successfully';

        $this->alert('success', $message);

        $this->showCreateReservation = false;
        $this->resetForm();
        $this->dispatch('$refresh');
    }

    private function resetForm()
    {
        $this->create_guest_id = '';
        $this->create_room_id = '';
        $this->create_check_in_date = '';
        $this->create_check_out_date = '';
        $this->create_room_type_id = '';
        $this->create_adults = 1;
        $this->create_children = 0;
        $this->create_notes = '';
        $this->create_booking_source = 'walk-in';
        $this->available_rooms = [];
        $this->selected_rooms = [];
        $this->resetErrorBag();
    }
    
    public $checkout_reservation = null;
    public $checkout_total_amount = 0;
    public $checkout_amount_paid = 0;
    public $checkout_balance_due = 0;
    public $checkout_payment_method = 'cash';
    public $checkout_notes = '';
    public $checkout_date_actual = '';

    // Quick charge from reservation list
    public $showAddChargeModal = false;
    public $charge_reservation_id = null;
    public $charge_type = 'minibar';
    public $charge_description = '';
    public $charge_amount = 0;

    public function editReservation($id)
    {
        abort_unless(user_can('check_out_guest'), 403);
        $this->editingReservationId = $id;
        $this->checkout_reservation = Reservation::with(['guest', 'room.roomType', 'charges', 'payments'])->find($id);
        
        if ($this->checkout_reservation) {
            // Recalculate from actual charges/payments
            $this->checkout_reservation->calculateTotal();
            $this->checkout_reservation->refresh();

            $this->checkout_total_amount = $this->checkout_reservation->total_amount;
            $this->checkout_balance_due = $this->checkout_reservation->balance_due;
            
            // Default payment to full balance
            $this->checkout_amount_paid = max(0, $this->checkout_balance_due);
            $this->checkout_date_actual = Carbon::today()->format('Y-m-d');
            
            $this->showEditReservation = true;
        }
    }

    public function processCheckout()
    {
        abort_unless(user_can('check_out_guest'), 403);

        $this->validate([
            'checkout_amount_paid' => 'required|numeric|min:0',
            'checkout_payment_method' => 'required|string',
            'checkout_date_actual' => 'required|date',
        ]);

        if (!$this->checkout_reservation) {
            return;
        }

        DB::transaction(function () {
            $settings = HotelSetting::where('restaurant_id', restaurant()->id)->first();

            // Auto-post late checkout surcharge before settlement
            if ($settings && (float) $settings->late_checkout_charge_per_hour > 0) {
                $defaultCheckout = Carbon::parse(
                    $this->checkout_reservation->checkout_date->toDateString() . ' ' . ($settings->default_checkout_time ?? '12:00')
                );
                $actualCheckout = Carbon::parse($this->checkout_date_actual)->setTimeFrom(now());

                if ($actualCheckout->gt($defaultCheckout)) {
                    $hoursLate = max(1, (int) ceil($defaultCheckout->floatDiffInHours($actualCheckout)));
                    $lateCharge = $hoursLate * (float) $settings->late_checkout_charge_per_hour;

                    RoomCharge::create([
                        'reservation_id' => $this->checkout_reservation->id,
                        'charge_type'    => RoomCharge::TYPE_SERVICE,
                        'description'    => "Late checkout surcharge ({$hoursLate}h after " . ($settings->default_checkout_time ?? '12:00') . ')',
                        'amount'         => $lateCharge,
                        'charge_date'    => now()->toDateString(),
                    ]);

                    // Recalculate totals to include late charge before recording payment
                    $this->checkout_reservation->calculateTotal();
                    $this->checkout_reservation->refresh();
                }
            }

            // Record settlement payment if amount > 0
            if ($this->checkout_amount_paid > 0) {
                HotelPayment::create([
                    'restaurant_id' => restaurant()->id,
                    'reservation_id' => $this->checkout_reservation->id,
                    'amount' => $this->checkout_amount_paid,
                    'payment_method' => $this->checkout_payment_method,
                    'payment_type' => HotelPayment::TYPE_SETTLEMENT,
                    'notes' => $this->checkout_notes ?: 'Settlement at checkout',
                    'received_by_user_id' => auth()->id(),
                ]);
            }

            // Update reservation status
            $this->checkout_reservation->update([
                'status' => Reservation::STATUS_CHECKED_OUT,
                'actual_checkout' => Carbon::parse($this->checkout_date_actual)->setTimeFrom(now()),
            ]);

            // Recalculate totals
            $this->checkout_reservation->calculateTotal();

            OrderFolioSettlement::settleReservationOrders($this->checkout_reservation->fresh());

            // Update room status to cleaning
            if ($this->checkout_reservation->room) {
                $this->checkout_reservation->room->update(['status' => 'cleaning']);
            }
        });

        $this->alert('success', 'Guest successfully checked out. Balance updated.');

        $this->showEditReservation = false;
        $this->resetCheckoutForm();
        $this->dispatch('$refresh'); // Refresh list to show updated status
    }

    public $pendingCancelId = null;

    public function confirmCancelReservation($id)
    {
        $this->pendingCancelId = $id;
        $this->alert('warning', 'Cancel this reservation?', [
            'showConfirmButton' => true,
            'showCancelButton' => true,
            'confirmButtonText' => 'Yes, Cancel',
            'cancelButtonText' => 'No',
            'onConfirmed' => 'cancelReservationConfirmed',
        ]);
    }

    #[On('cancelReservationConfirmed')]
    public function cancelReservation($id = null)
    {
        $id = $id ?? $this->pendingCancelId;
        abort_unless(user_can('edit_reservation'), 403);
        $reservation = Reservation::where('restaurant_id', restaurant()->id)->find($id);
        
        if (!$reservation) {
            return;
        }

        // Only allow cancellation for specific statuses
        if (!in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN])) {
            $this->alert('error', 'Cannot cancel reservation in current status.');
            return;
        }

        $reservation->update(['status' => Reservation::STATUS_CANCELLED]);

        // If room was occupied (checked in), free it up
        if ($reservation->room) {
             $reservation->room->update(['status' => 'available']);
        }

        $this->alert('success', 'Reservation cancelled successfully.');
        
        $this->dispatch('$refresh');
    }

    private function resetCheckoutForm()
    {
        $this->checkout_reservation = null;
        $this->checkout_total_amount = 0;
        $this->checkout_amount_paid = 0;
        $this->checkout_balance_due = 0;
        $this->checkout_notes = '';
        $this->checkout_date_actual = '';
        $this->editingReservationId = null;
    }

    // --- Mark as No-Show ---

    public $pendingNoShowId = null;

    public function confirmMarkNoShow($id)
    {
        abort_unless(user_can('edit_reservation'), 403);
        $this->pendingNoShowId = $id;
        $this->alert('warning', 'Mark this reservation as No-Show? The room will be freed.', [
            'showConfirmButton' => true,
            'showCancelButton'  => true,
            'confirmButtonText' => 'Yes, No-Show',
            'cancelButtonText'  => 'Cancel',
            'onConfirmed'       => 'markNoShowConfirmed',
        ]);
    }

    #[On('markNoShowConfirmed')]
    public function markNoShow($id = null)
    {
        $id = $id ?? $this->pendingNoShowId;
        abort_unless(user_can('edit_reservation'), 403);

        $reservation = Reservation::where('restaurant_id', restaurant()->id)->find($id);

        if (!$reservation) {
            return;
        }

        if ($reservation->status !== Reservation::STATUS_CONFIRMED) {
            $this->alert('error', 'Only confirmed reservations can be marked as No-Show.');
            return;
        }

        $reservation->update(['status' => Reservation::STATUS_NO_SHOW]);

        // Free the room
        if ($reservation->room) {
            $reservation->room->update(['status' => 'available']);
        }

        $this->pendingNoShowId = null;
        $this->alert('success', 'Reservation marked as No-Show. Room is now available.');
        $this->dispatch('$refresh');
    }

    // --- Quick Add Charge from Reservation List ---

    public function openAddCharge($reservationId)
    {
        abort_unless(user_can('add_room_charge'), 403);
        $this->charge_reservation_id = $reservationId;
        $this->charge_type = 'minibar';
        $this->charge_description = '';
        $this->charge_amount = 0;
        $this->showAddChargeModal = true;
    }

    public function saveQuickCharge()
    {
        abort_unless(user_can('add_room_charge'), 403);

        $this->validate([
            'charge_reservation_id' => 'required|exists:hotel_reservations,id',
            'charge_type' => 'required|string',
            'charge_description' => 'required|string|max:255',
            'charge_amount' => 'required|numeric|min:0.01',
        ]);

        $reservation = Reservation::find($this->charge_reservation_id);
        if (!$reservation || !in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN])) {
            $this->alert('error', 'Cannot add charges to this reservation.');
            return;
        }

        RoomCharge::create([
            'reservation_id' => $reservation->id,
            'charge_type' => $this->charge_type,
            'description' => $this->charge_description,
            'amount' => $this->charge_amount,
            'charge_date' => now()->toDateString(),
        ]);

        $reservation->calculateTotal();

        $this->showAddChargeModal = false;
        $this->alert('success', 'Charge added successfully.');
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $reservations = Reservation::with(['guest', 'room.roomType'])
            ->when($this->search, function ($query) {
                $query->whereHas('guest', function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%');
                })->orWhere('reservation_number', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->dateFilter === 'today', function ($query) {
                $query->whereDate('check_in_date', Carbon::today());
            })
            ->when($this->dateFilter === 'upcoming', function ($query) {
                $query->where('check_in_date', '>=', Carbon::today())
                    ->where('status', Reservation::STATUS_CONFIRMED);
            })
            ->when($this->dateFilter === 'current', function ($query) {
                $query->where('status', Reservation::STATUS_CHECKED_IN);
            })
            ->latest()
            ->paginate(15);

        $guests = \Modules\Hotel\Entities\Guest::orderBy('first_name')->get();
        $roomTypes = \Modules\Hotel\Entities\RoomType::all();

        return view('hotel::livewire.reservation.reservation-list', [
            'reservations' => $reservations,
            'guests' => $guests,
            'roomTypes' => $roomTypes,
        ])->layout('layouts.app');
    }
}
