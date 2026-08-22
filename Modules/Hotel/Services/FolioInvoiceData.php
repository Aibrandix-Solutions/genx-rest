<?php

namespace Modules\Hotel\Services;

use Modules\Hotel\Entities\HotelPayment;
use Modules\Hotel\Entities\HotelSetting;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;

class FolioInvoiceData
{
    /**
     * @return array<string, mixed>
     */
    public static function build(int $reservationId, string $viewMode = 'consolidated', string $format = 'thermal', int $width = 80): array
    {
        $reservation = Reservation::with(['guest', 'room', 'room.roomType'])
            ->findOrFail($reservationId);

        $branchId = $reservation->branch_id;

        $settings = HotelSetting::first();
        $hotelName = $settings->hotel_name ?? restaurant()->name ?? '';
        $hotelLogo = $settings->hotel_logo ?? '';
        $hotelAddress = restaurant()->address ?? '';
        $hotelPhone = restaurant()->phone ?? '';

        if ($reservation->group_booking_id) {
            $groupReservations = Reservation::where('group_booking_id', $reservation->group_booking_id)->get();
            $resIds = $groupReservations->pluck('id');

            $charges = RoomCharge::with(['order', 'reservation.room'])
                ->where('branch_id', $branchId)
                ->whereIn('reservation_id', $resIds)
                ->orderBy('charge_date', 'asc')
                ->get();

            $payments = HotelPayment::whereIn('reservation_id', $resIds)
                ->orderBy('created_at', 'asc')
                ->get();

            $folioSummary = $viewMode === 'roomwise'
                ? FolioChargePresenter::summarize($reservation, $charges, '')
                : FolioChargePresenter::summarize($reservation, $charges, 'consolidated');
        } else {
            $charges = RoomCharge::with('order')
                ->where('branch_id', $branchId)
                ->where('reservation_id', $reservationId)
                ->orderBy('charge_date', 'asc')
                ->get();

            $payments = HotelPayment::where('reservation_id', $reservationId)
                ->orderBy('created_at', 'asc')
                ->get();

            $folioSummary = FolioChargePresenter::summarize($reservation, $charges);
        }

        $totalCharges = $folioSummary['subtotal'];
        $totalPaid = $payments->where('payment_type', '!=', HotelPayment::TYPE_REFUND)->sum('amount');
        $totalRefunds = $payments->where('payment_type', HotelPayment::TYPE_REFUND)->sum('amount');
        $totalPayments = $totalPaid - $totalRefunds;
        $balance = $totalCharges - $totalPayments;

        $thermal = $format === 'thermal';

        return compact(
            'reservation',
            'folioSummary',
            'payments',
            'viewMode',
            'totalCharges',
            'totalPayments',
            'balance',
            'hotelName',
            'hotelLogo',
            'hotelAddress',
            'hotelPhone',
            'format',
            'width',
            'thermal',
        );
    }
}
