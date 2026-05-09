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
            ['Tomato', 'Vegetables', 'Kg', 5, 120, 'Fresh Farm Suppliers'],
            ['Chicken Breast', 'Meat', 'Kg', 10, 950, ''],
        ];
    }
}
