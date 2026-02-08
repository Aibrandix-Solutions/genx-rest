<?php

namespace Modules\Hotel\Livewire\Dashboard;

use Livewire\Component;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\HousekeepingTask;
use Carbon\Carbon;

class HotelDashboard extends Component
{
    public $selectedBranch = 'all';
    public $selectedPeriod = 'today';

    public function mount()
    {
        abort_unless(user_can('view_hotel_dashboard'), 403);
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

        $today = Carbon::today();

        return [
            'total_confirmed' => (clone $query)->where('status', Reservation::STATUS_CONFIRMED)->count(),
            'total_checked_in' => (clone $query)->where('status', Reservation::STATUS_CHECKED_IN)->count(),
            'check_ins_today' => (clone $query)
                ->whereDate('check_in_date', $today)
                ->where('status', Reservation::STATUS_CONFIRMED)
                ->count(),
            'checkouts_today' => (clone $query)
                ->whereDate('checkout_date', $today)
                ->where('status', Reservation::STATUS_CHECKED_IN)
                ->count(),
            'arriving_tomorrow' => (clone $query)
                ->whereDate('check_in_date', $today->copy()->addDay())
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
        return Reservation::with(['guest', 'room.roomType'])
            ->where('status', Reservation::STATUS_CONFIRMED)
            ->whereDate('check_in_date', Carbon::today())
            ->when($this->selectedBranch !== 'all', function ($query) {
                $query->where('branch_id', $this->selectedBranch);
            })
            ->orderBy('check_in_time')
            ->limit(5)
            ->get();
    }

    private function getTodaysDepartures()
    {
        return Reservation::with(['guest', 'room.roomType'])
            ->where('status', Reservation::STATUS_CHECKED_IN)
            ->whereDate('checkout_date', Carbon::today())
            ->when($this->selectedBranch !== 'all', function ($query) {
                $query->where('branch_id', $this->selectedBranch);
            })
            ->orderBy('checkout_time')
            ->limit(5)
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
            ->limit(5)
            ->get();
    }

    public function updatedSelectedBranch()
    {
        $this->dispatch('$refresh');
    }

    public function updatedSelectedPeriod()
    {
        $this->dispatch('$refresh');
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
        ])->layout('layouts.app');
    }
}
