<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CustomerLedger extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $customer;
    public $startDate;
    public $endDate;
    public $search = '';

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

    public function viewOrder($orderId)
    {
        // Tell CustomerTable to close the ledger modal first, then open the order detail.
        // Dispatching showOrderDetail directly here would leave the ledger modal open (2 modals).
        $this->dispatch('viewOrderFromLedger', orderId: $orderId)->to('customer.customer-table');
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

        // Sort ascending (oldest first) so running balance accumulates correctly
        $transactions = $transactions->sortBy('date')->values();

        // Calculate totals before balance calculation
        $totalDebit = $transactions->sum('debit');
        $totalCredit = $transactions->sum('credit');
        $openingBalance = $this->getOpeningBalance();
        $closingBalance = $openingBalance + $totalDebit - $totalCredit;

        // Running balance starts from opening balance, grows oldest → newest
        $balance = $openingBalance;
        $transactions = $transactions->map(function ($transaction) use (&$balance) {
            $balance = $balance + $transaction['debit'] - $transaction['credit'];
            $transaction['balance'] = $balance;
            return $transaction;
        });

        // Reverse to show newest first in the UI
        $transactions = $transactions->reverse()->values();

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

        $perPage = 20;
        $currentPage = $this->getPage();
        $items = $data['transactions']->forPage($currentPage, $perPage);
        $paginatedTransactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $data['transactions']->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );
        $paginatedTransactions->withQueryString();

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
        $cutoff = Carbon::parse($this->startDate)->startOfDay();

        // Only consider orders that contribute to balance (exclude cancelled)
        $beforeOrders = $this->customer->orders()
            ->where('date_time', '<', $cutoff)
            ->whereIn('status', ['paid', 'payment_due', 'billed'])
            ->pluck('id');

        if ($beforeOrders->isEmpty()) {
            return 0.0;
        }

        $totalOrders = $this->customer->orders()
            ->where('date_time', '<', $cutoff)
            ->whereIn('status', ['paid', 'payment_due', 'billed'])
            ->sum('total');

        $totalPayments = Payment::whereIn('order_id', $beforeOrders)
            ->where('payment_method', '!=', 'due')
            ->sum('amount');

        return (float)$totalOrders - (float)$totalPayments;
    }
}

