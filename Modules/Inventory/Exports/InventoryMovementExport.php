<?php

namespace Modules\Inventory\Exports;

use Modules\Inventory\Entities\InventoryMovement;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryMovementExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $search;
    protected $startDate;
    protected $endDate;
    protected $type;
    protected $category;

    public function __construct($search = null, $startDate = null, $endDate = null, $type = null, $category = null)
    {
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
        $this->category = $category;
    }

    public function headings(): array
    {
        return [
            __('app.date'),
            __('inventory::modules.inventoryItem.name'),
            __('inventory::modules.inventoryMovement.type'),
            __('inventory::modules.inventoryMovement.quantity'),
            __('inventory::modules.inventoryMovement.source'),
            __('inventory::modules.inventoryMovement.destination'),
        ];
    }

    public function map($movement): array
    {
        $source = $movement->location->display_name ?? $movement->sourceBranch->name ?? '--';
        $destination = '--';

        if ($movement->transaction_type === 'transfer' && $movement->inventoryTransfer) {
            $source = $movement->inventoryTransfer->sourceLocation->display_name
                ?? $movement->inventoryTransfer->sourceBranch->name
                ?? $source;

            $destination = $movement->inventoryTransfer->destinationLocation->display_name
                ?? $movement->inventoryTransfer->destinationBranch->name
                ?? '--';
        }

        return [
            $movement->created_at->format('Y-m-d H:i'),
            $movement->item->name ?? '--',
            $movement->transaction_type,
            $movement->quantity,
            $source,
            $destination,
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
        return InventoryMovement::with([
                'item',
                'location',
                'inventoryTransfer.sourceLocation',
                'inventoryTransfer.destinationLocation',
                'inventoryTransfer.sourceBranch',
                'inventoryTransfer.destinationBranch',
                'sourceBranch',
                'transferBranch'
            ])
            ->where('branch_id', branch()->id)
            ->when($this->search, function($query) {
                $query->whereHas('item', function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->startDate && $this->endDate, function($query) {
                $query->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
            })
            ->when($this->type, function($query) {
                $query->where('transaction_type', $this->type);
            })
            ->when($this->category, function($query) {
                $query->whereHas('item', function($q) {
                    $q->where('inventory_item_category_id', $this->category);
                });
            })
            ->latest()
            ->get();
    }
}
