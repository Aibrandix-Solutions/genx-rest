<?php

namespace Modules\Inventory\Exports;

use Modules\Inventory\Entities\InventoryItem;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryItemExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{

    use Exportable;

    public function headings(): array
    {
        return [
            __('inventory::modules.inventoryItem.name'),
            __('inventory::modules.inventoryItem.category'),
            __('inventory::modules.inventoryItem.unit'),
            __('inventory::modules.inventoryItem.purchasePrice'),
            __('inventory::modules.inventoryItem.thresholdQuantity'),
            __('inventory::modules.inventoryItem.preferredSupplier'),
        ];
    }

    public function map($item): array
    {
        return [
            $item->name,
            $item->category->name ?? '--',
            $item->unit->name ?? '--',
            currency_format($item->unit_purchase_price, restaurant()->currency_id),
            $item->threshold_quantity,
            $item->supplier->name ?? '--',
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
            1    => ['font' => ['bold' => true, 'name' => 'Arial'], 'fill'  => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'f5f5f5'),
            ]],
        ];
    }

    public function collection()
    {
        return InventoryItem::with(['category', 'unit', 'supplier'])->get();
    }

}
