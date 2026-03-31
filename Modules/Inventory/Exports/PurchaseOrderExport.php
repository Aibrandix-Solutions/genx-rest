<?php

namespace Modules\Inventory\Exports;

use Modules\Inventory\Entities\PurchaseOrder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseOrderExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $search;
    protected $startDate;
    protected $endDate;
    protected $supplierId;
    protected $status;

    public function __construct($search = null, $startDate = null, $endDate = null, $supplierId = null, $status = null)
    {
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->supplierId = $supplierId;
        $this->status = $status;
    }

    public function headings(): array
    {
        return [
            __('app.date'),
            __('inventory::modules.purchaseOrder.poNumber'),
            __('inventory::modules.purchaseOrder.supplier'),
            __('app.status'),
            __('inventory::modules.purchaseOrder.totalCost'),
        ];
    }

    public function map($order): array
    {
        return [
            $order->order_date ? $order->order_date->format('Y-m-d') : '',
            $order->po_number,
            $order->supplier->name ?? '--',
            $order->status,
            currency_format($order->total_amount, restaurant()->currency_id),
        ];
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return $defaultStyle->getFont()->setName('Arial');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'name' => 'Arial'], 'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'f5f5f5'),
            ]],
        ];
    }

    public function collection()
    {
        return PurchaseOrder::with(['supplier'])
            ->where('branch_id', branch()->id)
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('po_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('supplier', function ($query) {
                            $query->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->supplierId, function ($query) {
                $query->where('supplier_id', $this->supplierId);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->startDate && $this->endDate, function($query) {
                $query->whereBetween('order_date', [$this->startDate, $this->endDate]);
            })
            ->latest()
            ->get();
    }
}
