<?php

namespace Modules\Hotel\Livewire\Dashboard;

use Livewire\Component;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;
use Modules\Hotel\Entities\HousekeepingTask;
use Modules\Hotel\Entities\HotelSetting;
use Modules\Hotel\Entities\HotelExpense;
use Carbon\Carbon;

class HotelDashboard extends Component
{
    public $selectedPeriod = 'today';
    public $activityFilter = 'all';
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
            default => [Carbon::today(), Carbon::today()],
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

    private function getPeriodDescription(): string
    {
        [$startDate, $endDate] = $this->getPeriodRange();

        if ($startDate->isSameDay($endDate)) {
            return $startDate->format('M d, Y');
        }

        return $startDate->format('M d') . ' – ' . $endDate->format('M d, Y');
    }

    private function getRoomStats(): array
    {
        $query = Room::query();

        return [
            'total' => $query->count(),
            'available' => (clone $query)->where('status', Room::STATUS_AVAILABLE)->count(),
            'occupied' => (clone $query)->where('status', Room::STATUS_OCCUPIED)->count(),
            'reserved' => (clone $query)->where('status', Room::STATUS_RESERVED)->count(),
            'cleaning' => (clone $query)->where('status', Room::STATUS_CLEANING)->count(),
            'maintenance' => (clone $query)->where('status', Room::STATUS_MAINTENANCE)->count(),
        ];
    }

