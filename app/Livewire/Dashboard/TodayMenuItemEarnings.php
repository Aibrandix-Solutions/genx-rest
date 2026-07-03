<?php

namespace App\Livewire\Dashboard;

use App\Models\MenuItem;
use App\Scopes\AvailableMenuItemScope;
use Livewire\Component;

class TodayMenuItemEarnings extends Component
{
    public function render()
    {
        $start = now()->startOfDay()->toDateTimeString();
        $end = now()->endOfDay()->toDateTimeString();

        $menuItems = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)
            ->with(['orders' => function ($q) use ($start, $end) {
                $q->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.status', 'paid')
                    ->whereDate('orders.date_time', '>=', $start)
                    ->whereDate('orders.date_time', '<=', $end);
            }])
            ->get()
            ->filter(fn (MenuItem $item) => $item->orders->sum('amount') > 0)
            ->sortByDesc(fn (MenuItem $item) => $item->orders->sum('amount'))
            ->take(5)
            ->values();

        return view('livewire.dashboard.today-menu-item-earnings', [
            'menuItems' => $menuItems,
        ]);
    }
}
