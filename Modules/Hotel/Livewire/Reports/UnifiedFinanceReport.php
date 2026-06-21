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

class UnifiedFinanceReport extends Component
{
    use LivewireAlert;

    public $dateRangeType = 'currentMonth';
    public $startDate = '';
    public $endDate   = '';

    public function mount()
    {
        abort_unless(user_can('view_unified_finance_report'), 403);
        $this->setDateRange();
    }

    public function setDateRange()
    {
        $tz = timezone();
        $ranges = [
            'today'        => [now($tz)->startOfDay(), now($tz)->endOfDay()],
            'yesterday'    => [now($tz)->subDay()->startOfDay(), now($tz)->subDay()->endOfDay()],
            'currentWeek'  => [now($tz)->startOfWeek(), now($tz)->endOfWeek()],
            'last7Days'    => [now($tz)->subDays(7)->startOfDay(), now($tz)->endOfDay()],
            'currentMonth' => [now($tz)->startOfMonth(), now($tz)->endOfDay()],
            'lastMonth'    => [now($tz)->subMonth()->startOfMonth(), now($tz)->subMonth()->endOfMonth()],
            'currentYear'  => [now($tz)->startOfYear(), now($tz)->endOfDay()],
        ];
        [$start, $end] = $ranges[$this->dateRangeType] ?? $ranges['currentMonth'];
        $this->startDate = $start->format('Y-m-d');
        $this->endDate   = $end->format('Y-m-d');
    }

    public function updatedDateRangeType()
    {
        $this->setDateRange();
    }

    // ──────────────────────────────────────────────
    //  Summary Totals (computed property)
    // ──────────────────────────────────────────────
    public function getSummaryProperty(): array
    {
        $restaurantId = restaurant()->id;
        $from = $this->startDate . ' 00:00:00';
        $to   = $this->endDate   . ' 23:59:59';

        // --- Restaurant dine-in / pickup / delivery sales (NOT room-service) ---
        $restaurantSales = Order::where('branch_id', branch()->id)
            ->whereNull('hotel_reservation_id')
            ->whereIn('status', ['paid', 'payment_due'])
            ->whereBetween('date_time', [$from, $to])
            ->sum('total');

        // --- Room-service orders (tagged to a hotel reservation) ---
        $roomServiceSales = Order::where('branch_id', branch()->id)
            ->whereNotNull('hotel_reservation_id')
            ->whereIn('status', OrderFolioSettlement::hotelRevenueStatuses())
            ->whereBetween('date_time', [$from, $to])
            ->sum('total');

        // --- Room Night charges ---
        $roomNightRevenue = RoomCharge::whereHas('reservation', fn($q) => $q->where('restaurant_id', $restaurantId))
            ->where('charge_type', RoomCharge::TYPE_ROOM_NIGHT)
            ->whereDate('charge_date', '>=', $this->startDate)
            ->whereDate('charge_date', '<=', $this->endDate)
            ->sum('amount');

        // --- Other hotel add-on charges (minibar, laundry, service, tax, other — NOT room_night / restaurant) ---
        $hotelAddOns = RoomCharge::whereHas('reservation', fn($q) => $q->where('restaurant_id', $restaurantId))
            ->whereNotIn('charge_type', [RoomCharge::TYPE_ROOM_NIGHT, RoomCharge::TYPE_RESTAURANT])
            ->whereDate('charge_date', '>=', $this->startDate)
            ->whereDate('charge_date', '<=', $this->endDate)
            ->sum('amount');

        // --- Hotel Payments received ---
        $hotelPaymentsReceived = HotelPayment::where('restaurant_id', $restaurantId)
            ->where('payment_type', '!=', HotelPayment::TYPE_REFUND)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $hotelRefunds = HotelPayment::where('restaurant_id', $restaurantId)
            ->where('payment_type', HotelPayment::TYPE_REFUND)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        // --- Restaurant Payments received ---
        $restaurantPaymentsReceived = Payment::whereHas('order', function ($q) use ($from, $to) {
            $q->where('branch_id', branch()->id)
              ->whereNull('hotel_reservation_id')
              ->whereBetween('date_time', [$from, $to]);
        })->sum('amount');

        // --- Hotel Expenses ---
        $hotelExpenses = HotelExpense::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['paid', 'pending'])
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->sum('amount');

