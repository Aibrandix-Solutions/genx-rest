<?php

namespace App\Exports;

use App\Models\Order;
use App\Services\RoomChargeOrderSettlement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RoomChargeOrdersReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected $orders, protected int $currencyId)
    {
    }

    public function headings(): array
    {
        return [
            __('modules.report.dateAndTime'),
            __('modules.order.orderNumber'),
            __('modules.report.room'),
            __('modules.report.guest'),
            __('modules.order.amount'),
            __('app.status'),
            __('modules.report.settledAmount'),
            __('modules.report.outstandingAmount'),
        ];
    }

    public function map($order): array
    {
        return [
            optional($order->date_time)->timezone(timezone())->format('d M Y, h:i A'),
            $order->show_formatted_order_number,
            $order->hotelReservation?->room?->room_number ?? '--',
            $order->hotelReservation?->guest?->full_name ?? '--',
            currency_format($order->total, $this->currencyId),
            match (RoomChargeOrderSettlement::status($order)) {
                'paid' => __('modules.report.settlementPaid'),
                'partially_paid' => __('modules.report.settlementPartiallyPaid'),
                default => __('modules.report.settlementOutstanding'),
            },
            currency_format(RoomChargeOrderSettlement::settledAmount($order), $this->currencyId),
            currency_format(RoomChargeOrderSettlement::outstandingAmount($order), $this->currencyId),
        ];
    }

    public function collection()
    {
        return $this->orders;
    }
}
