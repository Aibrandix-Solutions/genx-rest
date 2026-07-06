<?php

namespace Modules\Hotel\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RestaurantDuesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        protected string $mode,
        protected $rows,
        protected int $currencyId,
    ) {
    }

    public function headings(): array
    {
        if ($this->mode === 'ledger') {
            return [
                __('app.date'),
                __('app.description'),
                __('hotel::modules.restaurantDues.debit'),
                __('hotel::modules.restaurantDues.credit'),
                __('hotel::modules.restaurantDues.balance'),
            ];
        }

        return [
            __('hotel::modules.restaurantDues.roomNumber'),
            __('hotel::modules.restaurantDues.guest'),
            __('hotel::modules.restaurantDues.restaurantBranch'),
            __('hotel::modules.restaurantDues.orders'),
            __('hotel::modules.restaurantDues.totalCharges'),
            __('hotel::modules.restaurantDues.totalPaid'),
            __('hotel::modules.restaurantDues.outstandingAmount'),
            __('hotel::modules.restaurantDues.settlementStatus'),
        ];
    }

    public function map($row): array
    {
        if ($this->mode === 'ledger') {
            return [
                \Carbon\Carbon::parse($row['date'])->format('d M Y'),
                $row['description'],
                currency_format($row['debit'], $this->currencyId),
                currency_format($row['credit'], $this->currencyId),
                currency_format($row['balance'], $this->currencyId),
            ];
        }

        return [
            $row['room_number'],
            $row['guest_name'],
            $row['restaurant_branch_name'],
            $row['orders_count'],
            currency_format($row['total_charges'], $this->currencyId),
            currency_format($row['total_paid'], $this->currencyId),
            currency_format($row['outstanding_amount'], $this->currencyId),
            $row['status'],
        ];
    }

    public function collection()
    {
        return collect($this->rows);
    }
}
