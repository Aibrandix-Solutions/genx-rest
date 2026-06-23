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

    private function restaurantId(): int
    {
        return (int) restaurant()->id;
    }

    private function getRoomStats()
    {
        $query = Room::where('restaurant_id', $this->restaurantId());

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
        $query = Reservation::where('restaurant_id', $this->restaurantId());

        [$startDate, $endDate] = $this->getPeriodRange();

        return [
            'total_confirmed' => (clone $query)->where('status', Reservation::STATUS_CONFIRMED)->count(),
            'total_checked_in' => (clone $query)->where('status', Reservation::STATUS_CHECKED_IN)->count(),
            'check_ins_period' => (clone $query)
                ->where(function ($q) use ($startDate, $endDate) {
                    // Already checked in during this period
                    $q->whereBetween('actual_check_in', [$startDate, $endDate->copy()->endOfDay()])
                      // OR still pending arrival for this period
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->whereBetween('check_in_date', [$startDate, $endDate])
                              ->where('status', Reservation::STATUS_CONFIRMED);
                      });
                })
                ->count(),
            'checkouts_period' => (clone $query)
                ->where(function ($q) use ($startDate, $endDate) {
                    // Already checked out during this period
                    $q->whereBetween('actual_checkout', [$startDate, $endDate->copy()->endOfDay()])
                      // OR still pending departure for this period
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->whereBetween('checkout_date', [$startDate, $endDate])
                              ->where('status', Reservation::STATUS_CHECKED_IN);
                      });
                })
                ->count(),
            'arriving_next' => (clone $query)
                ->whereDate('check_in_date', Carbon::today()->addDay())
                ->where('status', Reservation::STATUS_CONFIRMED)
                ->count(),
        ];
    }

    private function getOccupancyRate()
    {
        [$startDate, $endDate] = $this->getPeriodRange();
        $daysInPeriod = max(1, $startDate->diffInDays($endDate) + 1);
        $totalRooms = Room::where('restaurant_id', $this->restaurantId())->count();

        if ($totalRooms === 0) {
            return 0;
        }

        $maxRoomNights = $totalRooms * $daysInPeriod;

        $totalNights = Reservation::where('restaurant_id', $this->restaurantId())
            ->whereNotIn('status', [Reservation::STATUS_CANCELLED, Reservation::STATUS_NO_SHOW])
            ->where('check_in_date', '<', $endDate->copy()->addDay())
            ->where('checkout_date', '>', $startDate)
            ->get()
            ->sum(function (Reservation $reservation) use ($startDate, $endDate) {
                if (!$reservation->check_in_date || !$reservation->checkout_date) {
                    return 0;
                }

                $stayStart = $reservation->check_in_date->greaterThan($startDate)
                    ? $reservation->check_in_date
                    : $startDate->copy();
                $stayEnd = $reservation->checkout_date->lessThan($endDate)
                    ? $reservation->checkout_date
                    : $endDate->copy()->addDay();

                return max(0, $stayStart->diffInDays($stayEnd));
            });

        return $maxRoomNights > 0
            ? round(($totalNights / $maxRoomNights) * 100, 1)
            : 0;
    }

    private function getTodaysArrivals()
    {
        [$startDate, $endDate] = $this->getPeriodRange();

        return Reservation::with(['guest', 'room.roomType'])
            ->where('restaurant_id', $this->restaurantId())
            ->where('status', Reservation::STATUS_CONFIRMED)
            ->whereBetween('check_in_date', [$startDate, $endDate])
            ->orderBy('check_in_date')
            ->orderBy('check_in_time')
            ->limit(10)
            ->get();
    }

    private function getTodaysDepartures()
    {
        [$startDate, $endDate] = $this->getPeriodRange();

        return Reservation::with(['guest', 'room.roomType'])
            ->where('restaurant_id', $this->restaurantId())
            ->where('status', Reservation::STATUS_CHECKED_IN)
            ->whereBetween('checkout_date', [$startDate, $endDate])
            ->orderBy('checkout_date')
            ->orderBy('checkout_time')
            ->limit(10)
            ->get();
    }

    private function getInHouseGuests()
    {
        return Reservation::with(['guest', 'room.roomType'])
            ->where('restaurant_id', $this->restaurantId())
            ->where('status', Reservation::STATUS_CHECKED_IN)
            ->orderBy('checkout_date') // Show those leaving soonest first
            ->limit(10)
            ->get();
    }

    private function getPendingHousekeeping()
    {
        return HousekeepingTask::with(['room'])
            ->where('restaurant_id', $this->restaurantId())
            ->where('status', HousekeepingTask::STATUS_PENDING)
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

        $orderQuery = \App\Models\Order::whereBetween('date_time', [$startDate, $endDate->endOfDay()]);

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
        [$startDate, $endDate] = $this->getPeriodRange();
        $daysInPeriod = max(1, $startDate->diffInDays($endDate) + 1);

        // Total room revenue in the selected period
        $roomRevenue = Reservation::where('restaurant_id', $this->restaurantId())
            ->whereIn('status', [
                Reservation::STATUS_CHECKED_IN,
                Reservation::STATUS_CHECKED_OUT,
            ])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('check_in_date', [$startDate, $endDate])
                  ->orWhereBetween('checkout_date', [$startDate, $endDate]);
            })
            ->sum('total_amount');

        // Rooms sold in the period (reservation count)
        $roomsSold = Reservation::where('restaurant_id', $this->restaurantId())
            ->whereIn('status', [
                Reservation::STATUS_CHECKED_IN,
                Reservation::STATUS_CHECKED_OUT,
            ])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('check_in_date', [$startDate, $endDate])
                  ->orWhereBetween('checkout_date', [$startDate, $endDate]);
            })
            ->count();

        $totalRooms = Room::where('restaurant_id', $this->restaurantId())->count();

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
        return view('hotel::livewire.dashboard.hotel-dashboard', [
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
