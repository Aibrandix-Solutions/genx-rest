<?php

namespace Modules\Inventory\Exports;

use Modules\Inventory\Entities\SupplierPayment;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplierPaymentExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $supplierId;
    protected $search;
    protected $startDate;
    protected $endDate;

    public function __construct($supplierId, $search = null, $startDate = null, $endDate = null)
    {
        $this->supplierId = $supplierId;
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function headings(): array
    {
        return [
            __('app.date'),
            __('inventory::modules.payments.method'),
            __('inventory::modules.payments.account'),
            __('app.note'),
            __('app.amount'),
            __('inventory::modules.payments.added_by'),
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->paid_on->format('Y-m-d H:i'),
            ucfirst($payment->payment_method),
            $payment->account->name ?? '--',
            $payment->note,
            $payment->amount,
            $payment->addedBy->name ?? '--',
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
        return SupplierPayment::query()
            ->with(['account', 'addedBy'])
            ->where('supplier_id', $this->supplierId)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('payment_method', 'like', '%' . $this->search . '%')
                        ->orWhere('note', 'like', '%' . $this->search . '%')
                        ->orWhereHas('account', function($q) {
                            $q->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('paid_on', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
            })
            ->latest('paid_on')
            ->get();
    }
}
