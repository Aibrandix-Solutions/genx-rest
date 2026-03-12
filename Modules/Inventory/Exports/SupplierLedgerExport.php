<?php

namespace Modules\Inventory\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SupplierLedgerExport implements WithMapping, FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $ledgerEntries;

    public function __construct($ledgerEntries)
    {
        $this->ledgerEntries = $ledgerEntries;
    }

    public function headings(): array
    {
        return [
            __('app.date'),
            __('app.description'),
            __('inventory::modules.ledger.debit'),
            __('inventory::modules.ledger.credit'),
            __('inventory::modules.ledger.balance'),
        ];
    }

    public function map($entry): array
    {
        return [
            Carbon::parse($entry['date'])->format('Y-m-d'),
            $entry['description'],
            $entry['debit'] > 0 ? $entry['debit'] : '',
            $entry['credit'] > 0 ? $entry['credit'] : '',
            $entry['balance'],
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

    public function array(): array
    {
        return $this->ledgerEntries instanceof \Illuminate\Support\Collection 
            ? $this->ledgerEntries->toArray() 
            : $this->ledgerEntries;
    }
}
