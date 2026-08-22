<?php

namespace Modules\Hrm\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PayrollImportTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'staff_code',
            'additional_pay',
            'advance',
            'epf',
            'etf',
            'time_deduction',
            'credit_purchase',
            'other_deduction',
            'payment_date',
            'note',
        ];
    }

    public function array(): array
    {
        return [
            [
                'EMP001',
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                now()->toDateString(),
                'Optional note',
            ],
        ];
    }
}
