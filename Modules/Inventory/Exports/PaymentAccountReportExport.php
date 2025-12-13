<?php

namespace Modules\Inventory\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Inventory\Entities\AccountTransaction;
use Illuminate\Http\Request;

class PaymentAccountReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        return AccountTransaction::query()
            ->with('account', 'reference')
            ->when($this->request->accountId, function ($q) {
                $q->where('payment_account_id', $this->request->accountId);
            })
            ->when($this->request->startDate, function ($q) {
                $q->whereDate('transaction_date', '>=', $this->request->startDate);
            })
            ->when($this->request->endDate, function ($q) {
                $q->whereDate('transaction_date', '<=', $this->request->endDate);
            })
            ->when($this->request->type && $this->request->type !== 'all', function ($q) {
                $q->where('type', $this->request->type);
            })
            ->when($this->request->search, function ($q) {
                $q->where('description', 'like', '%' . $this->request->search . '%');
            })
            ->latest('transaction_date')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Account',
            'Description',
            'Reference Type',
            'Reference ID',
            'Type',
            'Amount',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->transaction_date->format('Y-m-d H:i:s'),
            $transaction->account->name,
            $transaction->description,
            class_basename($transaction->reference_type),
            $transaction->reference_id,
            ucfirst($transaction->type),
            number_format($transaction->amount, 2),
        ];
    }
}