    private function getReservationStats(): array
    {
        $query = Reservation::query();
        [$startDate, $endDate] = $this->getPeriodRange();

        return [
            'total_confirmed' => (clone $query)->where('status', Reservation::STATUS_CONFIRMED)->count(),
            'total_checked_in' => (clone $query)->where('status', Reservation::STATUS_CHECKED_IN)->count(),
            'check_ins_period' => (clone $query)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('actual_check_in', [$startDate, $endDate->copy()->endOfDay()])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->whereBetween('check_in_date', [$startDate, $endDate])
                              ->where('status', Reservation::STATUS_CONFIRMED);
                      });
                })
                ->count(),
            'checkouts_period' => (clone $query)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('actual_checkout', [$startDate, $endDate->copy()->endOfDay()])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->whereBetween('checkout_date', [$startDate, $endDate])
                              ->where('status', Reservation::STATUS_CHECKED_IN);
                      });
                })
                ->count(),
            'arriving_today' => Reservation::query()
                ->whereDate('check_in_date', Carbon::today())
                ->where('status', Reservation::STATUS_CONFIRMED)
                ->count(),
            'arriving_next' => Reservation::query()
                ->whereDate('check_in_date', Carbon::today()->addDay())
                ->where('status', Reservation::STATUS_CONFIRMED)
                ->count(),
            'departing_today' => Reservation::query()
                ->whereDate('checkout_date', Carbon::today())
                ->where('status', Reservation::STATUS_CHECKED_IN)
                ->count(),
            'no_shows_period' => (clone $query)
                ->where('status', Reservation::STATUS_NO_SHOW)
                ->whereBetween('check_in_date', [$startDate, $endDate])
                ->count(),
            'outstanding_balance' => (float) Reservation::query()
                ->where('status', Reservation::STATUS_CHECKED_IN)
                ->sum('balance_due'),
        ];
    }

    private function getOccupancyRate(): float
    {
        [$startDate, $endDate] = $this->getPeriodRange();
        $daysInPeriod = max(1, $startDate->diffInDays($endDate) + 1);
        $totalRooms = Room::count();

        if ($totalRooms === 0) {
            return 0;
        }

        $maxRoomNights = $totalRooms * $daysInPeriod;

        $totalNights = Reservation::query()
            ->whereNotIn('status', [Reservation::STATUS_CANCELLED, Reservation::STATUS_NO_SHOW])
            ->where('check_in_date', '<=', $endDate)
            ->where('checkout_date', '>=', $startDate)
            ->get()
            ->sum(fn (Reservation $reservation) => $reservation->countStayNightsInPeriod($startDate, $endDate));

        return $maxRoomNights > 0
            ? round(($totalNights / $maxRoomNights) * 100, 1)
            : 0;
    }

    private function getArrivals()
    {
        [$startDate, $endDate] = $this->getPeriodRange();

        return Reservation::with(['guest', 'room.roomType'])
            ->where('status', Reservation::STATUS_CONFIRMED)
            ->whereBetween('check_in_date', [$startDate, $endDate])
            ->orderBy('check_in_date')
            ->orderBy('check_in_time')
            ->limit(15)
            ->get();
    }

    private function getDepartures()
    {
        [$startDate, $endDate] = $this->getPeriodRange();

        return Reservation::with(['guest', 'room.roomType'])
            ->where('status', Reservation::STATUS_CHECKED_IN)
            ->whereBetween('checkout_date', [$startDate, $endDate])
            ->orderBy('checkout_date')
            ->orderBy('checkout_time')
            ->limit(15)
            ->get();
    }

    private function getInHouseGuests()
    {
        return Reservation::with(['guest', 'room.roomType'])
            ->where('status', Reservation::STATUS_CHECKED_IN)
            ->orderBy('checkout_date')
            ->limit(15)
            ->get();
    }

    private function getPendingHousekeeping()
    {
        return HousekeepingTask::with(['room'])
            ->where('status', HousekeepingTask::STATUS_PENDING)
            ->orderBy('priority', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Restaurant quick stats — shown in hotel_primary & equal modes
     */
    private function getRestaurantStats(): ?array
    {
        if (! in_array($this->businessMode, ['hotel_primary', 'equal'], true)) {
            return null;
        }

        [$startDate, $endDate] = $this->getPeriodRange();
        $orderQuery = \App\Models\Order::whereBetween('date_time', [$startDate, $endDate->endOfDay()]);

        return [
            'orders' => (clone $orderQuery)->count(),
            'earnings' => (clone $orderQuery)->where('status', '!=', 'cancelled')->sum('total'),
            'customers' => (clone $orderQuery)->distinct('customer_id')->count('customer_id'),
        ];
    }

    /**
     * Hotel revenue and expense KPIs for the selected period.
     */
    private function getRevenueMetrics(): array
    {
        [$startDate, $endDate] = $this->getPeriodRange();
        $daysInPeriod = max(1, $startDate->diffInDays($endDate) + 1);

        $charges = RoomCharge::query()
            ->where('branch_id', branch()->id)
            ->whereDate('charge_date', '>=', $startDate)
            ->whereDate('charge_date', '<=', $endDate)
            ->selectRaw('charge_type, SUM(amount) as total')
            ->groupBy('charge_type')
            ->pluck('total', 'charge_type');

        $roomRevenue = (float) ($charges[RoomCharge::TYPE_ROOM_NIGHT] ?? 0);
        $otherRevenue = (float) $charges->except([RoomCharge::TYPE_ROOM_NIGHT])->sum();
        $totalRevenue = $roomRevenue + $otherRevenue;

        $expensesPaid = 0.0;
        $expensesPending = 0.0;
        $expenses = 0.0;

        if (user_can('view_hotel_expenses')) {
            $expenseQuery = HotelExpense::query()
                ->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->whereIn('status', [HotelExpense::STATUS_PAID, HotelExpense::STATUS_PENDING]);

            $expensesPaid = (float) (clone $expenseQuery)->where('status', HotelExpense::STATUS_PAID)->sum('amount');
            $expensesPending = (float) (clone $expenseQuery)->where('status', HotelExpense::STATUS_PENDING)->sum('amount');
            $expenses = $expensesPaid + $expensesPending;
        }

        $net = user_can('view_hotel_expenses')
            ? $totalRevenue - $expenses
            : 0.0;

        $roomsSold = Reservation::query()
            ->whereNotIn('status', [Reservation::STATUS_CANCELLED, Reservation::STATUS_NO_SHOW])
            ->where('check_in_date', '<=', $endDate)
            ->where('checkout_date', '>=', $startDate)
            ->get()
            ->sum(fn (Reservation $reservation) => $reservation->countStayNightsInPeriod($startDate, $endDate));

        $totalRooms = Room::count();
        $adr = $roomsSold > 0 ? $roomRevenue / $roomsSold : 0;
        $totalRoomNights = $totalRooms * $daysInPeriod;
        $revPar = $totalRoomNights > 0 ? $roomRevenue / $totalRoomNights : 0;

        return [
            'total_revenue' => round($totalRevenue, 2),
            'room_revenue' => round($roomRevenue, 2),
            'other_revenue' => round($otherRevenue, 2),
            'expenses' => round($expenses, 2),
            'expenses_paid' => round($expensesPaid, 2),
            'expenses_pending' => round($expensesPending, 2),
            'net' => round($net, 2),
            'adr' => round($adr, 2),
            'rev_par' => round($revPar, 2),
            'rooms_sold' => (int) $roomsSold,
        ];
    }

    private function getHotelSettings(): ?HotelSetting
    {
        return HotelSetting::query()->first();
    }

    public function render()
    {
        $hotelSettings = $this->getHotelSettings();

        return view('hotel::livewire.dashboard.hotel-dashboard', [
            'roomStats' => $this->getRoomStats(),
            'reservationStats' => $this->getReservationStats(),
            'occupancyRate' => $this->getOccupancyRate(),
            'todaysArrivals' => user_can('view_hotel_reservations') ? $this->getArrivals() : collect(),
            'todaysDepartures' => user_can('view_hotel_reservations') ? $this->getDepartures() : collect(),
            'inHouseGuests' => user_can('view_hotel_reservations') ? $this->getInHouseGuests() : collect(),
            'pendingHousekeeping' => user_can('view_hotel_housekeeping') ? $this->getPendingHousekeeping() : collect(),
            'businessMode' => $this->businessMode,
            'restaurantStats' => $this->getRestaurantStats(),
            'revenueMetrics' => $this->getRevenueMetrics(),
            'periodLabel' => $this->getPeriodLabel(),
            'periodDescription' => $this->getPeriodDescription(),
            'hotelSettings' => $hotelSettings,
        ])->layout('layouts.app');
    }
}
