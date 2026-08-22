<?php

namespace Modules\Inventory\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryItemImportTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'name',
            'item_code',
            'category_name',
            'unit_name',
            'threshold_quantity',
            'unit_purchase_price',
            'preferred_supplier_name',
        ];
    }

    public function array(): array
    {
        return [
            // item_code is optional; leave blank to auto-generate (e.g. INV0001)
            ['Tomato', '', 'Vegetables', 'Kg', 5, 120, 'Fresh Farm Suppliers'],
            ['Chicken Breast', 'INV1001', 'Meat', 'Kg', 10, 950, ''],
        ];
    }
}
