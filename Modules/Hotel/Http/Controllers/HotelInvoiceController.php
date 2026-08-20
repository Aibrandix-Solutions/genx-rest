<?php

namespace Modules\Hotel\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Hotel\Services\FolioInvoiceData;

class HotelInvoiceController extends Controller
{
    public function print(int $reservationId)
    {
        abort_unless(user_can('view_hotel_billing'), 403);

        $viewMode = request()->query('viewMode', 'consolidated');
        $format = request()->query('format', 'thermal');
        if (! in_array($format, ['thermal', 'a4'], true)) {
            $format = 'thermal';
        }

        if (request()->has('thermal') && ! request()->boolean('thermal')) {
            $format = 'a4';
        }

        $width = (int) request()->query('width', 80);
        if (! in_array($width, [56, 58, 80, 112], true)) {
            $width = 80;
        }

        $view = $format === 'a4'
            ? 'hotel::folio.invoice-print-a4'
            : 'hotel::folio.invoice-print';

        return view($view, FolioInvoiceData::build(
            $reservationId,
            $viewMode,
            $format,
            $width,
        ));
    }
}
