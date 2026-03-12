<?php

namespace Modules\Inventory\Exports;

use Modules\Inventory\Entities\PurchaseReturn;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseReturnExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $search;
    protected $startDate;
    protected $endDate;
    protected $supplierId;
    protected $purchaseOrderId;
    protected $status;

    public function __construct($search = null, $startDate = null, $endDate = null, $supplierId = null, $purchaseOrderId = null, $status = null)
    {
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->supplierId = $supplierId;
        $this->purchaseOrderId = $purchaseOrderId;
        $this->status = $status;
    }

    public function headings(): array
    {
        return [
            __('app.date'),
            __('inventory::modules.purchaseReturn.return_number'),
            __('inventory::modules.purchaseReturn.supplier'),
            __('inventory::modules.purchaseReturn.po_ref'),
            __('app.status'),
            __('inventory::modules.purchaseReturn.amount'),
        ];
    }

    public function map($return): array
    {
        return [
            $return->return_date ? $return->return_date->format('Y-m-d') : '',
            $return->return_number,
            $return->supplier->name ?? '--',
            $return->purchaseOrder->po_number ?? '--',
            $return->status,
            $return->total_amount,
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
        return PurchaseReturn::query()
            ->with(['supplier', 'purchaseOrder'])
            ->where('branch_id', branch()->id)
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('return_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('supplier', function ($query) {
                            $query->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('purchaseOrder', function ($query) {
                            $query->where('po_number', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->supplierId, function ($query) {
                $query->where('supplier_id', $this->supplierId);
            })
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('return_date', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
            })
            ->when($this->purchaseOrderId, function ($query) {
                $query->where('purchase_order_id', $this->purchaseOrderId);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->latest()
            ->get();
    }
}
