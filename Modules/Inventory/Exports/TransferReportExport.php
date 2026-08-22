<?php

namespace Modules\Inventory\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Inventory\Services\TransferReportService;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransferReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    public function __construct(
        private readonly Collection $rows,
        private readonly string $reportType,
        private readonly TransferReportService $reportService,
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return match ($this->reportType) {
            'daily' => [
                __('inventory::modules.reports.transfers.table.date'),
                __('inventory::modules.reports.transfers.table.transfer_number'),
                __('inventory::modules.reports.transfers.table.from_location'),
                __('inventory::modules.reports.transfers.table.to_location'),
                __('inventory::modules.reports.transfers.table.items_count'),
                __('inventory::modules.reports.transfers.table.quantity'),
                __('inventory::modules.reports.transfers.table.total_cost'),
            ],
            'monthly' => [
                __('inventory::modules.reports.transfers.table.month'),
                __('inventory::modules.reports.transfers.table.transfers_count'),
                __('inventory::modules.reports.transfers.table.lines_count'),
                __('inventory::modules.reports.transfers.table.quantity'),
                __('inventory::modules.reports.transfers.table.total_cost'),
            ],
            default => [
                __('inventory::modules.reports.transfers.table.product_code'),
                __('inventory::modules.reports.transfers.table.product_name'),
                __('inventory::modules.reports.transfers.table.category'),
                __('inventory::modules.reports.transfers.table.from_location'),
                __('inventory::modules.reports.transfers.table.to_location'),
                __('inventory::modules.reports.transfers.table.unit'),
                __('inventory::modules.reports.transfers.table.quantity'),
                __('inventory::modules.reports.transfers.table.unit_cost'),
                __('inventory::modules.reports.transfers.table.total_cost'),
                __('inventory::modules.reports.transfers.table.transfer_number'),
                __('inventory::modules.reports.transfers.table.date'),
            ],
        };
    }

    public function map($row): array
    {
        return match ($this->reportType) {
            'daily' => [
                $row->date_label,
                $row->transfer_number,
                $row->from_location,
                $row->to_location,
                $row->items_count,
                $this->reportService->formatQuantity((float) $row->total_quantity),
                number_format((float) $row->total_cost, 2, '.', ''),
            ],
            'monthly' => [
                $row->month_label,
                $row->transfers_count,
                $row->lines_count,
                $this->reportService->formatQuantity((float) $row->total_quantity),
                number_format((float) $row->total_cost, 2, '.', ''),
            ],
            default => [
                $row->product_code,
                $row->product_name,
                $row->category,
                $row->from_location,
                $row->to_location,
                $row->unit,
                $this->reportService->formatQuantity((float) $row->quantity),
                number_format((float) $row->unit_cost, 2, '.', ''),
                number_format((float) $row->total_cost, 2, '.', ''),
                $row->transfer_number,
                $row->date_label,
            ],
        };
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
