<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Livewire\WithPagination;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CustomerLedger extends Component
{
    use WithPagination;

    public $customer;
    public $startDate;
    public $endDate;
    public $search = '';

    protected $queryString = ['startDate', 'endDate', 'search'];

    public function mount($customer)
    {
        $this->customer = $customer;
        $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function downloadPdf()
    {
        $data = $this->getTransactionData();
        
        $pdf = Pdf::loadView('livewire.customer.customer-ledger-pdf', $data);
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'customer-ledger-' . $this->customer->id . '-' . Carbon::now()->format('Y-m-d') . '.pdf';
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    private function getTransactionData()
    {
        $transactions = collect();

        // Get orders
        $orders = $this->customer->orders()
            ->whereBetween('date_time', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ])
            ->when($this->search, function ($query) {
                $query->where('order_number', 'like', '%' . $this->search . '%')
                      ->orWhere('formatted_order_number', 'like', '%' . $this->search . '%');
            })
            ->with(['payments', 'items'])
            ->orderBy('date_time', 'desc')
            ->get();

        // Convert orders to transactions
        foreach ($orders as $order) {
            $transactions->push([
                'type' => 'order',
                'date' => $order->date_time,
                'reference' => $order->formatted_order_number ?? '#' . $order->order_number,
                'description' => __('modules.customer.order') . ' - ' . ($order->items->count() . ' ' . __('app.items')),
                'debit' => (float)$order->total,
                'credit' => 0,
                'balance' => 0, // Will calculate after
                'status' => $order->status,
                'id' => $order->id,
                'data' => $order,
            ]);

            // Add payments for this order
            foreach ($order->payments()->where('payment_method', '!=', 'due')->get() as $payment) {
                $transactions->push([
                    'type' => 'payment',
                    'date' => $payment->created_at,
                    'reference' => __('modules.customer.payment'),
                    'description' => __('modules.customer.payment') . ' - ' . strtoupper($payment->payment_method),
                    'debit' => 0,
                    'credit' => (float)$payment->amount,
                    'balance' => 0,
                    'status' => 'paid',
                    'id' => $payment->id,
                    'data' => $payment,
                ]);
            }
        }

        // Sort by date
        $transactions = $transactions->sortByDesc('date')->values();

        // Calculate running balance
        $balance = 0;
        $transactions = $transactions->map(function ($transaction) use (&$balance) {
            $balance = $balance + $transaction['debit'] - $transaction['credit'];
            $transaction['balance'] = $balance;
            return $transaction;
        });

        // Paginate
        $perPage = 20;
        $currentPage = $this->page ?? 1;
        $items = $transactions->forPage($currentPage, $perPage);
        $paginatedTransactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $transactions->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Calculate totals
        $totalDebit = $transactions->sum('debit');
        $totalCredit = $transactions->sum('credit');
        $openingBalance = $this->getOpeningBalance();
        $closingBalance = $openingBalance + $totalDebit - $totalCredit;

        return [
            'transactions' => $transactions,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'customer' => $this->customer,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ];
    }

    public function render()
    {
        $data = $this->getTransactionData();
        
        // Paginate for display
        $perPage = 20;
        $currentPage = $this->page ?? 1;
        $items = $data['transactions']->forPage($currentPage, $perPage);
        $paginatedTransactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $data['transactions']->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.customer.customer-ledger', [
            'transactions' => $paginatedTransactions,
            'totalDebit' => $data['totalDebit'],
            'totalCredit' => $data['totalCredit'],
            'openingBalance' => $data['openingBalance'],
            'closingBalance' => $data['closingBalance'],
        ]);
    }

    private function getOpeningBalance()
    {
        // Get total outstanding before start date
        $beforeOrders = $this->customer->orders()
            ->where('date_time', '<', Carbon::parse($this->startDate)->startOfDay())
            ->where('status', 'payment_due')
            ->get()
            ->sum(function ($order) {
                $paid = (float)$order->payments()
                    ->where('payment_method', '!=', 'due')
                    ->sum('amount');
                return max(0, (float)$order->total - $paid);
            });

        // Get total orders before start date
        $totalOrders = $this->customer->orders()
            ->where('date_time', '<', Carbon::parse($this->startDate)->startOfDay())
            ->sum('total');

        // Get total payments before start date
        $totalPayments = Payment::whereHas('order', function ($query) {
                $query->where('customer_id', $this->customer->id)
                      ->where('date_time', '<', Carbon::parse($this->startDate)->startOfDay());
            })
            ->where('payment_method', '!=', 'due')
            ->sum('amount');

        return $totalOrders - $totalPayments;
    }
}