        $hotelExpensesByDept = HotelExpense::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['paid', 'pending'])
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->groupBy('department')
            ->select('department', DB::raw('SUM(amount) as total'))
            ->get()
            ->mapWithKeys(fn($r) => [$r->department => $r->total]);

        // --- Outstanding hotel balances ---
        $hotelOutstanding = DB::table('hotel_reservations')
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereRaw('COALESCE(balance_due, 0) > 0')
            ->sum('balance_due');

        $totalRevenue = $restaurantSales + $roomServiceSales + $roomNightRevenue + $hotelAddOns;
        $totalCollected = $hotelPaymentsReceived + $restaurantPaymentsReceived - $hotelRefunds;

        return compact(
            'restaurantSales',
            'roomServiceSales',
            'roomNightRevenue',
            'hotelAddOns',
            'hotelPaymentsReceived',
            'hotelRefunds',
            'restaurantPaymentsReceived',
            'hotelExpenses',
            'hotelExpensesByDept',
            'hotelOutstanding',
            'totalRevenue',
            'totalCollected',
        );
    }

    // ──────────────────────────────────────────────
    //  Daily breakdown
    // ──────────────────────────────────────────────
    public function getDailyBreakdownProperty(): \Illuminate\Support\Collection
    {
        $restaurantId = restaurant()->id;
        $from = $this->startDate;
        $to   = $this->endDate;

        // Restaurant sales per day
        $restaurantByDay = Order::where('branch_id', branch()->id)
            ->whereNull('hotel_reservation_id')
            ->whereIn('status', ['paid', 'payment_due'])
            ->whereBetween(DB::raw('DATE(date_time)'), [$from, $to])
            ->groupBy(DB::raw('DATE(date_time)'))
            ->select(DB::raw('DATE(date_time) as day'), DB::raw('SUM(total) as amount'))
            ->get()->keyBy('day');

        // Room-service per day
        $roomServiceByDay = Order::where('branch_id', branch()->id)
            ->whereNotNull('hotel_reservation_id')
            ->whereIn('status', OrderFolioSettlement::hotelRevenueStatuses())
            ->whereBetween(DB::raw('DATE(date_time)'), [$from, $to])
            ->groupBy(DB::raw('DATE(date_time)'))
            ->select(DB::raw('DATE(date_time) as day'), DB::raw('SUM(total) as amount'))
            ->get()->keyBy('day');

        // Room charges per day (exclude restaurant lines — counted via room-service orders above)
        $roomChargesByDay = RoomCharge::whereHas('reservation', fn($q) => $q->where('restaurant_id', $restaurantId))
            ->where('charge_type', '!=', RoomCharge::TYPE_RESTAURANT)
            ->whereBetween(DB::raw('DATE(charge_date)'), [$from, $to])
            ->groupBy(DB::raw('DATE(charge_date)'))
            ->select(DB::raw('DATE(charge_date) as day'), DB::raw('SUM(amount) as amount'))
            ->get()->keyBy('day');

        // Expenses per day
        $expensesByDay = HotelExpense::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['paid', 'pending'])
            ->whereBetween('expense_date', [$from, $to])
            ->groupBy('expense_date')
            ->select(DB::raw('DATE(expense_date) as day'), DB::raw('SUM(amount) as amount'))
            ->get()->keyBy('day');

        // Build day range
        $days = collect();
        $current = Carbon::parse($from);
        $end = Carbon::parse($to);
        while ($current->lte($end)) {
            $day = $current->toDateString();
            $rSales   = (float) ($restaurantByDay->get($day)?->amount ?? 0);
            $rsService = (float) ($roomServiceByDay->get($day)?->amount ?? 0);
            $rCharges  = (float) ($roomChargesByDay->get($day)?->amount ?? 0);
            $expenses  = (float) ($expensesByDay->get($day)?->amount ?? 0);
            $days->push([
                'day'             => $day,
                'restaurant'      => $rSales,
                'room_service'    => $rsService,
                'hotel_charges'   => $rCharges,
                'total_revenue'   => $rSales + $rsService + $rCharges,
                'expenses'        => $expenses,
                'net'             => $rSales + $rsService + $rCharges - $expenses,
            ]);
            $current->addDay();
        }
        return $days->filter(fn($d) => $d['total_revenue'] > 0 || $d['expenses'] > 0);
    }

    public function render()
    {
        return view('hotel::livewire.reports.unified-finance-report', [
            'summary'        => $this->summary,
            'dailyBreakdown' => $this->dailyBreakdown,
            'currencyId'     => restaurant()->currency_id,
        ])->layout('layouts.app');
    }
}
