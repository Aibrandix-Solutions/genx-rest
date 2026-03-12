<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerPayment extends Component
{
    use LivewireAlert;

    public $customer;
    public $outstandingOrders = [];
    public $selectedOrders = [];
    public $paymentAmount = 0;
    public $paymentMethod = 'cash';
    public $notes = '';
    public $totalOutstanding = 0;

    public function mount($customer)
    {
        $this->customer = $customer;
        $this->loadOutstandingOrders();
    }

    public function loadOutstandingOrders()
    {
        // Load all orders that are not fully paid (including payment_due and partially paid)
        $this->outstandingOrders = $this->customer->orders()
            ->whereIn('status', ['payment_due', 'paid', 'billed'])
            ->with(['payments', 'items'])
            ->orderBy('date_time', 'asc')
            ->get()
            ->map(function ($order) {
                $paidAmount = (float)$order->payments()
                    ->where('payment_method', '!=', 'due')
                    ->sum('amount');
                
                $outstanding = max(0, (float)$order->total - $paidAmount);
                
                // Only include orders with outstanding balance
                if ($outstanding <= 0) {
                    return null;
                }
                
                return [
                    'id' => $order->id,
                    'order_number' => $order->formatted_order_number ?? '#' . $order->order_number,
                    'date' => $order->date_time->format('M d, Y h:i A'),
                    'total' => (float)$order->total,
                    'paid' => $paidAmount,
                    'outstanding' => $outstanding,
                    'items_count' => $order->items->count(),
                ];
            })
            ->filter() // Remove null values
            ->values()
            ->toArray();

        $this->totalOutstanding = collect($this->outstandingOrders)->sum('outstanding');
    }

    public function updatedSelectedOrders()
    {
        $this->calculatePaymentAmount();
    }

    public function selectAll()
    {
        $this->selectedOrders = collect($this->outstandingOrders)
            ->pluck('id')
            ->toArray();
        $this->calculatePaymentAmount();
    }

    public function deselectAll()
    {
        $this->selectedOrders = [];
        $this->paymentAmount = 0;
    }

    public function calculatePaymentAmount()
    {
        $this->paymentAmount = collect($this->outstandingOrders)
            ->whereIn('id', $this->selectedOrders)
            ->sum('outstanding');
    }

    public function submitPayment()
    {
        if (empty($this->selectedOrders)) {
            $this->alert('error', __('modules.customer.select_orders_to_pay'), ['toast' => true]);
            return;
        }

        if ($this->paymentAmount <= 0) {
            $this->alert('error', __('modules.customer.payment_amount_required'), ['toast' => true]);
            return;
        }

        try {
            DB::transaction(function () {
                $remainingAmount = $this->paymentAmount;
                
                foreach ($this->selectedOrders as $orderId) {
                    $order = Order::find($orderId);
                    if (!$order || $order->customer_id !== $this->customer->id) {
                        continue;
                    }

                    $paidAmount = (float)$order->payments()
                        ->where('payment_method', '!=', 'due')
                        ->sum('amount');
                    
                    $orderOutstanding = max(0, (float)$order->total - $paidAmount);
                    
                    if ($orderOutstanding <= 0 || $remainingAmount <= 0) {
                        continue;
                    }

                    // Calculate payment for this order
                    $orderPayment = min($remainingAmount, $orderOutstanding);
                    
                    // Create payment record
                    Payment::create([
                        'order_id' => $order->id,
                        'payment_method' => $this->paymentMethod,
                        'amount' => $orderPayment,
                        'balance' => 0,
                        'notes' => $this->notes ?: null,
                        'branch_id' => branch()->id,
                        'restaurant_id' => restaurant()->id,
                    ]);

                    // Update order
                    $newPaidAmount = $paidAmount + $orderPayment;
                    $order->amount_paid = $newPaidAmount;
                    $order->status = $newPaidAmount >= $order->total ? 'paid' : 'payment_due';
                    $order->save();

                    // Remove due payment if exists
                    Payment::where('order_id', $order->id)
                        ->where('payment_method', 'due')
                        ->delete();

                    // Add due payment if still outstanding
                    if ($newPaidAmount < $order->total) {
                        Payment::create([
                            'order_id' => $order->id,
                            'payment_method' => 'due',
                            'amount' => $order->total - $newPaidAmount,
                            'branch_id' => branch()->id,
                            'restaurant_id' => restaurant()->id,
                        ]);
                    }

                    $remainingAmount -= $orderPayment;
                }
            });

            $this->alert('success', __('modules.customer.payment_recorded_successfully'), ['toast' => true]);
            
            // Reset form
            $this->selectedOrders = [];
            $this->paymentAmount = 0;
            $this->notes = '';
            
            // Reload orders
            $this->loadOutstandingOrders();
            
            // Dispatch event to refresh customer table
            $this->dispatch('customerPaymentRecorded');
            $this->dispatch('refreshCustomers');
            
        } catch (\Exception $e) {
            Log::error('Error recording customer payment: ' . $e->getMessage());
            $this->alert('error', __('modules.customer.payment_failed'), ['toast' => true]);
        }
    }

    public function render()
    {
        return view('livewire.customer.customer-payment');
    }
}

