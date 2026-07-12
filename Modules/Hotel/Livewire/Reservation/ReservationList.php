<?php

namespace Modules\Hotel\Livewire\Reservation;

use Livewire\Component;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;
use Modules\Hotel\Entities\HotelPayment;
use Modules\Hotel\Entities\HotelSetting;
use Modules\Hotel\Support\HotelPaymentRecorder;
use App\Enums\ActivityEvent;
use App\Support\ActivityLogger;
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
    public $checkInProcessingRate = 0;
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
        $settings = HotelSetting::first();
        $this->checkInAdvanceAmount = $settings ? $settings->calculateDeposit($this->checkInTotalAmount) : 0;
        $this->checkInPaymentMethod = 'cash';
        $this->checkInProcessingRate = 0;
        $this->checkInNotes = '';
        $this->showCheckInModal = true;
    }

    public function processCheckIn()
    {
        abort_unless(user_can('check_in_guest'), 403);

        $this->validate([
            'checkInAdvanceAmount' => 'required|numeric|min:0',
            'checkInPaymentMethod' => 'required|string',
            'checkInProcessingRate' => 'nullable|numeric|min:0|max:100',
        ]);

        if (!$this->checkInReservation) {
            return;
        }

        DB::transaction(function () {
            $this->performCheckInOnReservation(
                $this->checkInReservation,
                (float) $this->checkInAdvanceAmount,
                $this->checkInPaymentMethod,
                (float) $this->checkInProcessingRate,
                $this->checkInNotes ?: 'Advance payment at check-in',
            );
        });

        if ($this->checkInReservation) {
            ActivityLogger::recordEvent(
                activityEvent: ActivityEvent::GuestCheckedIn,
                description: 'Guest checked in to room ' . ($this->checkInReservation->room?->room_number ?? 'N/A'),
                subject: $this->checkInReservation->fresh(),
                properties: [
                    'reservation_id' => $this->checkInReservation->id,
                    'reservation_number' => $this->checkInReservation->reservation_number ?? null,
                ],
                restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
            );
        }

        $this->showCheckInModal = false;
        $this->checkInReservation = null;

        $this->alert('success', 'Guest checked in successfully.');
        $this->dispatch('$refresh');
    }

    /**
     * Check in a confirmed reservation: room charges, optional advance payment, status updates.
     */
    protected function performCheckInOnReservation(
        Reservation $reservation,
        float $advanceAmount = 0,
        string $paymentMethod = 'cash',
        float $processingRate = 0,
        ?string $notes = null,
    ): void {
        $reservation->load(['room.roomType']);
        $settings = HotelSetting::first();

        $this->generateRoomNightCharges($reservation);

        if ($settings && (float) $settings->early_checkin_charge_per_hour > 0) {
            $defaultCheckIn = Carbon::parse(
                $reservation->check_in_date->toDateString() . ' ' . ($settings->default_check_in_time ?? '14:00')
            );
            $actualCheckIn = now();

            if ($actualCheckIn->lt($defaultCheckIn)) {
                $hoursEarly = max(1, (int) ceil($actualCheckIn->diffInHours($defaultCheckIn, true)));
                $earlyCharge = $hoursEarly * (float) $settings->early_checkin_charge_per_hour;

                RoomCharge::create([
                    'branch_id'      => $reservation->branch_id,
                    'reservation_id' => $reservation->id,
                    'charge_type'    => RoomCharge::TYPE_SERVICE,
                    'description'    => "Early check-in surcharge ({$hoursEarly}h before " . ($settings->default_check_in_time ?? '14:00') . ')',
                    'amount'         => $earlyCharge,
                    'charge_date'    => now()->toDateString(),
                ]);
            }
        }

        if ($advanceAmount > 0) {
            HotelPaymentRecorder::record(
                $reservation,
                $advanceAmount,
                $paymentMethod,
                HotelPayment::TYPE_ADVANCE,
                null,
                $notes ?: 'Advance payment at check-in',
                auth()->id(),
                $processingRate,
                (bool) ($settings->enable_payment_surcharge ?? false),
            );
        }

        $reservation->update([
            'status' => Reservation::STATUS_CHECKED_IN,
            'actual_check_in' => now(),
            'created_by_user_id' => $reservation->created_by_user_id ?? auth()->id(),
        ]);

        $reservation->calculateTotal();

        if ($reservation->room) {
            $reservation->room->update(['status' => 'occupied']);
        }
    }

    /**
     * Generate room night charges for the entire stay using dynamic pricing
     */
    protected function generateRoomNightCharges(Reservation $reservation)
    {
        $checkIn  = $reservation->check_in_date->copy();
        $checkOut = $reservation->checkout_date->copy();
        $settings = HotelSetting::first();

        $roomChargesTotal = 0;
        $currentDate = $checkIn->copy();
        while ($currentDate->lt($checkOut)) {
            $nightlyRate = $reservation->getNightlyRateForDate($currentDate);

            RoomCharge::create([
                'branch_id'      => $reservation->branch_id,
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
        $taxRate = $reservation->getEffectiveTaxRate();
        if ($taxRate > 0) {
            $taxAmount = round($roomChargesTotal * ($taxRate / 100), 2);
            if ($taxAmount > 0) {
                RoomCharge::create([
                    'branch_id'      => $reservation->branch_id,
                    'reservation_id' => $reservation->id,
                    'charge_type'    => RoomCharge::TYPE_TAX,
                    'description'    => 'Tax (' . number_format($taxRate, 2, '.', '') . '%)',
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
                    'branch_id'      => $reservation->branch_id,
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
        return $reservation->calculateRoomChargesTotal();
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
     * Format: [ ['room_id' => int, 'adults' => int, 'children' => int, 'nightly_rate_override' => ?float], ... ]
     */
    public $selected_rooms = [];
    public $maxRoomsPerBooking = 10;

    /**
     * Staff-edited nightly rates keyed by room ID (applies flat rate for all nights).
     */
    public $room_rate_overrides = [];

    public $editing_room_rate_id = null;
    public $edit_room_rate_value = 0;

    protected function rules()
    {
        return [
            'create_guest_id' => 'required|exists:hotel_guests,id',
            'create_check_in_date' => 'required|date',
            'create_check_out_date' => 'required|date|after:create_check_in_date',
            'selected_rooms' => 'required|array|min:1',
            'selected_rooms.*.room_id' => 'required|exists:hotel_rooms,id',
            'selected_rooms.*.adults' => 'required|integer|min:1',
            'selected_rooms.*.children' => 'integer|min:0',
            'selected_rooms.*.nightly_rate_override' => 'nullable|numeric|min:0',
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
            'branch_id'     => branch()->id,
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

        $this->available_rooms = $query->with(['roomType.prices'])->get();
    }

    public function createNewReservation()
    {
        abort_unless(user_can('create_reservation'), 403);
        $this->resetForm();
        $this->create_check_in_date = Carbon::today()->format('Y-m-d');
        $this->create_check_out_date = Carbon::tomorrow()->format('Y-m-d');
        $settings = HotelSetting::first();
        $this->maxRoomsPerBooking = $settings->max_rooms_per_booking ?? 10;
        $this->findAvailableRooms();
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
                'nightly_rate_override' => $this->room_rate_overrides[$roomId] ?? null,
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

    public function getDefaultRoomNightlyRate(Room $room): float
    {
        if (!$this->create_check_in_date) {
            return (float) ($room->roomType->base_price ?? 0);
        }

        return (float) $room->roomType->getPriceForDate($this->create_check_in_date);
    }

    public function getEffectiveRoomNightlyRate(Room $room): float
    {
        $roomId = (int) $room->id;

        if (isset($this->room_rate_overrides[$roomId])) {
            return (float) $this->room_rate_overrides[$roomId];
        }

        return $this->getDefaultRoomNightlyRate($room);
    }

    public function hasCustomRoomRate(int $roomId): bool
    {
        return array_key_exists($roomId, $this->room_rate_overrides);
    }

    public function startEditRoomRate(int $roomId): void
    {
        abort_unless(user_can('create_reservation'), 403);

        $room = collect($this->available_rooms)->firstWhere('id', $roomId);

        if (!$room) {
            return;
        }

        $this->editing_room_rate_id = $roomId;
        $this->edit_room_rate_value = $this->getEffectiveRoomNightlyRate($room);
    }

    public function saveEditRoomRate(): void
    {
        abort_unless(user_can('create_reservation'), 403);

        if (!$this->editing_room_rate_id) {
            return;
        }

        $this->validate([
            'edit_room_rate_value' => 'required|numeric|min:0.01',
        ]);

        $roomId = (int) $this->editing_room_rate_id;
        $rate = round((float) $this->edit_room_rate_value, 2);

        $this->room_rate_overrides[$roomId] = $rate;

        foreach ($this->selected_rooms as $index => $entry) {
            if ((int) $entry['room_id'] === $roomId) {
                $this->selected_rooms[$index]['nightly_rate_override'] = $rate;
            }
        }

        $this->cancelEditRoomRate();
    }

    public function cancelEditRoomRate(): void
    {
        $this->editing_room_rate_id = null;
        $this->edit_room_rate_value = 0;
        $this->resetErrorBag('edit_room_rate_value');
    }

    public function clearRoomRateOverride(int $roomId): void
    {
        unset($this->room_rate_overrides[$roomId]);

        foreach ($this->selected_rooms as $index => $entry) {
            if ((int) $entry['room_id'] === $roomId) {
                unset($this->selected_rooms[$index]['nightly_rate_override']);
            }
        }

        if ((int) $this->editing_room_rate_id === $roomId) {
            $this->cancelEditRoomRate();
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
        $this->persistReservations(checkInAfterCreate: false);
    }

    public function saveReservationAndCheckIn()
    {
        abort_unless(user_can('create_reservation'), 403);
        abort_unless(user_can('check_in_guest'), 403);

        $this->persistReservations(checkInAfterCreate: true);
    }

    private function persistReservations(bool $checkInAfterCreate = false): void
    {
        abort_unless(user_can('create_reservation'), 403);

        $this->validate();

        if (empty($this->selected_rooms)) {
            $this->addError('selected_rooms', 'Please select at least one room.');
            return;
        }

        $checkIn = Carbon::parse($this->create_check_in_date);
        $checkOut = Carbon::parse($this->create_check_out_date);
        $settings = HotelSetting::first();

        $groupBookingId = count($this->selected_rooms) > 1
            ? Reservation::generateGroupBookingId()
            : null;

        $createdCount = 0;
        $checkedInReservations = [];

        try {
            DB::transaction(function () use ($checkIn, $checkOut, $groupBookingId, $settings, $checkInAfterCreate, &$createdCount, &$checkedInReservations) {
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

                $nightlyOverride = isset($entry['nightly_rate_override'])
                    ? round((float) $entry['nightly_rate_override'], 2)
                    : (isset($this->room_rate_overrides[$entry['room_id']])
                        ? round((float) $this->room_rate_overrides[$entry['room_id']], 2)
                        : null);

                $totalAmount = 0;
                $current = $checkIn->copy();
                while ($current->lt($checkOut)) {
                    if ($nightlyOverride !== null) {
                        $totalAmount += $nightlyOverride;
                    } else {
                        $totalAmount += (float) $room->roomType->getPriceForDate($current);
                    }
                    $current->addDay();
                }

                $checkInTime = $settings ? $settings->default_check_in_time : '14:00';
                $checkOutTime = $settings ? $settings->default_checkout_time : '12:00';

                $reservation = Reservation::create([
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
                    'nightly_rate_override' => $nightlyOverride,
                    'created_by_user_id' => auth()->id(),
                ]);

                ActivityLogger::recordEvent(
                    activityEvent: ActivityEvent::ReservationCreated,
                    description: "Reservation created for room {$room->room_number}",
                    subject: $reservation,
                    properties: [
                        'reservation_id' => $reservation->id,
                        'reservation_number' => $reservation->reservation_number ?? null,
                        'room_id' => $entry['room_id'],
                        'group_booking_id' => $groupBookingId,
                    ],
                    restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
                );

                if ($checkInAfterCreate) {
                    $advanceAmount = $settings ? (float) $settings->calculateDeposit($totalAmount) : 0;
                    $this->performCheckInOnReservation(
                        $reservation,
                        $advanceAmount,
                        'cash',
                        0,
                        'Advance payment at check-in',
                    );
                    $checkedInReservations[] = $reservation->fresh(['room']);
                } else {
                    $room->update(['status' => 'reserved']);
                }

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

        if ($checkInAfterCreate) {
            foreach ($checkedInReservations as $reservation) {
                ActivityLogger::recordEvent(
                    activityEvent: ActivityEvent::GuestCheckedIn,
                    description: 'Guest checked in to room ' . ($reservation->room?->room_number ?? 'N/A'),
                    subject: $reservation,
                    properties: [
                        'reservation_id' => $reservation->id,
                        'reservation_number' => $reservation->reservation_number ?? null,
                    ],
                    restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
                );
            }
        }

        $roomCount = $createdCount;
        if ($checkInAfterCreate) {
            $message = $roomCount > 1
                ? "{$roomCount} reservations created and guests checked in (Group: {$groupBookingId})"
                : 'Reservation created and guest checked in successfully';
        } else {
            $message = $roomCount > 1
                ? "{$roomCount} room reservations created (Group: {$groupBookingId})"
                : 'Reservation created successfully';
        }

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
        $this->room_rate_overrides = [];
        $this->cancelEditRoomRate();
        $this->resetErrorBag();
    }
    
    public $checkout_reservation = null;
    public $checkout_total_amount = 0;
    public $checkout_amount_paid = 0;
    public $checkout_balance_due = 0;
    public $checkout_payment_method = 'cash';
    public $checkout_processing_rate = 0;
    public $checkout_notes = '';
    public $checkout_date_actual = '';

    // Extended stay charge fields
    public $checkout_extended_days   = 0;  // float, supports 0.5 increments
    public $checkout_extended_rate   = 0;  // per-day rate (auto-filled, editable)
    public $checkout_extended_amount = 0;  // days × rate (editable override)
    public $checkout_extended_tax    = 0;  // tax on the extended amount (same rate as reservation)

    // Quick charge from reservation list
    public $showAddChargeModal = false;
    public $charge_reservation_id = null;
    public $charge_type = 'minibar';
    public $charge_type_custom = '';
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
            $this->checkout_payment_method = 'cash';
            $this->checkout_processing_rate = 0;

            // Extended stay — auto-detect extra days vs original checkout date
            $this->checkout_extended_days   = 0;
            $this->checkout_extended_rate   = 0;
            $this->checkout_extended_amount = 0;
            $this->checkout_extended_tax    = 0;
            $this->recalculateExtendedStay();

            $this->showEditReservation = true;
        }
    }

    // ── Extended-stay lifecycle hooks ──────────────────────────────────────

    public function updatedCheckoutDateActual(): void
    {
        $this->recalculateExtendedStay();
    }

    public function updatedCheckoutExtendedDays(): void
    {
        $this->checkout_extended_days   = max(0, (float) $this->checkout_extended_days);
        $this->checkout_extended_amount = round($this->checkout_extended_days * (float) $this->checkout_extended_rate, 2);
        $this->checkout_extended_tax    = $this->computeExtendedTax($this->checkout_extended_amount);
        $this->syncExtendedSettlementAmount();
    }

    public function updatedCheckoutExtendedRate(): void
    {
        $this->checkout_extended_rate   = max(0, (float) $this->checkout_extended_rate);
        $this->checkout_extended_amount = round($this->checkout_extended_days * (float) $this->checkout_extended_rate, 2);
        $this->checkout_extended_tax    = $this->computeExtendedTax($this->checkout_extended_amount);
        $this->syncExtendedSettlementAmount();
    }

    public function updatedCheckoutExtendedAmount(): void
    {
        $this->checkout_extended_amount = max(0, (float) $this->checkout_extended_amount);
        $this->checkout_extended_tax    = $this->computeExtendedTax($this->checkout_extended_amount);
        $this->syncExtendedSettlementAmount();
    }

    public function incrementExtendedDays(): void
    {
        $this->checkout_extended_days   = round((float) $this->checkout_extended_days + 0.5, 1);
        $this->checkout_extended_amount = round($this->checkout_extended_days * (float) $this->checkout_extended_rate, 2);
        $this->checkout_extended_tax    = $this->computeExtendedTax($this->checkout_extended_amount);
        $this->syncExtendedSettlementAmount();
    }

    public function decrementExtendedDays(): void
    {
        $this->checkout_extended_days   = max(0, round((float) $this->checkout_extended_days - 0.5, 1));
        $this->checkout_extended_amount = round($this->checkout_extended_days * (float) $this->checkout_extended_rate, 2);
        $this->checkout_extended_tax    = $this->computeExtendedTax($this->checkout_extended_amount);
        $this->syncExtendedSettlementAmount();
    }

    /** Auto-fill nightly rate and day count when actual checkout date changes. */
    private function recalculateExtendedStay(): void
    {
        if (!$this->checkout_reservation || !$this->checkout_date_actual) {
            $this->checkout_extended_days   = 0;
            $this->checkout_extended_amount = 0;
            return;
        }

        $originalCheckout = $this->checkout_reservation->checkout_date->copy()->startOfDay();
        $actualDate       = Carbon::parse($this->checkout_date_actual)->startOfDay();

        if ($actualDate->lte($originalCheckout)) {
            $this->checkout_extended_days   = 0;
            $this->checkout_extended_amount = 0;
            return;
        }

        $this->checkout_extended_days = $originalCheckout->diffInDays($actualDate);

        // Auto-fill rate from room type (editable by staff)
        if ($this->checkout_extended_rate == 0 &&
            $this->checkout_reservation->room?->roomType) {
            $this->checkout_extended_rate = (float) $this->checkout_reservation->room->roomType
                ->getPriceForDate($originalCheckout);
        }

        $this->checkout_extended_amount = round(
            $this->checkout_extended_days * (float) $this->checkout_extended_rate, 2
        );

        $this->checkout_extended_tax = $this->computeExtendedTax($this->checkout_extended_amount);

        $this->syncExtendedSettlementAmount();
    }

    /** Tax on the extended stay amount using the reservation's effective rate. */
    private function computeExtendedTax(float $amount): float
    {
        if (!$this->checkout_reservation || $amount <= 0) {
            return 0;
        }
        $taxRate = $this->checkout_reservation->getEffectiveTaxRate();
        return round($amount * ($taxRate / 100), 2);
    }

    /** Keep the settlement amount in sync with the updated balance due (including tax). */
    private function syncExtendedSettlementAmount(): void
    {
        $newBalance = (float) $this->checkout_balance_due
            + (float) $this->checkout_extended_amount
            + (float) $this->checkout_extended_tax;
        $this->checkout_amount_paid = max(0, round($newBalance, 2));
    }

    // ── Checkout ───────────────────────────────────────────────────────────

    public function processCheckout()
    {
        abort_unless(user_can('check_out_guest'), 403);

        $this->validate([
            'checkout_amount_paid'     => 'required|numeric|min:0',
            'checkout_payment_method'  => 'required|string',
            'checkout_processing_rate' => 'nullable|numeric|min:0|max:100',
            'checkout_date_actual'     => 'required|date',
            'checkout_extended_days'   => 'nullable|numeric|min:0',
            'checkout_extended_amount' => 'nullable|numeric|min:0',
        ]);

        if (!$this->checkout_reservation) {
            return;
        }

        DB::transaction(function () {
            $settings = HotelSetting::first();
            $surchargeEnabled = (bool) ($settings?->enable_payment_surcharge ?? false);

            // ── Extended stay room charge ───────────────────────────────
            if ((float) $this->checkout_extended_days > 0 && (float) $this->checkout_extended_amount > 0) {
                $extDays = (float) $this->checkout_extended_days;

                // Label: "1 night", "1.5 nights", "2 nights"
                $daysLabel = ($extDays == (int) $extDays)
                    ? (int) $extDays . ($extDays == 1 ? ' night' : ' nights')
                    : number_format($extDays, 1) . ' nights';

                RoomCharge::create([
                    'branch_id'      => $this->checkout_reservation->branch_id,
                    'reservation_id' => $this->checkout_reservation->id,
                    'charge_type'    => RoomCharge::TYPE_ROOM_NIGHT,
                    'description'    => "Extended stay ({$daysLabel})",
                    'amount'         => round((float) $this->checkout_extended_amount, 2),
                    'charge_date'    => $this->checkout_reservation->checkout_date->toDateString(),
                ]);

                // Recalculate tax and service charges to include the new room-night charge
                $this->checkout_reservation->recalculateTaxCharge();
                $this->checkout_reservation->recalculateLinkedServiceCharge();
                $this->checkout_reservation->calculateTotal();
                $this->checkout_reservation->refresh();
            }

            // Auto-post late checkout surcharge before settlement
            if ($settings && (float) $settings->late_checkout_charge_per_hour > 0) {
                $defaultCheckout = Carbon::parse(
                    $this->checkout_reservation->checkout_date->toDateString() . ' ' . ($settings->default_checkout_time ?? '12:00')
                );
                $actualCheckout = Carbon::parse($this->checkout_date_actual)->setTimeFrom(now());

                if ($actualCheckout->gt($defaultCheckout)) {
                    $hoursLate = max(1, (int) ceil($defaultCheckout->diffInHours($actualCheckout, true)));
                    $lateCharge = $hoursLate * (float) $settings->late_checkout_charge_per_hour;

                    RoomCharge::create([
                        'branch_id'      => $this->checkout_reservation->branch_id,
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
                HotelPaymentRecorder::record(
                    $this->checkout_reservation,
                    (float) $this->checkout_amount_paid,
                    $this->checkout_payment_method,
                    HotelPayment::TYPE_SETTLEMENT,
                    null,
                    $this->checkout_notes ?: 'Settlement at checkout',
                    auth()->id(),
                    (float) $this->checkout_processing_rate,
                    $surchargeEnabled,
                );
            }

            // Update reservation status
            $this->checkout_reservation->update([
                'status' => Reservation::STATUS_CHECKED_OUT,
                'actual_checkout' => Carbon::parse($this->checkout_date_actual)->setTimeFrom(now()),
            ]);

            // Recalculate totals
            $this->checkout_reservation->calculateTotal();

            // Free the room for the next booking; staff can set cleaning/maintenance manually on Rooms page
            if ($this->checkout_reservation->room) {
                $this->checkout_reservation->room->update(['status' => Room::STATUS_AVAILABLE]);
            }
        });

        if ($this->checkout_reservation) {
            ActivityLogger::recordEvent(
                activityEvent: ActivityEvent::GuestCheckedOut,
                description: 'Guest checked out from room ' . ($this->checkout_reservation->room?->room_number ?? 'N/A'),
                subject: $this->checkout_reservation->fresh(),
                properties: [
                    'reservation_id' => $this->checkout_reservation->id,
                    'reservation_number' => $this->checkout_reservation->reservation_number ?? null,
                    'amount_paid' => $this->checkout_amount_paid,
                ],
                restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
            );
        }

        $this->alert('success', 'Guest successfully checked out. Balance updated.');

        $this->showEditReservation = false;
        $this->resetCheckoutForm();
        $this->dispatch('$refresh'); // Refresh list to show updated status
    }

    public $pendingCancelId = null;
    public $pendingDeleteId = null;

    public function confirmDeleteReservation($id)
    {
        abort_unless(user_can('delete_reservation'), 403);
        $this->pendingDeleteId = $id;
        $this->alert('warning', 'Delete this reservation permanently? This cannot be undone.', [
            'showConfirmButton' => true,
            'showCancelButton' => true,
            'confirmButtonText' => 'Yes, Delete',
            'cancelButtonText' => 'No',
            'onConfirmed' => 'deleteReservationConfirmed',
        ]);
    }

    #[On('deleteReservationConfirmed')]
    public function deleteReservation($id = null)
    {
        $id = $id ?? $this->pendingDeleteId;
        abort_unless(user_can('delete_reservation'), 403);

        $reservation = Reservation::find($id);

        if (!$reservation) {
            return;
        }

        if ($reservation->status !== Reservation::STATUS_CANCELLED) {
            $this->alert('error', 'Only cancelled reservations can be deleted.');
            return;
        }

        $reservationNumber = $reservation->reservation_number;

        DB::transaction(function () use ($reservation) {
            $reservation->delete();
        });

        ActivityLogger::recordEvent(
            activityEvent: ActivityEvent::ReservationCancelled,
            description: 'Reservation deleted' . ($reservationNumber ? " (#{$reservationNumber})" : ''),
            subject: null,
            properties: [
                'reservation_number' => $reservationNumber,
            ],
            restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
        );

        $this->pendingDeleteId = null;
        $this->alert('success', 'Reservation deleted successfully.');
        $this->dispatch('$refresh');
    }

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
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return;
        }

        // Only allow cancellation for confirmed reservations (before check-in)
        if ($reservation->status !== Reservation::STATUS_CONFIRMED) {
            $this->alert('error', 'Cannot cancel reservation in current status.');
            return;
        }

        $reservation->update(['status' => Reservation::STATUS_CANCELLED]);

        ActivityLogger::recordEvent(
            activityEvent: ActivityEvent::ReservationCancelled,
            description: 'Reservation cancelled' . ($reservation->reservation_number ? " (#{$reservation->reservation_number})" : ''),
            subject: $reservation,
            properties: [
                'reservation_id' => $reservation->id,
                'reservation_number' => $reservation->reservation_number ?? null,
            ],
            restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
        );

        // Free the room if it was reserved
        if ($reservation->room && $reservation->room->status === 'reserved') {
            $reservation->room->update(['status' => 'available']);
        }

        $this->alert('success', 'Reservation cancelled successfully.');
        
        $this->dispatch('$refresh');
    }

    private function resetCheckoutForm()
    {
        $this->checkout_reservation      = null;
        $this->checkout_total_amount     = 0;
        $this->checkout_amount_paid      = 0;
        $this->checkout_balance_due      = 0;
        $this->checkout_notes            = '';
        $this->checkout_payment_method   = 'cash';
        $this->checkout_processing_rate  = 0;
        $this->checkout_date_actual      = '';
        $this->checkout_extended_days    = 0;
        $this->checkout_extended_rate    = 0;
        $this->checkout_extended_amount  = 0;
        $this->checkout_extended_tax     = 0;
        $this->editingReservationId      = null;
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

        $reservation = Reservation::find($id);

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
        $this->charge_type_custom = '';
        $this->charge_description = '';
        $this->charge_amount = 0;
        $this->showAddChargeModal = true;
    }

    public function saveQuickCharge()
    {
        abort_unless(user_can('add_room_charge'), 403);

        $rules = [
            'charge_reservation_id' => 'required|exists:hotel_reservations,id',
            'charge_type' => 'required|in:room_night,minibar,laundry,service,tax,other',
            'charge_description' => 'required|string|max:255',
            'charge_amount' => 'required|numeric|min:0.01',
        ];

        if ($this->charge_type === 'other') {
            $rules['charge_type_custom'] = ['required', 'string', 'max:50', 'regex:/^[^:]+$/'];
        }

        $this->validate($rules);

        $description = $this->charge_type === 'other'
            ? RoomCharge::encodeCustomTypeDescription($this->charge_type_custom, $this->charge_description)
            : $this->charge_description;

        $reservation = Reservation::find($this->charge_reservation_id);
        if (!$reservation || !in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN])) {
            $this->alert('error', 'Cannot add charges to this reservation.');
            return;
        }

        RoomCharge::create([
            'branch_id'      => $reservation->branch_id,
            'reservation_id' => $reservation->id,
            'charge_type' => $this->charge_type,
            'description' => $description,
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
        $hotelSettings = HotelSetting::first();

        return view('hotel::livewire.reservation.reservation-list', [
            'reservations' => $reservations,
            'guests' => $guests,
            'roomTypes' => $roomTypes,
            'paymentSurchargeEnabled' => (bool) ($hotelSettings->enable_payment_surcharge ?? false),
        ])->layout('layouts.app');
    }

    public function checkInAppliesSurcharge(): bool
    {
        return $this->paymentSurchargeEnabledForView()
            && in_array($this->checkInPaymentMethod, ['card', 'bank_transfer'], true);
    }

    public function checkInCalculatedSurcharge(): float
    {
        if (!$this->checkInAppliesSurcharge()) {
            return 0;
        }

        return HotelPaymentRecorder::calculateSurcharge(
            (float) $this->checkInAdvanceAmount,
            (float) $this->checkInProcessingRate
        );
    }

    public function checkInCalculatedTotal(): float
    {
        return round((float) $this->checkInAdvanceAmount + $this->checkInCalculatedSurcharge(), 2);
    }

    public function checkoutAppliesSurcharge(): bool
    {
        return $this->paymentSurchargeEnabledForView()
            && in_array($this->checkout_payment_method, ['card', 'bank_transfer'], true);
    }

    public function checkoutCalculatedSurcharge(): float
    {
        if (!$this->checkoutAppliesSurcharge()) {
            return 0;
        }

        return HotelPaymentRecorder::calculateSurcharge(
            (float) $this->checkout_amount_paid,
            (float) $this->checkout_processing_rate
        );
    }

    public function checkoutCalculatedTotal(): float
    {
        return round((float) $this->checkout_amount_paid + $this->checkoutCalculatedSurcharge(), 2);
    }

    protected function paymentSurchargeEnabledForView(): bool
    {
        $settings = HotelSetting::first();

        return (bool) ($settings->enable_payment_surcharge ?? false);
    }
}
