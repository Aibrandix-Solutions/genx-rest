<?php

namespace Modules\Inventory\Livewire\PaymentAccount;

use Livewire\Component;
use Modules\Inventory\Entities\AccountTransaction;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashFlow extends Component
{
    public $startDate;
    public $endDate;
    public $groupBy = 'day'; // day, month
    public $branchId;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function render()
    {
        $query = AccountTransaction::query()
            ->whereDate('transaction_date', '>=', $this->startDate)
            ->whereDate('transaction_date', '<=', $this->endDate);

        if ($this->branchId) {
            $query->whereHas('account', function($q) {
                $q->where('branch_id', $this->branchId);
            });
        }

        if ($this->groupBy === 'month') {
            $dateFormat = '%Y-%m';
            $labelFormat = 'M Y';
        } else {
            $dateFormat = '%Y-%m-%d';
            $labelFormat = 'M d, Y';
        }

        // Group by Date and calculate sums
        $flows = $query->select(
                DB::raw("DATE_FORMAT(transaction_date, '$dateFormat') as date_group"),
                DB::raw("SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as total_in"),
                DB::raw("SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as total_out")
            )
            ->groupBy('date_group')
            ->orderBy('date_group', 'asc')
            ->get()
            ->map(function($flow) use ($labelFormat) {
                // Determine label from date_group
                // MySQL date_group will be string.
                $flow->label = \Carbon\Carbon::parse($flow->date_group)->format($labelFormat);
                $flow->net = $flow->total_in - $flow->total_out;
                return $flow;
            });

        $totalIn = $flows->sum('total_in');
        $totalOut = $flows->sum('total_out');
        $netChange = $totalIn - $totalOut;

        return view('inventory::livewire.payment-account.cash-flow', [
            'flows' => $flows,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'netChange' => $netChange,
            'branches' => Branch::all()
        ]);
    }
}
