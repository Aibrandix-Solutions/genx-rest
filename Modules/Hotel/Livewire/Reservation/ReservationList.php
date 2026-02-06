<?php

namespace Modules\Hotel\Livewire\Reservation;

use Livewire\Component;
use Livewire\Attributes\On;
use Modules\Hotel\Entities\Reservation;
use Carbon\Carbon;

class ReservationList extends Component
{
    public $showCreateReservation = false;
    public $showEditReservation = false;
    public $editingReservationId = null;
    public $search = '';
    public $statusFilter = 'all';
    public $dateFilter = 'all';

    #[On('reservation-saved')]
    public function reservationSaved()
    {
        $this->showCreateReservation = false;
        $this->showEditReservation = false;
        $this->editingReservationId = null;
        $this->dispatch('$refresh');
    }

    // editReservation method replaced below with checkout logic

    public function checkIn($id)
    {
        $reservation = Reservation::find($id);
        if ($reservation && $reservation->status === Reservation::STATUS_CONFIRMED) {
            $reservation->update([
                'status' => Reservation::STATUS_CHECKED_IN,
                'actual_check_in' => now(),
            ]);

            // Update room status
            if ($reservation->room) {
                $reservation->room->update(['status' => 'occupied']);
            }

            $this->dispatch('$refresh');
        }
    }

    public $create_guest_id = '';
    public $create_room_id = '';
    public $create_check_in_date = '';
    public $create_check_out_date = '';
    public $create_room_type_id = ''; // Filter for finding rooms
    public $create_adults = 1;
    public $create_children = 0;
    public $create_notes = '';
    
    public $available_rooms = [];

    protected function rules()
    {
        return [
            'create_guest_id' => 'required|exists:hotel_guests,id',
            'create_check_in_date' => 'required|date|after_or_equal:today',
            'create_check_out_date' => 'required|date|after:create_check_in_date',
            'create_room_id' => 'required|exists:hotel_rooms,id',
            'create_adults' => 'required|integer|min:1',
            'create_children' => 'integer|min:0',
            'create_notes' => 'nullable|string',
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

        $this->available_rooms = $query->get();
    }

    public function createNewReservation()
    {
        $this->resetForm();
        $this->create_check_in_date = Carbon::today()->format('Y-m-d');
        $this->create_check_out_date = Carbon::tomorrow()->format('Y-m-d');
        $this->showCreateReservation = true;
    }

    public function saveReservation()
    {
        $this->validate();

        $room = \Modules\Hotel\Entities\Room::find($this->create_room_id);
        if (!$room) {
             $this->addError('create_room_id', 'Selected room is invalid.');
             return;
        }

        // Calculate total amount based on room price and duration
        // Assuming RoomType has base_price
        $days = Carbon::parse($this->create_check_in_date)->diffInDays(Carbon::parse($this->create_check_out_date));
        $totalAmount = $room->roomType->base_price * $days;

        Reservation::create([
            'branch_id' => auth()->user()->branch_id ?? 1,
            'guest_id' => $this->create_guest_id,
            'room_id' => $this->create_room_id,
            'check_in_date' => $this->create_check_in_date,
            'checkout_date' => $this->create_check_out_date,
            'adults' => $this->create_adults,
            'children' => $this->create_children,
            'status' => Reservation::STATUS_CONFIRMED,
            'total_amount' => $totalAmount,
            'balance_due' => $totalAmount, // Assuming no upfront payment for now
            'notes' => $this->create_notes,
        ]);
        
        // Update room status if check-in is today
        if (Carbon::parse($this->create_check_in_date)->isToday()) {
            // We keep it as confirmed until manual check-in or auto-update script runs, 
            // but for simplicity we could mark room as occupied if it was immediate check-in.
            // keeping standard flow: Confirmed -> Check In action.
        }

        $this->dispatch('show-notification', [
            'title' => 'Success',
            'message' => 'Reservation created successfully',
            'type' => 'success'
        ]);

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
        $this->available_rooms = [];
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
        $this->editingReservationId = $id;
        $this->checkout_reservation = Reservation::with(['guest', 'room.roomType'])->find($id);
        
        if ($this->checkout_reservation) {
            $this->checkout_total_amount = $this->checkout_reservation->total_amount;
            $this->checkout_balance_due = $this->checkout_reservation->balance_due;
            
            // Default payment to full balance
            $this->checkout_amount_paid = $this->checkout_balance_due;
            
            $this->showEditReservation = true;
        }
    }

    public function processCheckout()
    {
        $this->validate([
            'checkout_amount_paid' => 'required|numeric|min:0',
            'checkout_payment_method' => 'required|string',
        ]);

        // Ensure full payment (Professional Requirement)
        if (abs($this->checkout_balance_due - $this->checkout_amount_paid) > 0.01) {
             $this->addError('checkout_amount_paid', 'Full payment is required to check out.');
             return;
        }

        if (!$this->checkout_reservation) {
            return;
        }

        // Update reservation
        $this->checkout_reservation->update([
            'status' => Reservation::STATUS_CHECKED_OUT,
            'actual_check_out' => now(),
            'balance_due' => $this->checkout_balance_due - $this->checkout_amount_paid,
            // In a real system, we'd create a Payment record here
        ]);

        // Update room status
        if ($this->checkout_reservation->room) {
            // Set to cleanup or available. Let's set to 'cleanup' if we had a housekeeping module, 
            // but for now 'available' is safest or 'dirty' if checking housekeeping.
            // Let's assume 'dirty' needs to be cleaned.
            $this->checkout_reservation->room->update(['status' => 'available']); 
        }

        $this->dispatch('show-notification', [
            'title' => 'Checked Out',
            'message' => 'Guest successfully checked out. Balance updated.',
            'type' => 'success'
        ]);

        $this->showEditReservation = false;
        $this->resetCheckoutForm();
        $this->dispatch('$refresh'); // Refresh list to show updated status
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
