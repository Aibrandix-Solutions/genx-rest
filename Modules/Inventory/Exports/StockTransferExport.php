<?php

namespace Modules\Inventory\Exports;

use Modules\Inventory\Entities\InventoryTransfer;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockTransferExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $search;
    protected $startDate;
    protected $endDate;
    protected $filterType;
    protected $status;

    public function __construct($search = null, $startDate = null, $endDate = null, $filterType = 'all', $status = 'all')
    {
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->filterType = $filterType;
        $this->status = $status;
    }

    public function headings(): array
    {
        return [
            __('app.date'),
            __('inventory::modules.transfers.transfer_number'),
            __('inventory::modules.transfers.source_branch'),
            __('inventory::modules.transfers.destination_branch'),
            __('app.status'),
            __('app.createdBy'),
        ];
    }

    public function map($transfer): array
    {
        return [
            $transfer->created_at->format('Y-m-d H:i'),
            $transfer->transfer_number,
            $transfer->sourceBranch->name ?? '--',
            $transfer->destinationBranch->name ?? '--',
            $transfer->status,
            $transfer->createdBy->name ?? '--',
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
        $query = InventoryTransfer::with(['sourceBranch', 'destinationBranch', 'createdBy'])
            ->where('restaurant_id', restaurant()->id);

        if ($this->filterType === 'outgoing') {
            $query->where('source_branch_id', branch()->id);
        } elseif ($this->filterType === 'incoming') {
            $query->where('destination_branch_id', branch()->id);
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->search) {
             $query->where(function($q) {
                $q->where('transfer_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('sourceBranch', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('destinationBranch', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
        }

        return $query->latest()->get();
    }
}
