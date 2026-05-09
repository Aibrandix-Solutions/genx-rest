<?php

namespace Modules\Inventory\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchaseItemsImportTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'item_name',
            'quantity',
            'unit_price',
            'discount',
            'discount_type',
        ];
    }

    public function array(): array
    {
        return [
            ['Sample Item A', 2, 100.00, 0, 'fixed'],
            ['Sample Item B', 1, 250.00, 10, 'percentage'],
        ];
    }
}
