<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
use App\Models\Order;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class CustomerTable extends Component
{
    use LivewireAlert;
    use WithPagination, WithoutUrlPagination;

    public $search;
    public $customer;
    public $filterCustomer = 'all';
    public $perPage = 10;
    public $showEditCustomerModal = false;
    public $confirmDeleteCustomerModal = false;
    public $showCustomerOrderModal = false;
    public $showPaymentModal = false;
    public $showLedgerModal = false;
    public $showSalesModal = false;
    public $showRewardPointsModal = false;
    protected $listeners = ['refreshCustomers' => '$refresh', 'reloadPage' => '$refresh'];

    #[On('refreshCustomers')]
    public function refreshCustomers()
    {
        // Reset search to show all customers after import
        $this->search = '';
        $this->render();
    }

    public function showEditCustomer($id)
    {
        $this->customer = Customer::findOrFail($id);
        $this->showEditCustomerModal = true;
    }

    public function showDeleteCustomer($id)
    {
        $this->customer = Customer::findOrFail($id);
        $this->confirmDeleteCustomerModal = true;
    }

    public function showCustomerOrders($id)
    {
        $this->customer = Customer::findOrFail($id);
        $this->showCustomerOrderModal = true;
    }

    public function showCustomerPayment($id)
    {
        $this->customer = Customer::findOrFail($id);
        $this->showPaymentModal = true;
    }

    public function showCustomerRewardPoints($id)
    {
        $this->customer = Customer::findOrFail($id);
        $this->showRewardPointsModal = true;
    }

    public function showCustomerLedger($id)
    {
        $this->customer = Customer::findOrFail($id);
        $this->showLedgerModal = true;
    }

    /**
     * Close the ledger modal and open the order detail panel.
     * Called by CustomerLedger when an order reference is clicked.
     */
    #[On('viewOrderFromLedger')]
    public function viewOrderFromLedger($orderId)
    {
        $this->showLedgerModal = false;
        $this->dispatch('showOrderDetail', id: $orderId);
    }

    public function showCustomerSales($id)
    {
        $this->customer = Customer::findOrFail($id);
        $this->showSalesModal = true;
    }

    public function deleteCustomer($id, $deleteOrder = false)
    {
        if ($deleteOrder) {
            Order::where('customer_id', $id)->delete();
        }

        Customer::destroy($id);

        $this->customer = null;
        $this->confirmDeleteCustomerModal = false;
        $this->dispatch('refreshOrders');

        $this->alert('success', __('messages.customerDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    #[On('hideEditCustomer')]
    public function hideEditCustomer()
    {
        $this->showEditCustomerModal = false;
    }

    public function render()
    {
        $query = Customer::withCount('orders')
            ->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });

        // Apply outstanding balance filter
        if ($this->filterCustomer === 'with_outstanding') {
            $query->whereHas('orders', function($q) {
                $q->whereIn('status', ['payment_due', 'paid', 'billed']);
            });
        } elseif ($this->filterCustomer === 'no_outstanding') {
            $query->whereDoesntHave('orders', function($q) {
                $q->whereIn('status', ['payment_due', 'paid', 'billed']);
            });
        }

        $perPage = in_array((int)$this->perPage, [10, 20, 50, 100, 200]) ? (int)$this->perPage : 10;

        $restaurant = restaurant();

        if (in_array('Reward Point', restaurant_modules()) && $restaurant) {
            $query->with(['rewardBalance' => function ($q) use ($restaurant) {
                $q->where('restaurant_id', $restaurant->id);
            }]);
        }

        $customers = $query->orderBy('id', 'desc')->paginate($perPage);

        $rewardSettings = $restaurant
            ? \App\Models\RewardSetting::getForRestaurant($restaurant->id)
            : null;

        return view('livewire.customer.customer-table', [
            'customers' => $customers,
            'rewardSettings' => $rewardSettings
        ]);
    }
}
