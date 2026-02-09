<?php

namespace Modules\Hotel\Livewire\Reservation;

use Livewire\Component;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;
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
        $branchId = auth()->user()->branch_id ?? 1;
        $settings = HotelSetting::where('branch_id', $branchId)->first();
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

            // 1. Generate room night charges
            $this->generateRoomNightCharges($reservation);

            // 2. Record advance payment if amount > 0
            if ($this->checkInAdvanceAmount > 0) {
                HotelPayment::create([
                    'reservation_id' => $reservation->id,
                    'branch_id' => $reservation->branch_id,
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
        $checkIn = $reservation->check_in_date->copy();
        $checkOut = $reservation->checkout_date->copy();

        // Create one charge per night
        $currentDate = $checkIn->copy();
        while ($currentDate->lt($checkOut)) {
            $nightlyRate = $roomType->getPriceForDate($currentDate);

            RoomCharge::create([
                'reservation_id' => $reservation->id,
                'charge_type' => RoomCharge::TYPE_ROOM_NIGHT,
                'description' => 'Room ' . $reservation->room->room_number . ' - ' . $currentDate->format('d M Y'),
                'amount' => $nightlyRate,
                'charge_date' => $currentDate->toDateString(),
            ]);

            $currentDate->addDay();
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
            'branch_id' => auth()->user()->branch_id ?? 1,
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

        $query = \Modules\Hotel\Entities\Room::where('branch_id', auth()->user()->branch_id ?? 1)
            ->where('status', '!=', 'maintenance')
            ->where('status', '!=', 'blocked');

        if ($this->create_room_type_id) {
            $query->where('room_type_id', $this->create_room_type_id);
        }

        // Exclude rooms that have confirmed reservations intersecting with the selected dates
        $query->whereDoesntHave('reservations', function ($q) use ($checkIn, $checkOut) {
            $q->whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN])
              ->where(function ($q2) use ($checkIn, $checkOut) {
                  $q2->whereBetween('check_in_date', [$checkIn, $checkOut])
                     ->orWhereBetween('checkout_date', [$checkIn, $checkOut])
                     ->orWhere(function ($q3) use ($checkIn, $checkOut) {
                         $q3->where('check_in_date', '<', $checkIn)
                            ->where('checkout_date', '>', $checkOut);
                     });
              });
        });

        $this->available_rooms = $query->with('roomType')->get();
    }

    public function createNewReservation()
    {
        abort_unless(user_can('create_reservation'), 403);
        $this->resetForm();
        $this->create_check_in_date = Carbon::today()->format('Y-m-d');
        $this->create_check_out_date = Carbon::tomorrow()->format('Y-m-d');

        // Load max rooms setting
        $branchId = auth()->user()->branch_id ?? 1;
        $settings = HotelSetting::where('branch_id', $branchId)->first();
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

    public function saveReservation()
    {
        abort_unless(user_can('create_reservation'), 403);

        $this->validate();

        if (empty($this->selected_rooms)) {
            $this->addError('selected_rooms', 'Please select at least one room.');
            return;
        }

        $branchId = auth()->user()->branch_id ?? 1;
        $checkIn = Carbon::parse($this->create_check_in_date);
        $checkOut = Carbon::parse($this->create_check_out_date);

        // Generate group booking ID only if multiple rooms
        $groupBookingId = count($this->selected_rooms) > 1
            ? Reservation::generateGroupBookingId($branchId)
            : null;

        DB::transaction(function () use ($branchId, $checkIn, $checkOut, $groupBookingId) {
            foreach ($this->selected_rooms as $entry) {
                $room = \Modules\Hotel\Entities\Room::with('roomType')->find($entry['room_id']);
                if (!$room) {
                    continue;
                }

                // Calculate total using dynamic pricing per night
                $totalAmount = 0;
                $current = $checkIn->copy();
                while ($current->lt($checkOut)) {
                    $totalAmount += (float) $room->roomType->getPriceForDate($current);
                    $current->addDay();
                }

                Reservation::create([
                    'branch_id' => $branchId,
                    'guest_id' => $this->create_guest_id,
                    'room_id' => $entry['room_id'],
                    'group_booking_id' => $groupBookingId,
                    'check_in_date' => $this->create_check_in_date,
                    'checkout_date' => $this->create_check_out_date,
                    'adults' => $entry['adults'] ?? 1,
                    'children' => $entry['children'] ?? 0,
                    'special_requests' => $this->create_notes,
                    'booking_source' => $this->create_booking_source ?: 'walk-in',
                    'status' => Reservation::STATUS_CONFIRMED,
                    'total_amount' => $totalAmount,
                    'balance_due' => $totalAmount,
                    'created_by_user_id' => auth()->id(),
                ]);
            }
        });

        $roomCount = count($this->selected_rooms);
        $message = $roomCount > 1
            ? "{$roomCount} room reservations created (Group: {$groupBookingId})"
            : 'Reservation created successfully';

        $this->alert('success', $message);

        $this->showCreateReservation = false;
        $this->resetForm();
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
            
            $this->showEditReservation = true;
        }
    }

    public function processCheckout()
    {
        abort_unless(user_can('check_out_guest'), 403);

        $this->validate([
            'checkout_amount_paid' => 'required|numeric|min:0',
            'checkout_payment_method' => 'required|string',
        ]);

        if (!$this->checkout_reservation) {
            return;
        }

        DB::transaction(function () {
            // Record settlement payment if amount > 0
            if ($this->checkout_amount_paid > 0) {
                HotelPayment::create([
                    'reservation_id' => $this->checkout_reservation->id,
                    'branch_id' => $this->checkout_reservation->branch_id,
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
                'actual_checkout' => now(),
            ]);

            // Recalculate totals
            $this->checkout_reservation->calculateTotal();

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

    public function cancelReservation($id)
    {
        abort_unless(user_can('edit_reservation'), 403);
        $reservation = Reservation::find($id);
        
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
        $this->editingReservationId = null;
    }

    public function render()
    {
        $branchId = auth()->user()->branch_id ?? 1;

        $reservations = Reservation::with(['guest', 'room.roomType', 'branch'])
            ->where('branch_id', $branchId)
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

        $guests = \Modules\Hotel\Entities\Guest::where('branch_id', auth()->user()->branch_id ?? 1)->orderBy('first_name')->get();
        $roomTypes = \Modules\Hotel\Entities\RoomType::where('branch_id', auth()->user()->branch_id ?? 1)->get();

        return view('hotel::livewire.reservation.reservation-list', [
            'reservations' => $reservations,
            'guests' => $guests,
            'roomTypes' => $roomTypes,
        ])->layout('layouts.app');
    }
}
