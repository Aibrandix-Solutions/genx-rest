<?php

namespace Modules\Hotel\Livewire\Folio;

use Livewire\Component;
use Modules\Hotel\Services\FolioInvoiceData;

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
    public $format = 'thermal';
    public $width = 80;
    public $thermal = true;

    public function mount($reservationId)
    {
        abort_unless(user_can('view_hotel_billing'), 403);
        $this->reservationId = $reservationId;
        $this->viewMode = request()->query('viewMode', 'consolidated');
        $this->format = request()->query('format', 'thermal');
        if (! in_array($this->format, ['thermal', 'a4'], true)) {
            $this->format = 'thermal';
        }
        $this->width = (int) request()->query('width', 80);
        if (! in_array($this->width, [56, 58, 80, 112], true)) {
            $this->width = 80;
        }
        $this->thermal = request()->boolean('thermal', $this->format === 'thermal');
        if (! $this->thermal) {
            $this->format = 'a4';
        }
        $this->loadData();
    }

    public function loadData()
    {
        $data = FolioInvoiceData::build(
            (int) $this->reservationId,
            $this->viewMode,
            $this->format,
            $this->width,
        );

        $this->reservation = $data['reservation'];
        $this->folioSummary = $data['folioSummary'];
        $this->payments = $data['payments'];
        $this->totalCharges = $data['totalCharges'];
        $this->totalPayments = $data['totalPayments'];
        $this->balance = $data['balance'];
        $this->hotelName = $data['hotelName'];
        $this->hotelLogo = $data['hotelLogo'];
        $this->hotelAddress = $data['hotelAddress'];
        $this->hotelPhone = $data['hotelPhone'];
    }

    public function render()
    {
        $view = $this->format === 'a4'
            ? 'hotel::folio.invoice-print-a4'
            : 'hotel::folio.invoice-print';

        return view($view, FolioInvoiceData::build(
            (int) $this->reservationId,
            $this->viewMode,
            $this->format,
            $this->width,
        ));
    }
}
