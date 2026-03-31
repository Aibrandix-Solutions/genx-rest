<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;

class CustomerOrders extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $customer;

    public function render()
    {
        // Exclude in-progress orders (kot/draft) that haven't been billed yet.
        // These can show 0 items if no items have been committed to order_items yet.
        $orders = $this->customer->orders()
            ->whereIn('status', ['billed', 'paid', 'payment_due', 'canceled'])
            ->with('items')
            ->paginate(10);

        $totalOrderCount = $this->customer->orders()
            ->whereIn('status', ['billed', 'paid', 'payment_due', 'canceled'])
            ->count();

        $totalAmount = $this->customer->orders()
            ->whereIn('status', ['billed', 'paid', 'payment_due'])
            ->sum('total');

        return view('livewire.customer.customer-orders', [
            'orders' => $orders,
            'totalOrderCount' => $totalOrderCount,
            'totalAmount' => $totalAmount,
        ]);
    }
}
