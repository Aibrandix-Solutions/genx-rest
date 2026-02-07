<?php

namespace Modules\Hotel\Livewire\Folio;

use Livewire\Component;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;

class InvoiceV2 extends Component
{
    public $reservationId;
    public $reservation;
    public $charges;
    public $totalCharges = 0;
    public $totalPayments = 0;
    public $balance = 0;

    public function mount($reservationId)
    {
        $this->reservationId = $reservationId;
        $this->loadData();
    }

    public function loadData()
    {
        $this->reservation = Reservation::with(['guest', 'room', 'room.roomType', 'branch'])->findOrFail($this->reservationId);
        
        $this->charges = RoomCharge::where('reservation_id', $this->reservationId)
            ->orderBy('charge_date', 'asc')
            ->get();

        $this->totalCharges = $this->charges->sum('amount');
        // Future: Handle payments
        $this->balance = $this->totalCharges - $this->totalPayments;
    }

    public function render()
    {
        return view('hotel::livewire.folio.invoice-v2')
            ->layout('layouts.empty'); // Use empty layout for print view
    }
}
