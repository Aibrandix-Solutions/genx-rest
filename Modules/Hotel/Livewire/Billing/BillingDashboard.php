<?php

namespace Modules\Hotel\Livewire\Billing;

use Livewire\Component;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\HotelPayment;

class BillingDashboard extends Component
{
    public $outstandingReservations;
    public $recentPayments;
    public $totalOutstandingBalance = 0;

    public function mount()
    {
        abort_unless(user_can('view_hotel_billing'), 403);
        $this->loadData();
    }

    public function loadData()
    {
        $this->outstandingReservations = Reservation::with(['guest', 'room'])
            ->whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN])
            ->where('balance_due', '>', 0)
            ->orderBy('balance_due', 'desc')
            ->limit(15)
            ->get();

        $this->totalOutstandingBalance = Reservation::whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN])
            ->where('balance_due', '>', 0)
            ->sum('balance_due');

        $this->recentPayments = HotelPayment::with(['reservation', 'receivedBy'])
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();
    }

    public function render()
    {
        return view('hotel::livewire.billing.billing-dashboard')->layout('layouts.app');
    }
}
