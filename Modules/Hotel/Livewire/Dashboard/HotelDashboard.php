<?php

namespace Modules\Hotel\Livewire\Dashboard;

use Livewire\Component;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\HousekeepingTask;
use Modules\Hotel\Entities\HotelSetting;
use Carbon\Carbon;

class HotelDashboard extends Component
{
    public $selectedBranch = 'all';
    public $selectedPeriod = 'today';
    public $businessMode = 'restaurant_primary';

    public function mount()
    {
        abort_unless(user_can('view_hotel_dashboard'), 403);
        $this->businessMode = function_exists('hotel_business_mode') ? hotel_business_mode() : 'restaurant_primary';
    }

    /**
     * Get the date range based on the selected period filter.
     */
    private function getPeriodRange(): array
    {
        return match ($this->selectedPeriod) {
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            default => [Carbon::today(), Carbon::today()], // 'today'
        };
    }

    /**
     * Human-readable label for the active period.
     */
    private function getPeriodLabel(): string
    {
        return match ($this->selectedPeriod) {
            'week' => __('This week'),
            'month' => __('This month'),
            default => __('Today'),
        };
    }

    private function getRoomStats()
    {
        $query = Room::query()
            ->when($this->selectedBranch !== 'all', function ($query) {
                $query->where('branch_id', $this->selectedBranch);
            });

        return [
            'total' => $query->count(),
            'available' => (clone $query)->where('status', Room::STATUS_AVAILABLE)->count(),
            'occupied' => (clone $query)->where('status', Room::STATUS_OCCUPIED)->count(),
            'cleaning' => (clone $query)->where('status', Room::STATUS_CLEANING)->count(),
            'maintenance' => (clone $query)->where('status', Room::STATUS_MAINTENANCE)->count(),
        ];
    }

    private function getReservationStats()
    {
        $query = Reservation::query()
            ->when($this->selectedBranch !== 'all', function ($query) {
                $query->where('branch_id', $this->selectedBranch);
            });

        [$startDate, $endDate] = $this->getPeriodRange();

        return [
            'total_confirmed' => (clone $query)->where('status', Reservation::STATUS_CONFIRMED)->count(),
            'total_checked_in' => (clone $query)->where('status', Reservation::STATUS_CHECKED_IN)->count(),
            'check_ins_period' => (clone $query)
                ->whereBetween('check_in_date', [$startDate, $endDate])
                ->where('status', Reservation::STATUS_CONFIRMED)
                ->count(),
            'checkouts_period' => (clone $query)
                ->whereBetween('checkout_date', [$startDate, $endDate])
                ->where('status', Reservation::STATUS_CHECKED_IN)
                ->count(),
            'arriving_next' => (clone $query)
                ->whereDate('check_in_date', Carbon::today()->addDay())
                ->where('status', Reservation::STATUS_CONFIRMED)
                ->count(),
        ];
    }

    private function getOccupancyRate()
    {
        $roomStats = $this->getRoomStats();
        
        if ($roomStats['total'] == 0) {
            return 0;
        }

        return round(($roomStats['occupied'] / $roomStats['total']) * 100, 1);
    }

    private function getTodaysArrivals()
    {
        [$startDate, $endDate] = $this->getPeriodRange();

        return Reservation::with(['guest', 'room.roomType'])
            ->where('status', Reservation::STATUS_CONFIRMED)
            ->whereBetween('check_in_date', [$startDate, $endDate])
            ->when($this->selectedBranch !== 'all', function ($query) {
                $query->where('branch_id', $this->selectedBranch);
            })
            ->orderBy('check_in_date')
            ->orderBy('check_in_time')
            ->limit(10)
            ->get();
    }

    private function getTodaysDepartures()
    {
        [$startDate, $endDate] = $this->getPeriodRange();

        return Reservation::with(['guest', 'room.roomType'])
            ->where('status', Reservation::STATUS_CHECKED_IN)
            ->whereBetween('checkout_date', [$startDate, $endDate])
            ->when($this->selectedBranch !== 'all', function ($query) {
                $query->where('branch_id', $this->selectedBranch);
            })
            ->orderBy('checkout_date')
            ->orderBy('checkout_time')
            ->limit(10)
            ->get();
    }

