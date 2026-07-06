<?php

namespace App\Livewire\Dashboard;

use App\Models\Order;
use App\Services\SalesReportData;
use Livewire\Component;

class AverageDailyEarning extends Component
{
    public $orderCount;

    public $percentChange;

    public function mount()
    {
        $statuses = SalesReportData::reportOrderStatuses();
        $daysElapsed = max(1, (int) now()->format('j'));

        $startOfMonth = now()->startOfMonth()->startOfDay()->toDateTimeString();
        $tillToday = now()->endOfDay()->toDateTimeString();

        $startOfLastMonth = now()->copy()->subMonth()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfLastMonth = now()->copy()->subMonth()->endOfMonth()->endOfDay()->toDateTimeString();
        $daysInPreviousMonth = now()->copy()->subMonth()->daysInMonth;

        $totalEarnings = Order::query()
            ->whereIn('orders.status', $statuses)
            ->whereBetween('orders.date_time', [$startOfMonth, $tillToday])
            ->sum('total');

        $totalPreviousEarnings = Order::query()
            ->whereIn('orders.status', $statuses)
            ->whereBetween('orders.date_time', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total');

        $this->orderCount = $totalEarnings / $daysElapsed;

        $averageDailyPreviousEarnings = $totalPreviousEarnings / $daysInPreviousMonth;

        $orderDifference = ($this->orderCount - $averageDailyPreviousEarnings);

        $this->percentChange = (($orderDifference / ($averageDailyPreviousEarnings == 0 ? 1 : $averageDailyPreviousEarnings)) * 100);
    }

    public function render()
    {
        return view('livewire.dashboard.average-daily-earning');
    }
}
