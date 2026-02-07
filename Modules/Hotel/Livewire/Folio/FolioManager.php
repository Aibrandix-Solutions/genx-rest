<?php

namespace Modules\Hotel\Livewire\Folio;

use Livewire\Component;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;
use Illuminate\Support\Facades\DB;

class FolioManager extends Component
{
    public $reservationNumber;
    public $reservation;
    public $charges;
    public $orders;
    public $payments;
    public $balance = 0;
    public $totalCharges = 0;
    public $totalOrders = 0;
    public $totalPayments = 0;

    public function mount($reservationNumber)
    {
        $this->reservationNumber = $reservationNumber;
        $this->loadData();
    }

    public function loadData()
    {
        $this->reservation = Reservation::with(['guest', 'room', 'room.roomType', 'orders'])
            ->where('reservation_number', $this->reservationNumber)
            ->firstOrFail();

        // Security Check: Ensure user has permission or belongs to same branch
        if (auth()->user()->branch_id && $this->reservation->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized access to this folio.');
        }
        
        // All posted charges (room nights, restaurant/room-service, minibar, etc.)
        // Restaurant orders are auto-posted as charges by the OrderObserver when billed.
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

        $this->totalCharges = $this->charges->sum('amount');
        $this->totalOrders = $this->orders->sum('total'); // pending orders not yet posted

        // Balance = all posted charges + pending order totals - payments
        $this->balance = ($this->totalCharges + $this->totalOrders) - $this->totalPayments;
    }

    public function render()
    {
        return view('hotel::livewire.folio.folio-manager')->layout('layouts.app');
    }
}