    private function getInHouseGuests()
    {
        return Reservation::with(['guest', 'room.roomType'])
            ->where('status', Reservation::STATUS_CHECKED_IN)
            ->when($this->selectedBranch !== 'all', function ($query) {
                $query->where('branch_id', $this->selectedBranch);
            })
            ->orderBy('checkout_date') // Show those leaving soonest first
            ->limit(10)
            ->get();
    }

    private function getPendingHousekeeping()
    {
        return HousekeepingTask::with(['room'])
            ->where('status', HousekeepingTask::STATUS_PENDING)
            ->when($this->selectedBranch !== 'all', function ($query) {
                $query->where('branch_id', $this->selectedBranch);
            })
            ->orderBy('priority', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Restaurant quick stats — shown in hotel_primary & equal modes
     */
    private function getRestaurantStats()
    {
        [$startDate, $endDate] = $this->getPeriodRange();
        $branchFilter = $this->selectedBranch !== 'all' ? $this->selectedBranch : null;

        $orderQuery = \App\Models\Order::whereBetween('date_time', [$startDate, $endDate->endOfDay()])
            ->when($branchFilter, fn($q) => $q->where('branch_id', $branchFilter));

        $orders = (clone $orderQuery)->count();
        $earnings = (clone $orderQuery)->where('status', '!=', 'cancelled')->sum('total');
        $customers = (clone $orderQuery)->distinct('customer_id')->count('customer_id');

        return [
            'orders' => $orders,
            'earnings' => $earnings,
            'customers' => $customers,
        ];
    }

    /**
     * Hotel revenue KPIs: ADR, RevPAR, total room revenue.
     * Uses the selected period (today / this week / this month).
     */
    private function getRevenueMetrics()
    {
        $branchFilter = $this->selectedBranch !== 'all' ? $this->selectedBranch : null;
        [$startDate, $endDate] = $this->getPeriodRange();
        $daysInPeriod = max(1, $startDate->diffInDays($endDate) + 1);

        // Total room revenue in the selected period
        $roomRevenue = Reservation::whereIn('status', [
                Reservation::STATUS_CHECKED_IN,
                Reservation::STATUS_CHECKED_OUT,
            ])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('check_in_date', [$startDate, $endDate])
                  ->orWhereBetween('checkout_date', [$startDate, $endDate]);
            })
            ->when($branchFilter, fn($q) => $q->where('branch_id', $branchFilter))
            ->sum('total_amount');

        // Rooms sold in the period (reservation count)
        $roomsSold = Reservation::whereIn('status', [
                Reservation::STATUS_CHECKED_IN,
                Reservation::STATUS_CHECKED_OUT,
            ])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('check_in_date', [$startDate, $endDate])
                  ->orWhereBetween('checkout_date', [$startDate, $endDate]);
            })
            ->when($branchFilter, fn($q) => $q->where('branch_id', $branchFilter))
            ->count();

        $totalRooms = Room::when($branchFilter, fn($q) => $q->where('branch_id', $branchFilter))->count();

        // ADR = Room Revenue / Rooms Sold
        $adr = $roomsSold > 0 ? $roomRevenue / $roomsSold : 0;

        // RevPAR = Room Revenue / (Total Rooms × Days in Period)
        $totalRoomNights = $totalRooms * $daysInPeriod;
        $revPar = $totalRoomNights > 0 ? $roomRevenue / $totalRoomNights : 0;

        return [
            'room_revenue' => $roomRevenue,
            'adr' => round($adr, 2),
            'rev_par' => round($revPar, 2),
            'rooms_sold' => $roomsSold,
        ];
    }

    public function render()
    {
        $branches = \App\Models\Branch::where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get();

        return view('hotel::livewire.dashboard.hotel-dashboard', [
            'branches' => $branches,
            'roomStats' => $this->getRoomStats(),
            'reservationStats' => $this->getReservationStats(),
            'occupancyRate' => $this->getOccupancyRate(),
            'todaysArrivals' => $this->getTodaysArrivals(),
            'todaysDepartures' => $this->getTodaysDepartures(),
            'inHouseGuests' => $this->getInHouseGuests(),
            'pendingHousekeeping' => $this->getPendingHousekeeping(),
            'businessMode' => $this->businessMode,
            'restaurantStats' => in_array($this->businessMode, ['hotel_primary', 'equal']) ? $this->getRestaurantStats() : null,
            'revenueMetrics' => $this->getRevenueMetrics(),
            'periodLabel' => $this->getPeriodLabel(),
        ])->layout('layouts.app');
    }
}
