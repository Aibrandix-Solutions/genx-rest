<?php

namespace Modules\Hotel\Livewire\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Hotel\Exports\PropertyProfitLossExport;
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
            ->leftJoin('hotel_expense_departments', 'hotel_expenses.department_id', '=', 'hotel_expense_departments.id')
            ->groupBy('hotel_expense_departments.name')
            ->select(DB::raw("COALESCE(hotel_expense_departments.name, 'Other') as department"), DB::raw('SUM(amount) as total'))
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

    public function exportExcel()
    {
        abort_unless(user_can('view_property_pnl'), 403);

        $filename = 'property-pnl-' . $this->startDate . '_to_' . $this->endDate . '.xlsx';

        return Excel::download(
            new PropertyProfitLossExport(
                $this->pnlData,
                $this->startDate,
                $this->endDate,
                (int) restaurant()->currency_id,
                (string) (restaurant()->name ?? ''),
            ),
            $filename,
        );
    }

    public function exportPdf()
    {
        abort_unless(user_can('view_property_pnl'), 403);

        $pdf = Pdf::loadView('hotel::reports.property-profit-loss-export', [
            'data' => $this->pnlData,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'currencyId' => (int) restaurant()->currency_id,
            'propertyName' => (string) (restaurant()->name ?? ''),
        ])->setPaper('A4', 'landscape');

        $filename = 'property-pnl-' . $this->startDate . '_to_' . $this->endDate . '.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $filename);
    }

    public function render()
    {
        return view('hotel::livewire.reports.property-profit-loss', [
            'data'       => $this->pnlData,
            'currencyId' => restaurant()->currency_id,
        ])->layout('layouts.app');
    }
}
