<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{

    use Exportable;

    protected string $filterCustomer;

    public function __construct(string $filterCustomer = 'all')
    {
        $this->filterCustomer = $filterCustomer;
    }
    
    public function headings(): array
    {
        return [
            __('modules.customer.name'),
            __('modules.customer.phone'),
            __('modules.customer.email'),
            __('modules.order.totalOrder'),
            __('modules.customer.outstanding_balance'),
            __('modules.customer.totalAmountReceived'),
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->name,
            $customer->phone,
            $customer->email,
            $customer->orders->count(),
            currency_format($customer->outstanding_balance, restaurant()->currency_id),
            currency_format($customer->orders->sum('total'), restaurant()->currency_id),
        ];
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return $defaultStyle
            ->getFont()
            ->setName('Arial');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true, 'name' => 'Arial'], 'fill'  => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'f5f5f5'),
            ]],
        ];
    }

    public function collection()
    {
        $query = Customer::with('orders');

        if ($this->filterCustomer === 'with_outstanding') {
            $query->whereHas('orders', function($q) {
                $q->where('status', 'payment_due')
                  ->whereRaw('total > amount_paid');
            });
        } elseif ($this->filterCustomer === 'no_outstanding') {
            $query->whereDoesntHave('orders', function($q) {
                $q->where('status', 'payment_due')
                  ->whereRaw('total > amount_paid');
            });
        }

        return $query->get();
    }

}
