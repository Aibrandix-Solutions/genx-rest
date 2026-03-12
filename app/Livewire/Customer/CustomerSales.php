<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\Customer;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerSales extends Component
{
    public $customer;
    public $startDate;
    public $endDate;
    public $period = '30'; // days

    public function mount($customer)
    {
        $this->customer = $customer;
        $this->updatedPeriod();
    }

    public function updatedPeriod()
    {
        $days = (int)$this->period;
        $this->startDate = Carbon::now()->subDays($days)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        $orders = $this->customer->orders()
            ->whereBetween('date_time', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ])
            ->whereIn('status', ['paid', 'payment_due'])
            ->with(['items.menuItem', 'payments'])
            ->get();

        // Calculate statistics
        $totalSales = $orders->sum('total');
        $totalOrders = $orders->count();
        $averageOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        // Use actual payment records (payments already eager-loaded above)
        $totalPaid = $orders->sum(function ($order) {
            return $order->payments->where('payment_method', '!=', 'due')->sum('amount');
        });
        $outstandingBalance = $orders->whereIn('status', ['payment_due', 'paid', 'billed'])->sum(function ($order) {
            $paid = $order->payments->where('payment_method', '!=', 'due')->sum('amount');
            return max(0, (float)$order->total - (float)$paid);
        });

        // Last order
        $lastOrder = $orders->sortByDesc('date_time')->first();

        // Top items
        $topItems = $orders->flatMap(function ($order) {
            return $order->items;
        })
        ->groupBy('menu_item_id')
        ->map(function ($items, $menuItemId) {
            $firstItem = $items->first();
            return [
                'name' => $firstItem->menuItem->name ?? 'N/A',
                'quantity' => $items->sum('quantity'),
                'amount' => $items->sum(function ($item) {
                    return (float)$item->price * (float)$item->quantity;
                }),
            ];
        })
        ->sortByDesc('amount')
        ->take(10)
        ->values();

        // Sales by day (for chart)
        $salesByDay = $orders->groupBy(function ($order) {
            return $order->date_time->format('Y-m-d');
        })->map(function ($dayOrders) {
            return [
                'date' => $dayOrders->first()->date_time->format('M d'),
                'count' => $dayOrders->count(),
                'amount' => $dayOrders->sum('total'),
            ];
        })->sortBy(function ($data, $key) {
            return $key;
        })->values();

        // Sales by status
        $salesByStatus = [
            'paid' => $orders->where('status', 'paid')->sum('total'),
            'payment_due' => $orders->where('status', 'payment_due')->sum('total'),
        ];

        // Payment methods breakdown
        $paymentMethods = $orders->flatMap(function ($order) {
            return $order->payments->where('payment_method', '!=', 'due');
        })
        ->groupBy('payment_method')
        ->map(function ($payments) {
            return $payments->sum('amount');
        });

        return view('livewire.customer.customer-sales', [
            'totalSales' => $totalSales,
            'totalOrders' => $totalOrders,
            'averageOrderValue' => $averageOrderValue,
            'totalPaid' => $totalPaid,
            'outstandingBalance' => $outstandingBalance,
            'lastOrder' => $lastOrder,
            'topItems' => $topItems,
            'salesByDay' => $salesByDay,
            'salesByStatus' => $salesByStatus,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}

