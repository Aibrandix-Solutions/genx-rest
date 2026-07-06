<?php

namespace Modules\Hotel\Livewire\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\HotelPayment;
use Modules\Hotel\Entities\RoomCharge;
use Modules\Hotel\Entities\HotelExpense;
use App\Models\Order;
use App\Models\Payment;
use Modules\Hotel\Services\OrderFolioSettlement;

class PropertyProfitLoss extends Component
{
    use LivewireAlert;

    public $dateRangeType = 'currentMonth';
    public $startDate = '';
    public $endDate   = '';

    public function mount()
    {
        abort_unless(user_can('view_property_pnl'), 403);
        $this->setDateRange();
    }

    public function setDateRange()
    {
        $tz = timezone();
        $ranges = [
            'today'        => [now($tz)->startOfDay(), now($tz)->endOfDay()],
            'currentWeek'  => [now($tz)->startOfWeek(), now($tz)->endOfWeek()],
            'currentMonth' => [now($tz)->startOfMonth(), now($tz)->endOfDay()],
            'lastMonth'    => [now($tz)->subMonth()->startOfMonth(), now($tz)->subMonth()->endOfMonth()],
            'currentYear'  => [now($tz)->startOfYear(), now($tz)->endOfDay()],
            'lastYear'     => [now($tz)->subYear()->startOfYear(), now($tz)->subYear()->endOfYear()],
        ];
        [$start, $end] = $ranges[$this->dateRangeType] ?? $ranges['currentMonth'];
        $this->startDate = $start->format('Y-m-d');
        $this->endDate   = $end->format('Y-m-d');
    }

    public function updatedDateRangeType()
    {
        $this->setDateRange();
    }

    public function getPnlDataProperty(): array
    {
        $branchId = branch()->id;
        $from = $this->startDate . ' 00:00:00';
        $to   = $this->endDate   . ' 23:59:59';

        // ── REVENUE ──
        $restaurantSales = Order::where('branch_id', $branchId)
            ->whereNull('hotel_reservation_id')
            ->whereIn('status', ['paid', 'payment_due'])
            ->whereBetween('date_time', [$from, $to])
            ->sum('total');

        $roomServiceSales = Order::where('branch_id', $branchId)
            ->whereNotNull('hotel_reservation_id')
            ->whereIn('status', OrderFolioSettlement::hotelRevenueStatuses())
            ->whereBetween('date_time', [$from, $to])
            ->sum('total');

        $roomNightRevenue = RoomCharge::where('branch_id', $branchId)
            ->where('charge_type', RoomCharge::TYPE_ROOM_NIGHT)
            ->whereDate('charge_date', '>=', $this->startDate)
            ->whereDate('charge_date', '<=', $this->endDate)
            ->sum('amount');

        $hotelAddOns = RoomCharge::where('branch_id', $branchId)
            ->whereNotIn('charge_type', [RoomCharge::TYPE_ROOM_NIGHT, RoomCharge::TYPE_RESTAURANT])
            ->whereDate('charge_date', '>=', $this->startDate)
            ->whereDate('charge_date', '<=', $this->endDate)
            ->sum('amount');

        $totalRevenue = $restaurantSales + $roomServiceSales + $roomNightRevenue + $hotelAddOns;

        // ── EXPENSES (HasBranch scope auto-applies for HotelExpense) ──
        $hotelExpenses = HotelExpense::whereIn('status', ['paid', 'pending'])
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->sum('amount');

        // Restaurant Expenses — scoped by branch_id (Expenses uses HasBranch, not HasRestaurant)
        $restaurantExpenses = DB::table('expenses')
            ->where('branch_id', branch()->id)
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->sum('amount');

        $totalExpenses = $hotelExpenses + $restaurantExpenses;

        // ── NET PROFIT ──
        $netProfit = $totalRevenue - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 1) : 0;

        // ── HOTEL EXPENSES BY DEPARTMENT ──
        $hotelExpByDept = HotelExpense::whereIn('status', ['paid', 'pending'])
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->groupBy('department')
            ->select('department', DB::raw('SUM(amount) as total'))
            ->get();

        // ── RESTAURANT EXPENSES BY CATEGORY ──
        $restExpByCategory = DB::table('expenses')
            ->where('branch_id', branch()->id)
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->groupBy('expense_category_id')
            ->select('expense_category_id', DB::raw('SUM(amount) as total'))
            ->get();

        // ── MONTHLY TREND (last 6 months) ──
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth()->toDateString();
            $monthEnd   = now()->subMonths($i)->endOfMonth()->toDateString();
            $mFrom = $monthStart . ' 00:00:00';
            $mTo   = $monthEnd   . ' 23:59:59';

            $mRevenue = Order::where('branch_id', $branchId)
                ->whereIn('status', ['paid', 'payment_due'])
                ->whereBetween('date_time', [$mFrom, $mTo])
                ->sum('total');
            $mRevenue += RoomCharge::where('branch_id', $branchId)
                ->whereDate('charge_date', '>=', $monthStart)
                ->whereDate('charge_date', '<=', $monthEnd)
                ->sum('amount');

            $mExpenses = HotelExpense::whereIn('status', ['paid', 'pending'])
                ->whereBetween('expense_date', [$monthStart, $monthEnd])
                ->sum('amount');
            $mExpenses += DB::table('expenses')
                ->where('branch_id', branch()->id)
                ->whereBetween('expense_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $trend[] = [
                'label'    => now()->subMonths($i)->format('M Y'),
                'revenue'  => $mRevenue,
                'expenses' => $mExpenses,
                'profit'   => $mRevenue - $mExpenses,
            ];
        }

        return compact(
            'restaurantSales', 'roomServiceSales', 'roomNightRevenue', 'hotelAddOns', 'totalRevenue',
            'hotelExpenses', 'restaurantExpenses', 'totalExpenses',
            'netProfit', 'profitMargin',
            'hotelExpByDept', 'restExpByCategory', 'trend'
        );
    }

    public function render()
    {
        return view('hotel::livewire.reports.property-profit-loss', [
            'data'       => $this->pnlData,
            'currencyId' => restaurant()->currency_id,
        ])->layout('layouts.app');
    }
}
