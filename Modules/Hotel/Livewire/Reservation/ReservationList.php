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
    public $bookingType = 'group'; // group | separate

    // --- Dedicated Update Reservation Modal ---
    public $showUpdateModal = false;
    public $updateReservation = null; // the loaded Reservation model
    public $update_check_in_date  = '';
    public $update_check_out_date = '';
    public $update_check_in_time  = '';
    public $update_check_out_time = '';
    public $update_notes = '';
    public $update_payment_id = null;
    public $update_payment_amount = 0;
    public $update_payment_method = 'cash';

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
            $status = $this->checkInReservation?->status ?? 'unknown';
            $this->alert('error', match ($status) {
                Reservation::STATUS_CHECKED_IN => 'This guest is already checked in.',
                Reservation::STATUS_CHECKED_OUT => 'This reservation has already been checked out.',
                Reservation::STATUS_CANCELLED => 'Cancelled reservations cannot be checked in.',
                Reservation::STATUS_NO_SHOW => 'No-show reservations cannot be checked in.',
                default => 'Only confirmed reservations can be checked in.',
            }, ['toast' => true, 'position' => 'top-end']);

            return;
        }

        $settings = HotelSetting::first();
        if ($this->checkInReservation->group_booking_id) {
            $groupReservations = Reservation::with(['guest', 'room.roomType'])
                ->where('group_booking_id', $this->checkInReservation->group_booking_id)
                ->where('status', Reservation::STATUS_CONFIRMED)
                ->get();
            
            $this->checkInTotalAmount = 0;
            $depositSum = 0;
            foreach ($groupReservations as $res) {
                $total = $this->calculateStayTotal($res);
                $this->checkInTotalAmount += $total;
                $depositSum += $settings ? $settings->calculateDeposit($total) : 0;
            }
            $this->checkInAdvanceAmount = $depositSum;
        } else {
            // Calculate total using dynamic pricing
            $this->checkInTotalAmount = $this->calculateStayTotal($this->checkInReservation);
            $this->checkInAdvanceAmount = $settings ? $settings->calculateDeposit($this->checkInTotalAmount) : 0;
        }

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
            if ($this->checkInReservation->group_booking_id) {
                $groupReservations = Reservation::where('group_booking_id', $this->checkInReservation->group_booking_id)
                    ->where('status', Reservation::STATUS_CONFIRMED)
                    ->get();
                
                $remainingAdvance = (float) $this->checkInAdvanceAmount;
                $settings = HotelSetting::first();

                foreach ($groupReservations as $res) {
                    $resTotal = $this->calculateStayTotal($res);
                    $suggestedDeposit = $settings ? $settings->calculateDeposit($resTotal) : 0;
                    $allocatedAdvance = min($remainingAdvance, $suggestedDeposit);

                    $this->performCheckInOnReservation(
                        $res,
                        $allocatedAdvance,
                        $this->checkInPaymentMethod,
                        (float) $this->checkInProcessingRate,
                        $this->checkInNotes ?: 'Advance payment at check-in',
                    );
                    $remainingAdvance -= $allocatedAdvance;
                }
                
                if ($remainingAdvance > 0) {
                    $primary = $groupReservations->firstWhere('id', $this->checkInReservation->id) ?: $groupReservations->first();
                    if ($primary) {
                        HotelPaymentRecorder::record(
                            $primary,
                            $remainingAdvance,
                            $this->checkInPaymentMethod,
                            HotelPayment::TYPE_ADVANCE,
                            null,
                            $this->checkInNotes ?: 'Advance payment at check-in (Group excess)',
                            auth()->id(),
                            (float) $this->checkInProcessingRate,
                            (bool) ($settings->enable_payment_surcharge ?? false),
                        );
                        $primary->calculateTotal();
                    }
                }
            } else {
                $this->performCheckInOnReservation(
                    $this->checkInReservation,
                    (float) $this->checkInAdvanceAmount,
                    $this->checkInPaymentMethod,
                    (float) $this->checkInProcessingRate,
                    $this->checkInNotes ?: 'Advance payment at check-in',
                );
            }
        });

        // Record Activity Logs
        if ($this->checkInReservation) {
            if ($this->checkInReservation->group_booking_id) {
                $groupReservations = Reservation::where('group_booking_id', $this->checkInReservation->group_booking_id)->get();
                foreach ($groupReservations as $res) {
                    ActivityLogger::recordEvent(
                        activityEvent: ActivityEvent::GuestCheckedIn,
                        description: 'Guest checked in to room ' . ($res->room?->room_number ?? 'N/A') . ' (Group Booking)',
                        subject: $res->fresh(),
                        properties: [
                            'reservation_id' => $res->id,
                            'reservation_number' => $res->reservation_number ?? null,
                            'group_booking_id' => $this->checkInReservation->group_booking_id,
                        ],
                        restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
                    );
                }
            } else {
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

        $reservation->postRoomNightCharges($settings);

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
     * Calculate stay total using unified folio estimate (room + tax + service).
     */
    protected function calculateStayTotal(Reservation $reservation): float
    {
        return $reservation->estimateStayFolioTotal($this->hotelSettings())['total'];
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
    public $create_check_in_time = '';
    public $create_check_out_time = '';
    public $create_room_type_id = '';
    public $create_adults = 1;
    public $create_children = 0;
    public $create_notes = '';
    public $create_booking_source = 'walk-in';
    public $create_payment_amount = '';
    public $create_payment_method = 'cash';
    public $create_payment_processing_rate = 0;
    public $create_payment_notes = '';
    
    public $available_rooms = [];

    /** @var HotelSetting|null Request-scoped cache (not persisted by Livewire) */
    private $hotelSettingsCache = false;

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
            'create_check_out_date' => 'required|date|after_or_equal:create_check_in_date',
            'create_check_in_time' => 'required|date_format:H:i',
            'create_check_out_time' => 'required|date_format:H:i',
            'selected_rooms' => 'required|array|min:1',
            'selected_rooms.*.room_id' => 'required|exists:hotel_rooms,id',
            'selected_rooms.*.adults' => 'required|integer|min:1',
            'selected_rooms.*.children' => 'integer|min:0',
            'selected_rooms.*.nightly_rate_override' => 'nullable|numeric|min:0',
            'create_notes' => 'nullable|string',
            'create_booking_source' => 'nullable|string|max:100',
            'create_payment_amount' => 'nullable|numeric|min:0',
            'create_payment_method' => 'nullable|in:cash,card,bank_transfer,upi,other',
            'create_payment_processing_rate' => 'nullable|numeric|min:0|max:100',
            'create_payment_notes' => 'nullable|string|max:500',
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

    public function updatedCreateCheckInTime()
    {
        $this->findAvailableRooms();
    }

    public function updatedCreateCheckOutTime()
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

        $this->create_guest_id = (string) $guest->id;
        $this->showCreateGuest = false;
        $this->showCreateReservation = true;

        $this->new_guest_first_name = '';
        $this->new_guest_last_name = '';
        $this->new_guest_email = '';
        $this->new_guest_phone = '';
        $this->resetValidation([
            'new_guest_first_name',
            'new_guest_last_name',
            'new_guest_email',
            'new_guest_phone',
        ]);

        $this->alert('success', $guest->full_name . ' added and selected', [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    public function toggleCreateGuest(): void
    {
        abort_unless(user_can('create_guest'), 403);

        $this->showCreateGuest = ! $this->showCreateGuest;
        $this->showCreateReservation = true;

        if (! $this->showCreateGuest) {
            $this->new_guest_first_name = '';
            $this->new_guest_last_name = '';
            $this->new_guest_email = '';
            $this->new_guest_phone = '';
            $this->resetValidation([
                'new_guest_first_name',
                'new_guest_last_name',
                'new_guest_email',
                'new_guest_phone',
            ]);
        }
    }

    public function findAvailableRooms()
    {
        if (!$this->create_check_in_date || !$this->create_check_out_date) {
            $this->available_rooms = [];
            return;
        }

        $checkIn = $this->resolveCreateStayDateTime(true);
        $checkOut = $this->resolveCreateStayDateTime(false);

        if ($checkOut->lte($checkIn)) {
            $this->available_rooms = [];
            return;
        }

        $settings = $this->hotelSettings();
        $rangeStart = $checkIn->toDateString();
        $rangeEnd = $checkOut->toDateString();

        // Only hard-exclude maintenance/blocked. Reserved/occupied rooms can still be
        // booked for a non-overlapping time window (e.g. after same-day checkout).
        $query = Room::query()
            ->select(['id', 'room_number', 'floor', 'section', 'status', 'room_type_id', 'branch_id'])
            ->whereNotIn('status', [Room::STATUS_MAINTENANCE, Room::STATUS_BLOCKED]);

        if ($this->create_room_type_id) {
            $query->where('room_type_id', $this->create_room_type_id);
        }

        $query->whereDoesntHave('reservations', function ($q) use ($checkIn, $checkOut) {
            $q->overlappingStay($checkIn, $checkOut);
        });

        $rooms = $query
            ->with([
                'roomType:id,name,base_price,max_occupancy,branch_id',
                'roomType.prices' => function ($priceQuery) use ($rangeStart, $rangeEnd) {
                    $priceQuery
                        ->select(['id', 'room_type_id', 'date_from', 'date_to', 'price', 'branch_id'])
                        ->whereDate('date_from', '<=', $rangeEnd)
                        ->whereDate('date_to', '>=', $rangeStart);
                },
            ])
            ->orderBy('room_number')
            ->get();

        // Lightweight payload for Livewire (avoid serializing full Eloquent + all prices)
        $this->available_rooms = $rooms->map(function (Room $room) use ($settings, $rangeStart) {
            $roomType = $room->roomType;
            $displayRate = $roomType
                ? (float) $roomType->getPriceForDate($rangeStart, true, $settings)
                : 0.0;

            return [
                'id' => (int) $room->id,
                'room_number' => $room->room_number,
                'floor' => $room->floor,
                'section' => $room->section,
                'room_type_id' => (int) $room->room_type_id,
                'room_type_name' => $roomType?->name ?? '',
                'base_price' => (float) ($roomType?->base_price ?? 0),
                'max_occupancy' => (int) ($roomType?->max_occupancy ?? 99),
                'display_rate' => $displayRate,
            ];
        })->values()->all();
    }

    public function createNewReservation()
    {
        abort_unless(user_can('create_reservation'), 403);
        $this->resetForm();
        $this->create_check_in_date = Carbon::today()->format('Y-m-d');
        $this->create_check_out_date = Carbon::tomorrow()->format('Y-m-d');
        $settings = $this->hotelSettings();
        $this->create_check_in_time = substr($settings?->default_check_in_time ?? '14:00', 0, 5);
        $this->create_check_out_time = substr($settings?->default_checkout_time ?? '12:00', 0, 5);
        $this->maxRoomsPerBooking = $settings?->max_rooms_per_booking ?? 10;
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

    public function getDefaultRoomNightlyRate($room): float
    {
        if (is_array($room)) {
            return (float) ($room['display_rate'] ?? $room['base_price'] ?? 0);
        }

        if (!$this->create_check_in_date) {
            return (float) ($room->roomType->base_price ?? 0);
        }

        return (float) $room->roomType->getPriceForDate(
            $this->create_check_in_date,
            true,
            $this->hotelSettings()
        );
    }

    public function getEffectiveRoomNightlyRate($room): float
    {
        $roomId = (int) (is_array($room) ? ($room['id'] ?? 0) : $room->id);

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
     * @param  array<string, mixed>|object|null  $room
     * @param  array<string, mixed>|null  $roomEntry
     * @return array{max: int, total: int, is_over: bool, adults: int, children: int}
     */
    public function getRoomCapacityInfo($room, ?array $roomEntry = null): array
    {
        if (is_array($room)) {
            $max = (int) ($room['max_occupancy'] ?? 99);
        } else {
            $max = (int) ($room->roomType->max_occupancy ?? $room->max_occupancy ?? 99);
        }

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
            ->overlappingStay($checkIn, $checkOut)
            ->exists();
    }

    private function hotelSettings(): ?HotelSetting
    {
        if ($this->hotelSettingsCache === false) {
            $this->hotelSettingsCache = HotelSetting::query()->first();
        }

        return $this->hotelSettingsCache instanceof HotelSetting
            ? $this->hotelSettingsCache
            : null;
    }

    /**
     * Resolve create-form stay datetime (check-in or check-out).
     */
    private function resolveCreateStayDateTime(bool $isCheckIn): Carbon
    {
        $settings = $this->hotelSettings();
        $defaultIn = substr($settings?->default_check_in_time ?? '14:00', 0, 5);
        $defaultOut = substr($settings?->default_checkout_time ?? '12:00', 0, 5);

        if ($isCheckIn) {
            return Reservation::combineDateAndTime(
                $this->create_check_in_date,
                $this->create_check_in_time ?: $defaultIn,
                $defaultIn . ':00'
            );
        }

        return Reservation::combineDateAndTime(
            $this->create_check_out_date,
            $this->create_check_out_time ?: $defaultOut,
            $defaultOut . ':00'
        );
    }

    /**
     * Charge nights for a stay window; same-day (day-use) charges one night.
     */
    private function calculateStayAmount(Room $room, Carbon $checkIn, Carbon $checkOut, ?float $nightlyOverride, ?HotelSetting $settings = null): float
    {
        $totalAmount = 0.0;
        $current = $checkIn->copy()->startOfDay();
        $checkOutDay = $checkOut->copy()->startOfDay();

        $nights = $current->equalTo($checkOutDay)
            ? [$current->copy()]
            : [];

        if (empty($nights)) {
            while ($current->lt($checkOutDay)) {
                $nights[] = $current->copy();
                $current->addDay();
            }
        }

        $settings = $settings ?? $this->hotelSettings();

        foreach ($nights as $night) {
            if ($nightlyOverride !== null) {
                $totalAmount += $nightlyOverride;
            } else {
                $totalAmount += (float) $room->roomType->getPriceForDate($night, true, $settings);
            }
        }

        return $totalAmount;
    }

    /**
     * Estimated stay total for currently selected rooms on the create form.
     */
    public function getCreateEstimatedTotalProperty(): float
    {
        if (
            empty($this->selected_rooms)
            || ! $this->create_check_in_date
            || ! $this->create_check_out_date
        ) {
            return 0.0;
        }

        try {
            $checkIn = $this->resolveCreateStayDateTime(true);
            $checkOut = $this->resolveCreateStayDateTime(false);
        } catch (\Throwable) {
            return 0.0;
        }

        if ($checkOut->lte($checkIn)) {
            return 0.0;
        }

        $total = 0.0;
        $checkInDay = $checkIn->copy()->startOfDay();
        $checkOutDay = $checkOut->copy()->startOfDay();
        $nights = $checkInDay->equalTo($checkOutDay)
            ? 1
            : (int) $checkInDay->diffInDays($checkOutDay);

        foreach ($this->selected_rooms as $entry) {
            $room = collect($this->available_rooms)->firstWhere('id', (int) ($entry['room_id'] ?? 0));
            if (! $room) {
                continue;
            }

            $override = isset($entry['nightly_rate_override'])
                ? (float) $entry['nightly_rate_override']
                : (isset($this->room_rate_overrides[$entry['room_id']])
                    ? (float) $this->room_rate_overrides[$entry['room_id']]
                    : null);

            $nightly = $override ?? (float) ($room['display_rate'] ?? $room['base_price'] ?? 0);
            $total += $nightly * $nights;
        }

        return round($total, 2);
    }

    /**
     * Full booking estimate breakdown for the create form (all selected rooms).
     *
     * @return array{room: float, extra_occupancy: float, tax: float, service: float, total: float, nights: int}
     */
    public function getCreateEstimatedBreakdownProperty(): array
    {
        $empty = [
            'room' => 0.0,
            'extra_occupancy' => 0.0,
            'tax' => 0.0,
            'service' => 0.0,
            'total' => 0.0,
            'nights' => 0,
        ];

        if (
            empty($this->selected_rooms)
            || ! $this->create_check_in_date
            || ! $this->create_check_out_date
        ) {
            return $empty;
        }

        try {
            $checkIn = $this->resolveCreateStayDateTime(true);
            $checkOut = $this->resolveCreateStayDateTime(false);
        } catch (\Throwable) {
            return $empty;
        }

        if ($checkOut->lte($checkIn)) {
            return $empty;
        }

        $settings = $this->hotelSettings();
        $checkInDay = $checkIn->copy()->startOfDay();
        $checkOutDay = $checkOut->copy()->startOfDay();
        $nights = $checkInDay->equalTo($checkOutDay)
            ? 1
            : (int) $checkInDay->diffInDays($checkOutDay);

        $roomTotal = 0.0;
        $extraTotal = 0.0;

        foreach ($this->selected_rooms as $entry) {
            $room = collect($this->available_rooms)->firstWhere('id', (int) ($entry['room_id'] ?? 0));
            if (! $room) {
                continue;
            }

            $override = isset($entry['nightly_rate_override'])
                ? (float) $entry['nightly_rate_override']
                : (isset($this->room_rate_overrides[$entry['room_id']])
                    ? (float) $this->room_rate_overrides[$entry['room_id']]
                    : null);

            $nightly = $override ?? (float) ($room['display_rate'] ?? $room['base_price'] ?? 0);
            $roomTotal += $nightly * $nights;

            $maxOcc = (int) ($room['max_occupancy'] ?? 99);
            $adults = (int) ($entry['adults'] ?? 1);
            $children = (int) ($entry['children'] ?? 0);
            $extraGuests = max(0, ($adults + $children) - $maxOcc);

            if ($extraGuests > 0) {
                $roomType = \Modules\Hotel\Entities\RoomType::find($room['room_type_id'] ?? null);
                if ($roomType) {
                    $extraTotal += $roomType->calculateExtraOccupancyCharges($adults, $children, 0) * $nights;
                }
            }
        }

        $roomTotal = round($roomTotal, 2);
        $extraTotal = round($extraTotal, 2);
        $taxableBase = $roomTotal + $extraTotal;
        $taxRate = (float) ($settings?->tax_rate ?? 0);
        $taxAmount = round($taxableBase * ($taxRate / 100), 2);
        $serviceAmount = 0.0;

        if ($settings && (float) $settings->service_charge_rate > 0) {
            $serviceAmount = round(
                ($taxableBase + $taxAmount) * ((float) $settings->service_charge_rate / 100),
                2
            );
        }

        return [
            'room' => $roomTotal,
            'extra_occupancy' => $extraTotal,
            'tax' => $taxAmount,
            'service' => $serviceAmount,
            'total' => round($taxableBase + $taxAmount + $serviceAmount, 2),
            'nights' => $nights,
        ];
    }

    /**
     * @param  array<int, float>  $totals
     * @return array<int, float>
     */
    private function allocatePaymentAmounts(array $totals, float $paymentAmount): array
    {
        $count = count($totals);
        if ($count === 0 || $paymentAmount <= 0) {
            return array_fill(0, $count, 0.0);
        }

        $grand = array_sum($totals);
        if ($grand <= 0) {
            $parts = array_fill(0, $count, 0.0);
            $parts[0] = round($paymentAmount, 2);

            return $parts;
        }

        $allocated = [];
        $remaining = round($paymentAmount, 2);

        foreach ($totals as $index => $total) {
            if ($index === $count - 1) {
                $allocated[$index] = round($remaining, 2);
                break;
            }

            $share = round($paymentAmount * ((float) $total / $grand), 2);
            $allocated[$index] = $share;
            $remaining = round($remaining - $share, 2);
        }

        return $allocated;
    }

    /**
     * Put the full payment on the first reservation (group folio).
     *
     * @param  array<int, float>  $totals
     * @return array<int, float>
     */
    private function allocatePaymentToFirst(array $totals, float $paymentAmount): array
    {
        $parts = array_fill(0, count($totals), 0.0);
        if (! empty($parts) && $paymentAmount > 0) {
            $parts[0] = round($paymentAmount, 2);
        }

        return $parts;
    }

    private function recordBookingAdvancePayment(
        Reservation $reservation,
        float $amount,
        string $paymentMethod,
        float $processingRate,
        string $notes,
        ?HotelSetting $settings,
    ): void {
        if ($amount <= 0) {
            return;
        }

        HotelPaymentRecorder::record(
            $reservation,
            $amount,
            $paymentMethod,
            HotelPayment::TYPE_ADVANCE,
            null,
            $notes,
            auth()->id(),
            $processingRate,
            (bool) ($settings?->enable_payment_surcharge ?? false),
        );

        // Keep estimated stay total; do not recalculate from charges (none yet).
        $totalPayments = (float) $reservation->payments()
            ->where('payment_type', '!=', HotelPayment::TYPE_REFUND)
            ->sum('amount');
        $totalRefunds = (float) $reservation->payments()
            ->where('payment_type', HotelPayment::TYPE_REFUND)
            ->sum('amount');
        $paidAmount = round($totalPayments - $totalRefunds, 2);

        $reservation->update([
            'paid_amount' => $paidAmount,
            'balance_due' => round(((float) $reservation->total_amount) - $paidAmount, 2),
        ]);
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

        $checkIn = $this->resolveCreateStayDateTime(true);
        $checkOut = $this->resolveCreateStayDateTime(false);

        if ($checkOut->lte($checkIn)) {
            $this->addError('create_check_out_time', 'Check-out must be after check-in (including time).');
            return;
        }

        $settings = $this->hotelSettings();

        $groupBookingId = (count($this->selected_rooms) > 1 && $this->bookingType === 'group')
            ? Reservation::generateGroupBookingId()
            : null;

        $createdCount = 0;
        $checkedInReservations = [];
        $createdEntries = [];
        $pendingCheckIns = [];

        $paymentAmount = round((float) ($this->create_payment_amount ?: 0), 2);
        $paymentMethod = $this->create_payment_method ?: 'cash';
        $paymentRate = (float) ($this->create_payment_processing_rate ?: 0);
        $paymentNotes = trim((string) $this->create_payment_notes) !== ''
            ? trim((string) $this->create_payment_notes)
            : 'Advance payment at booking';

        if ($paymentAmount > 0 && ! in_array($paymentMethod, ['cash', 'card', 'bank_transfer', 'upi', 'other'], true)) {
            $this->addError('create_payment_method', 'Please select a payment method.');
            return;
        }

        $selectedRoomIds = collect($this->selected_rooms)
            ->pluck('room_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        try {
            DB::transaction(function () use (
                $checkIn,
                $checkOut,
                $groupBookingId,
                $settings,
                $checkInAfterCreate,
                $paymentAmount,
                $paymentMethod,
                $paymentRate,
                $paymentNotes,
                $selectedRoomIds,
                &$createdCount,
                &$checkedInReservations,
                &$createdEntries,
                &$pendingCheckIns
            ) {
            $rangeStart = $checkIn->toDateString();
            $rangeEnd = $checkOut->toDateString();

            $rooms = Room::query()
                ->with([
                    'roomType:id,name,base_price,max_occupancy,branch_id',
                    'roomType.prices' => function ($priceQuery) use ($rangeStart, $rangeEnd) {
                        $priceQuery
                            ->select(['id', 'room_type_id', 'date_from', 'date_to', 'price', 'branch_id'])
                            ->whereDate('date_from', '<=', $rangeEnd)
                            ->whereDate('date_to', '>=', $rangeStart);
                    },
                ])
                ->whereIn('id', $selectedRoomIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($rooms->count() !== count($selectedRoomIds)) {
                throw new \RuntimeException('One or more selected rooms are no longer available.');
            }

            $conflictedRoomIds = Reservation::query()
                ->whereIn('room_id', $selectedRoomIds)
                ->overlappingStay($checkIn, $checkOut)
                ->pluck('room_id')
                ->unique()
                ->all();

            $checkInTime = Reservation::normalizeTimeString($this->create_check_in_time)
                ?? Reservation::normalizeTimeString($settings?->default_check_in_time ?? '14:00')
                ?? '14:00:00';
            $checkOutTime = Reservation::normalizeTimeString($this->create_check_out_time)
                ?? Reservation::normalizeTimeString($settings?->default_checkout_time ?? '12:00')
                ?? '12:00:00';

            foreach ($this->selected_rooms as $entry) {
                $roomId = (int) $entry['room_id'];
                /** @var Room $room */
                $room = $rooms->get($roomId);

                if (in_array($roomId, $conflictedRoomIds, true)) {
                    throw new \RuntimeException(
                        "Room {$room->room_number} is no longer available for the selected dates and times."
                    );
                }

                $nightlyOverride = isset($entry['nightly_rate_override'])
                    ? round((float) $entry['nightly_rate_override'], 2)
                    : (isset($this->room_rate_overrides[$entry['room_id']])
                        ? round((float) $this->room_rate_overrides[$entry['room_id']], 2)
                        : null);

                $estimateReservation = new Reservation([
                    'branch_id' => $room->branch_id,
                    'check_in_date' => $this->create_check_in_date,
                    'checkout_date' => $this->create_check_out_date,
                    'adults' => (int) ($entry['adults'] ?? 1),
                    'children' => (int) ($entry['children'] ?? 0),
                    'nightly_rate_override' => $nightlyOverride,
                ]);
                $estimateReservation->setRelation('room', $room);
                $totalAmount = $this->calculateStayTotal($estimateReservation);

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

                $createdEntries[] = [
                    'reservation' => $reservation,
                    'total' => (float) $totalAmount,
                ];

                if ($checkInAfterCreate) {
                    $pendingCheckIns[] = [
                        'reservation' => $reservation,
                        'total' => (float) $totalAmount,
                    ];
                } else {
                    $room->update(['status' => 'reserved']);
                }

                $createdCount++;
            }

            if ($checkInAfterCreate) {
                $totals = array_column($pendingCheckIns, 'total');
                if ($paymentAmount > 0) {
                    $advances = $groupBookingId
                        ? $this->allocatePaymentToFirst($totals, $paymentAmount)
                        : $this->allocatePaymentAmounts($totals, $paymentAmount);
                } else {
                    $advances = array_map(
                        fn (float $total) => $settings ? (float) $settings->calculateDeposit($total) : 0.0,
                        $totals
                    );
                }

                foreach ($pendingCheckIns as $index => $item) {
                    $this->performCheckInOnReservation(
                        $item['reservation'],
                        (float) ($advances[$index] ?? 0),
                        $paymentMethod,
                        $paymentRate,
                        $paymentNotes,
                    );
                    $checkedInReservations[] = $item['reservation']->fresh(['room']);
                }
            } elseif ($paymentAmount > 0 && ! empty($createdEntries)) {
                $totals = array_column($createdEntries, 'total');
                $parts = $groupBookingId
                    ? $this->allocatePaymentToFirst($totals, $paymentAmount)
                    : $this->allocatePaymentAmounts($totals, $paymentAmount);

                foreach ($createdEntries as $index => $item) {
                    $part = (float) ($parts[$index] ?? 0);
                    if ($part <= 0) {
                        continue;
                    }

                    $this->recordBookingAdvancePayment(
                        $item['reservation'],
                        $part,
                        $paymentMethod,
                        $paymentRate,
                        $paymentNotes,
                        $settings,
                    );
                }
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
        $this->showCreateGuest = false;
        $this->new_guest_first_name = '';
        $this->new_guest_last_name = '';
        $this->new_guest_email = '';
        $this->new_guest_phone = '';
        $this->create_room_id = '';
        $this->create_check_in_date = '';
        $this->create_check_out_date = '';
        $this->create_check_in_time = '';
        $this->create_check_out_time = '';
        $this->create_room_type_id = '';
        $this->create_adults = 1;
        $this->create_children = 0;
        $this->create_notes = '';
        $this->create_booking_source = 'walk-in';
        $this->create_payment_amount = '';
        $this->create_payment_method = 'cash';
        $this->create_payment_processing_rate = 0;
        $this->create_payment_notes = '';
        $this->available_rooms = [];
        $this->selected_rooms = [];
        $this->room_rate_overrides = [];
        $this->bookingType = 'group';
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
            if ($this->checkout_reservation->group_booking_id) {
                $groupReservations = Reservation::where('group_booking_id', $this->checkout_reservation->group_booking_id)->get();
                $this->checkout_total_amount = 0;
                $this->checkout_balance_due = 0;
                foreach ($groupReservations as $res) {
                    $res->calculateTotal();
                    $res->refresh();
                    $this->checkout_total_amount += $res->total_amount;
                    $this->checkout_balance_due += $res->balance_due;
                }
            } else {
                $this->checkout_reservation->calculateTotal();
                $this->checkout_reservation->refresh();
                $this->checkout_total_amount = $this->checkout_reservation->total_amount;
                $this->checkout_balance_due = $this->checkout_reservation->balance_due;
            }
            
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

            if ($this->checkout_reservation->group_booking_id) {
                $groupReservations = Reservation::with(['room'])->where('group_booking_id', $this->checkout_reservation->group_booking_id)
                    ->where('status', Reservation::STATUS_CHECKED_IN)
                    ->get();

                if ((float) $this->checkout_extended_days > 0 && (float) $this->checkout_extended_amount > 0) {
                    $extDays = (float) $this->checkout_extended_days;
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

                    $this->checkout_reservation->recalculateTaxCharge();
                    $this->checkout_reservation->recalculateLinkedServiceCharge();
                    $this->checkout_reservation->calculateTotal();
                    $this->checkout_reservation->refresh();
                }

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

                        $this->checkout_reservation->calculateTotal();
                        $this->checkout_reservation->refresh();
                    }
                }

                if ($this->checkout_amount_paid > 0) {
                    $remainingPayment = (float) $this->checkout_amount_paid;
                    foreach ($groupReservations as $res) {
                        if ($remainingPayment <= 0) break;
                        $due = (float) $res->balance_due;
                        if ($due > 0) {
                            $allocatedPayment = min($remainingPayment, $due);
                            HotelPaymentRecorder::record(
                                $res,
                                $allocatedPayment,
                                $this->checkout_payment_method,
                                HotelPayment::TYPE_SETTLEMENT,
                                null,
                                $this->checkout_notes ?: 'Settlement at checkout (Group)',
                                auth()->id(),
                                (float) $this->checkout_processing_rate,
                                $surchargeEnabled,
                            );
                            $res->calculateTotal();
                            $remainingPayment -= $allocatedPayment;
                        }
                    }
                    if ($remainingPayment > 0) {
                        HotelPaymentRecorder::record(
                            $this->checkout_reservation,
                            $remainingPayment,
                            $this->checkout_payment_method,
                            HotelPayment::TYPE_SETTLEMENT,
                            null,
                            $this->checkout_notes ?: 'Settlement at checkout (Group excess)',
                            auth()->id(),
                            (float) $this->checkout_processing_rate,
                            $surchargeEnabled,
                        );
                        $this->checkout_reservation->calculateTotal();
                    }
                }

                foreach ($groupReservations as $res) {
                    $res->update([
                        'status' => Reservation::STATUS_CHECKED_OUT,
                        'actual_checkout' => Carbon::parse($this->checkout_date_actual)->setTimeFrom(now()),
                    ]);
                    $res->calculateTotal();
                    if ($res->room) {
                        $res->room->update(['status' => Room::STATUS_AVAILABLE]);
                    }

                    ActivityLogger::recordEvent(
                        activityEvent: ActivityEvent::GuestCheckedOut,
                        description: 'Guest checked out from room ' . ($res->room?->room_number ?? 'N/A') . ' (Group)',
                        subject: $res->fresh(),
                        properties: [
                            'reservation_id' => $res->id,
                            'reservation_number' => $res->reservation_number ?? null,
                            'amount_paid' => $this->checkout_amount_paid,
                        ],
                        restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
                    );
                }
            } else {
                if ((float) $this->checkout_extended_days > 0 && (float) $this->checkout_extended_amount > 0) {
                    $extDays = (float) $this->checkout_extended_days;
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

                    $this->checkout_reservation->recalculateTaxCharge();
                    $this->checkout_reservation->recalculateLinkedServiceCharge();
                    $this->checkout_reservation->calculateTotal();
                    $this->checkout_reservation->refresh();
                }

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

                        $this->checkout_reservation->calculateTotal();
                        $this->checkout_reservation->refresh();
                    }
                }

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

                $this->checkout_reservation->update([
                    'status' => Reservation::STATUS_CHECKED_OUT,
                    'actual_checkout' => Carbon::parse($this->checkout_date_actual)->setTimeFrom(now()),
                ]);

                $this->checkout_reservation->calculateTotal();

                if ($this->checkout_reservation->room) {
                    $this->checkout_reservation->room->update(['status' => Room::STATUS_AVAILABLE]);
                }

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
        });

        $this->alert('success', 'Guest successfully checked out. Balance updated.');
        $this->showEditReservation = false;
        $this->resetCheckoutForm();
        $this->dispatch('$refresh');
    }

    public $pendingCancelId = null;
    public $pendingDeleteId = null;
    public $pendingUpdateId = null;

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

        $groupReservations = $reservation->group_booking_id
            ? Reservation::where('group_booking_id', $reservation->group_booking_id)->get()
            : collect([$reservation]);

        DB::transaction(function () use ($groupReservations) {
            foreach ($groupReservations as $res) {
                $reservationNumber = $res->reservation_number;
                $res->delete();

                ActivityLogger::recordEvent(
                    activityEvent: ActivityEvent::ReservationCancelled,
                    description: 'Reservation deleted' . ($reservationNumber ? " (#{$reservationNumber})" : ''),
                    subject: null,
                    properties: [
                        'reservation_number' => $reservationNumber,
                    ],
                    restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
                );
            }
        });

        $this->pendingDeleteId = null;
        $this->alert('success', 'Reservation deleted successfully.');
        $this->dispatch('$refresh');
    }

    public function confirmUpdateReservation($id)
    {
        $this->openUpdateReservation($id);
    }

    #[On('updateReservationConfirmed')]
    public function openUpdateReservation($id = null)
    {
        abort_unless(user_can('edit_reservation'), 403);
        $id = $id ?? $this->pendingUpdateId;
        if (!$id) {
            return;
        }
        $reservation = Reservation::with(['guest', 'room.roomType'])->find($id);
        if (!$reservation) {
            $this->alert('error', 'Reservation not found.');
            return;
        }
        // Open the date-editor update modal for all statuses
        $this->updateReservation      = $reservation;
        $this->update_check_in_date   = $reservation->check_in_date instanceof \Carbon\Carbon
            ? $reservation->check_in_date->format('Y-m-d')
            : \Carbon\Carbon::parse($reservation->check_in_date)->format('Y-m-d');
        $this->update_check_out_date  = $reservation->checkout_date instanceof \Carbon\Carbon
            ? $reservation->checkout_date->format('Y-m-d')
            : \Carbon\Carbon::parse($reservation->checkout_date)->format('Y-m-d');
        $this->update_check_in_time   = substr((string) ($reservation->check_in_time ?? '14:00'), 0, 5);
        $this->update_check_out_time  = substr((string) ($reservation->checkout_time ?? '12:00'), 0, 5);
        $this->update_notes           = '';

        $this->update_payment_id = null;
        $this->update_payment_amount = 0;
        $this->update_payment_method = 'cash';

        if ($reservation->status === Reservation::STATUS_CHECKED_IN) {
            $payment = \Modules\Hotel\Entities\HotelPayment::where('reservation_id', $reservation->id)
                ->where('payment_type', \Modules\Hotel\Entities\HotelPayment::TYPE_ADVANCE)
                ->first();
            if ($payment) {
                $this->update_payment_id = $payment->id;
                $this->update_payment_amount = $payment->amount;
                $this->update_payment_method = $payment->payment_method;
            }
        }

        $this->pendingUpdateId        = null;
        $this->showUpdateModal        = true;
    }

    public function saveReservationUpdate()
    {
        abort_unless(user_can('edit_reservation'), 403);

        $this->validate([
            'update_check_in_date'  => 'required|date',
            'update_check_out_date' => 'required|date|after_or_equal:update_check_in_date',
            'update_check_in_time'  => 'required|date_format:H:i',
            'update_check_out_time' => 'required|date_format:H:i',
        ]);

        if (!$this->updateReservation) {
            return;
        }

        $checkIn = Reservation::combineDateAndTime(
            $this->update_check_in_date,
            $this->update_check_in_time,
            '14:00:00'
        );
        $checkOut = Reservation::combineDateAndTime(
            $this->update_check_out_date,
            $this->update_check_out_time,
            '12:00:00'
        );

        if ($checkOut->lte($checkIn)) {
            $this->addError('update_check_out_time', 'Check-out must be after check-in (including time).');
            return;
        }

        $reservation = Reservation::find($this->updateReservation->id);
        if (!$reservation) {
            $this->alert('error', 'Reservation not found.');
            return;
        }

        // Update reservation dates on all rooms in group, or just this one
        $reservations = $reservation->group_booking_id
            ? Reservation::where('group_booking_id', $reservation->group_booking_id)->get()
            : collect([$reservation]);

        $excludeIds = $reservations->pluck('id')->all();

        foreach ($reservations as $res) {
            if (! $res->room_id) {
                continue;
            }

            $conflict = Room::find($res->room_id)
                ?->reservations()
                ->whereNotIn('id', $excludeIds)
                ->overlappingStay($checkIn, $checkOut)
                ->exists();

            if ($conflict) {
                $this->alert('error', "Room {$res->room?->room_number} is not available for the selected dates and times.", [
                    'toast' => true,
                    'position' => 'top-end',
                ]);

                return;
            }
        }

        $checkInTime = Reservation::normalizeTimeString($this->update_check_in_time) ?? '14:00:00';
        $checkOutTime = Reservation::normalizeTimeString($this->update_check_out_time) ?? '12:00:00';

        DB::transaction(function () use ($reservations, $checkInTime, $checkOutTime) {
            foreach ($reservations as $res) {
                $res->update([
                    'check_in_date'   => $this->update_check_in_date,
                    'check_in_time'   => $checkInTime,
                    'checkout_date'   => $this->update_check_out_date,
                    'checkout_time'   => $checkOutTime,
                ]);
                $res->calculateTotal();

                ActivityLogger::recordEvent(
                    activityEvent: ActivityEvent::ReservationUpdated,
                    description: 'Reservation dates updated' . ($res->reservation_number ? " (#{$res->reservation_number})" : ''),
                    subject: $res,
                    properties: [
                        'reservation_id'    => $res->id,
                        'check_in_date'     => $this->update_check_in_date,
                        'checkout_date'     => $this->update_check_out_date,
                    ],
                    restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
                );
            }
        });

        // Handle payment update if checked in
        if ($reservation->status === Reservation::STATUS_CHECKED_IN) {
            $amount = (float) $this->update_payment_amount;
            if ($this->update_payment_id) {
                $payment = \Modules\Hotel\Entities\HotelPayment::find($this->update_payment_id);
                if ($payment) {
                    if ($amount > 0) {
                        $payment->update([
                            'amount' => $amount,
                            'payment_method' => $this->update_payment_method,
                        ]);
                    } else {
                        $payment->delete();
                    }
                }
            } elseif ($amount > 0) {
                \Modules\Hotel\Services\HotelPaymentRecorder::record(
                    $reservation,
                    $amount,
                    $this->update_payment_method,
                    \Modules\Hotel\Entities\HotelPayment::TYPE_ADVANCE,
                    null,
                    'Advance payment updated at check-in edit',
                    auth()->id()
                );
            }
        }

        $this->showUpdateModal  = false;
        $this->updateReservation = null;
        $this->alert('success', 'Reservation updated successfully.');
        $this->dispatch('$refresh');
    }

    public function confirmCancelReservation($id)
    {
        abort_unless(user_can('edit_reservation'), 403);
        $this->pendingCancelId = $id;
        $this->alert('warning', 'Cancel this reservation?', [
            'showConfirmButton' => true,
            'showCancelButton' => true,
            'confirmButtonText' => 'Yes, Cancel',
            'cancelButtonText'  => 'No',
            'onConfirmed'       => 'cancelReservationConfirmed',
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

        if (!in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN])) {
            $this->alert('error', 'Cannot cancel reservation in current status.');
            return;
        }

        $groupReservations = $reservation->group_booking_id
            ? Reservation::where('group_booking_id', $reservation->group_booking_id)->get()
            : collect([$reservation]);

        DB::transaction(function () use ($groupReservations) {
            foreach ($groupReservations as $res) {
                $res->update(['status' => Reservation::STATUS_CANCELLED]);

                if ($res->room && in_array($res->room->status, ['reserved', 'occupied', 'dirty'])) {
                    $res->room->update(['status' => 'available']);
                }

                ActivityLogger::recordEvent(
                    activityEvent: ActivityEvent::ReservationCancelled,
                    description: 'Reservation cancelled' . ($res->reservation_number ? " (#{$res->reservation_number})" : ''),
                    subject: $res,
                    properties: [
                        'reservation_id' => $res->id,
                        'reservation_number' => $res->reservation_number ?? null,
                    ],
                    restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
                );
            }
        });

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

        $groupReservations = $reservation->group_booking_id
            ? Reservation::where('group_booking_id', $reservation->group_booking_id)->get()
            : collect([$reservation]);

        DB::transaction(function () use ($groupReservations) {
            foreach ($groupReservations as $res) {
                $res->update(['status' => Reservation::STATUS_NO_SHOW]);

                if ($res->room) {
                    $res->room->update(['status' => 'available']);
                }
            }
        });

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
            ->where(function ($q) {
                $q->whereNull('group_booking_id')
                  ->orWhereRaw('id = (SELECT MIN(id) FROM hotel_reservations as hr WHERE hr.group_booking_id = hotel_reservations.group_booking_id)');
            })
            ->when($this->search, function ($query) {
                $query->where(function($sub) {
                    $sub->whereHas('guest', function ($q) {
                        $q->where('first_name', 'like', '%' . $this->search . '%')
                            ->orWhere('last_name', 'like', '%' . $this->search . '%');
                    })->orWhere('reservation_number', 'like', '%' . $this->search . '%')
                      ->orWhere('group_booking_id', 'like', '%' . $this->search . '%');
                });
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

        $guests = ($this->showCreateReservation || $this->showCreateGuest)
            ? \Modules\Hotel\Entities\Guest::query()
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'email'])
            : collect();
        $roomTypes = \Modules\Hotel\Entities\RoomType::query()
            ->orderBy('name')
            ->get(['id', 'name']);
        $hotelSettings = $this->hotelSettings();

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

    public function undoCheckout($id)
    {
        abort_unless(user_can('check_out_guest'), 403);

        $reservation = Reservation::with(['room'])->find($id);
        if (!$reservation || $reservation->status !== Reservation::STATUS_CHECKED_OUT) {
            return;
        }

        DB::transaction(function () use ($reservation) {
            $groupReservations = $reservation->group_booking_id
                ? Reservation::where('group_booking_id', $reservation->group_booking_id)->get()
                : collect([$reservation]);

            foreach ($groupReservations as $res) {
                $res->update([
                    'status' => Reservation::STATUS_CHECKED_IN,
                    'actual_checkout' => null,
                ]);

                if ($res->room) {
                    $res->room->update(['status' => Room::STATUS_OCCUPIED]);
                }

                $res->charges()
                    ->where(function ($q) {
                        $q->where('description', 'like', 'Extended stay (%)')
                          ->orWhere('description', 'like', 'Late checkout surcharge (%');
                    })->delete();

                $res->payments()
                    ->where('payment_type', HotelPayment::TYPE_SETTLEMENT)
                    ->delete();

                $res->recalculateTaxCharge();
                $res->recalculateLinkedServiceCharge();
                $res->calculateTotal();
                
                ActivityLogger::recordEvent(
                    activityEvent: ActivityEvent::CheckoutUndone,
                    description: 'Guest checkout undone for room ' . ($res->room?->room_number ?? 'N/A'),
                    subject: $res->fresh(),
                    properties: [
                        'reservation_id' => $res->id,
                        'reservation_number' => $res->reservation_number ?? null,
                    ],
                    restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
                );
            }
        });

        $this->alert('success', 'Guest checkout undone successfully. Status set back to Checked In.');
        $this->dispatch('$refresh');
    }
}
