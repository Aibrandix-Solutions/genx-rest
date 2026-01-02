<?php

namespace App\Livewire\Dashboard;

use App\Events\TodayOrdersUpdated;
use App\Models\Kot;
use App\Models\Order;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class TodayOrders extends Component
{

    use LivewireAlert;

    public function render()
    {
        $count = Order::whereDate('orders.date_time', '>=', now()->startOfDay()->toDateTimeString())
            ->whereDate('orders.date_time', '<=', now()->endOfDay()->toDateTimeString())
            ->where('status', '<>', 'canceled')
            ->where('status', '<>', 'draft')
            ->count();

        $todayKotCount = Kot::join('orders', 'kots.order_id', '=', 'orders.id')
            ->whereDate('kots.created_at', '>=', now()->startOfDay()->toDateTimeString())
            ->whereDate('kots.created_at', '<=', now()->endOfDay()->toDateTimeString())
            ->where('orders.status', '<>', 'canceled')
            ->where('orders.status', '<>', 'draft')
            ->count();

        $playSound = false;
        $playCustomerOrderPlacedSound = false;

        if (session()->has('today_order_count') && session('today_order_count') < $todayKotCount) {
            $playSound = true;

            $this->alert('success', __('messages.newOrderReceived'), [
                'toast' => true,
                'position' => 'top-end'
            ]);

            $this->dispatch('refreshOrders');
        }

        session(['today_order_count' => $todayKotCount]);

        // Customer-site order placed (should be confirmed by staff before kitchen)
        $pendingShopCount = Order::whereDate('orders.date_time', '>=', now()->startOfDay()->toDateTimeString())
            ->whereDate('orders.date_time', '<=', now()->endOfDay()->toDateTimeString())
            ->where('status', 'pending_verification')
            ->where('placed_via', 'shop')
            ->count();

        if (session()->has('pending_shop_order_count') && session('pending_shop_order_count') < $pendingShopCount) {
            $playCustomerOrderPlacedSound = true;

            $this->alert('success', __('messages.customerOrderPlaced'), [
                'toast' => true,
                'position' => 'top-end'
            ]);
        }

        session(['pending_shop_order_count' => $pendingShopCount]);


        return view('livewire.dashboard.today-orders', [
            'count' => $count,
            'playSound' => $playSound,
            'playCustomerOrderPlacedSound' => $playCustomerOrderPlacedSound,
        ]);
    }

    /**
     * Handle refresh from Pusher event
     */
    public function refreshOrders()
    {
        // This method will be called when Pusher sends data
        // The component will automatically re-render with fresh data
        $this->dispatch('$refresh');
    }
}
