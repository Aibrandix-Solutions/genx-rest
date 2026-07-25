<?php

namespace Modules\Hotel\Livewire\Folio;

use Livewire\Component;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;
use Modules\Hotel\Entities\HotelPayment;
use Modules\Hotel\Entities\HotelSetting;
use Modules\Hotel\Services\FolioChargePresenter;

class InvoiceV2 extends Component
{
    public $reservationId;
    public $reservation;
    public $charges;
    public $folioSummary = [];
    public $payments;
    public $viewMode = 'single'; // single | group
    public $totalCharges = 0;
    public $totalPayments = 0;
    public $balance = 0;
    public $hotelName    = '';
    public $hotelLogo    = '';
    public $hotelAddress = '';
    public $hotelPhone   = '';

    public function mount($reservationId)
    {
        abort_unless(user_can('view_hotel_billing'), 403);
        $this->reservationId = $reservationId;
        $this->viewMode = request()->query('viewMode', 'consolidated');
        $this->loadData();
    }

    public function loadData()
    {
        $this->reservation = Reservation::with(['guest', 'room', 'room.roomType'])
            ->findOrFail($this->reservationId);

        // Load hotel name from settings
        $settings = HotelSetting::first();
        $this->hotelName    = $settings->hotel_name ?? restaurant()->name ?? '';
        $this->hotelLogo    = $settings->hotel_logo ?? '';
        $this->hotelAddress = restaurant()->address ?? '';
        $this->hotelPhone   = restaurant()->phone ?? '';
        
        if ($this->reservation->group_booking_id) {
            $groupReservations = Reservation::where('group_booking_id', $this->reservation->group_booking_id)->get();
            $resIds = $groupReservations->pluck('id');

            $this->charges = RoomCharge::with(['order', 'reservation.room'])
                ->whereIn('reservation_id', $resIds)
                ->orderBy('charge_date', 'asc')
                ->get();

            $this->payments = HotelPayment::whereIn('reservation_id', $resIds)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($this->viewMode === 'roomwise') {
                $this->folioSummary = FolioChargePresenter::summarize($this->reservation, $this->charges, '');
            } else {
                $this->folioSummary = FolioChargePresenter::summarize($this->reservation, $this->charges, 'consolidated');
            }
        } else {
            $this->charges = RoomCharge::with('order')
                ->where('reservation_id', $this->reservationId)
                ->orderBy('charge_date', 'asc')
                ->get();

            $this->payments = HotelPayment::where('reservation_id', $this->reservationId)
                ->orderBy('created_at', 'asc')
                ->get();

            $this->folioSummary = FolioChargePresenter::summarize($this->reservation, $this->charges);
        }

        $this->totalCharges = $this->folioSummary['subtotal'];

        $totalPaid = $this->payments->where('payment_type', '!=', HotelPayment::TYPE_REFUND)->sum('amount');
        $totalRefunds = $this->payments->where('payment_type', HotelPayment::TYPE_REFUND)->sum('amount');
        $this->totalPayments = $totalPaid - $totalRefunds;

        $this->balance = $this->totalCharges - $this->totalPayments;
    }

    public function render()
    {
        return view('hotel::livewire.folio.invoice-v2')
            ->layout('layouts.empty');
    }
}
