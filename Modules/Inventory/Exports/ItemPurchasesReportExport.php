<?php

namespace Modules\Inventory\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Inventory\Services\ItemPurchasesReportService;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemPurchasesReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    public function __construct(
        private readonly Collection $rows,
        private readonly ItemPurchasesReportService $reportService,
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            __('inventory::modules.reports.item_purchases.table.item'),
            __('inventory::modules.reports.item_purchases.table.item_code'),
            __('inventory::modules.reports.item_purchases.table.unit'),
            __('inventory::modules.reports.item_purchases.table.location'),
            __('inventory::modules.reports.item_purchases.table.branch'),
            __('inventory::modules.reports.item_purchases.table.category'),
            __('inventory::modules.reports.item_purchases.table.purchased_quantity'),
            __('inventory::modules.reports.item_purchases.table.total_purchase_price'),
        ];
    }

    public function map($row): array
    {
        return [
            $row->item_name,
            $row->item_code ?: '—',
            $row->unit,
            $row->location,
            $row->branch,
            $row->category,
            $this->reportService->formatQuantity($row->purchased_quantity),
            number_format((float) $row->total_purchase_price, 2, '.', ''),
        ];
    }

    public function defaultStyles(Style $defaultStyle): Style
    {
        return $defaultStyle->getFont()->setName('Arial');
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'name' => 'Arial'],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'f5f5f5'],
                ],
            ],
        ];
    }
}
