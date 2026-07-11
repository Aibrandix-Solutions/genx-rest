<?php

namespace Modules\Hotel\Livewire\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Hotel\Exports\UnifiedFinanceReportExport;
use Modules\Hotel\Entities\HotelPayment;
use Modules\Hotel\Entities\RoomCharge;
use Modules\Hotel\Entities\HotelExpense;
use Modules\Hotel\Entities\Reservation;
use App\Models\Order;
use App\Models\Payment;
use Modules\Hotel\Services\OrderFolioSettlement;

class UnifiedFinanceReport extends Component
{
    use LivewireAlert;

    public $dateRangeType = 'currentMonth';
    public $startDate = '';
    public $endDate   = '';
    public $activeTab = 'daily';
    public $showIncomeDetails = false;
    public $showExpenseDetails = false;
    public $showCashInflowDetails = false;
    public $showCashOutflowDetails = false;

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
        $branchId = branch()->id;
        $from = $this->startDate . ' 00:00:00';
        $to   = $this->endDate   . ' 23:59:59';

        // 1. Get filtered active reservations (EXCLUDING cancelled and no-show)
        $detailedIncome = Reservation::with(['guest', 'room.roomType', 'charges'])
            ->where('branch_id', $branchId)
            ->whereBetween('check_in_date', [$this->startDate, $this->endDate])
            ->whereNotIn('status', [Reservation::STATUS_CANCELLED, Reservation::STATUS_NO_SHOW])
            ->get();

        $filteredResIds = $detailedIncome->pluck('id')->toArray();

        // 2. Restaurant dine-in / pickup / delivery sales (NOT room-service)
        $restaurantSales = 0;

        // 3. Room-service orders (tagged to filtered active hotel reservations)
        $roomServiceSales = 0;
        if (!empty($filteredResIds)) {
            $roomServiceSales = Order::where('branch_id', $branchId)
                ->whereIn('hotel_reservation_id', $filteredResIds)
                ->whereIn('status', OrderFolioSettlement::hotelRevenueStatuses())
                ->sum('total');
        }

        // 4. Room Night charges from filtered active reservations
        $roomNightRevenue = 0;
        foreach ($detailedIncome as $res) {
            $roomNightRevenue += (float)$res->charges->where('charge_type', RoomCharge::TYPE_ROOM_NIGHT)->sum('amount');
        }

        // 5. Other hotel add-on charges (minibar, laundry, service, tax, other — NOT room_night / restaurant) from filtered active reservations
        $hotelAddOns = 0;
        foreach ($detailedIncome as $res) {
            $hotelAddOns += (float)$res->charges->whereNotIn('charge_type', [RoomCharge::TYPE_ROOM_NIGHT, RoomCharge::TYPE_RESTAURANT])->sum('amount');
        }

        // 6. Hotel Payments and outstanding balances from filtered active reservations (splitting restaurant charges)
        $hotelPaymentsReceived = 0.0;
        $hotelOutstanding = 0.0;
        $restaurantPaymentsReceived = 0.0;
        $unpaidRestaurant = 0.0;

        foreach ($detailedIncome as $res) {
            $resRestaurantCharges = (float)$res->charges->where('charge_type', RoomCharge::TYPE_RESTAURANT)->sum('amount');
            $paid = (float)$res->paid_amount;
            $unpaid = (float)$res->balance_due;
            $total = (float)$res->total_amount;

            $hotelPaymentsReceived += ($paid - $resRestaurantCharges * ($total > 0 ? ($paid / $total) : 0));
            $hotelOutstanding += ($unpaid - $resRestaurantCharges * ($total > 0 ? ($unpaid / $total) : 0));

            if ($total > 0) {
                $ratio = $resRestaurantCharges / $total;
                $restaurantPaymentsReceived += $paid * $ratio;
                $unpaidRestaurant += $unpaid * $ratio;
            }
        }
        $restaurantPaymentsReceived -= $unpaidRestaurant;

        $hotelRefunds = 0;

        // 7. Restaurant Payments received (already calculated above)
        $restaurantPaymentsReceivedRaw = 0;

        // 8. Hotel Expenses
        $detailedExpenses = $this->detailedExpenses;
        $hotelExpenses = $detailedExpenses->sum('amount');
        $hotelExpensesPaid = $detailedExpenses->where('status', 'paid')->sum('amount');
        $hotelExpensesUnpaid = $detailedExpenses->where('status', 'pending')->sum('amount');

        $hotelExpensesByDept = $detailedExpenses
            ->groupBy('department')
            ->mapWithKeys(fn($items, $dept) => [$dept => $items->sum('amount')]);

        // 9. Total Revenue and Total Collected
        $totalRevenue = $restaurantSales + $detailedIncome->sum('total_amount');
        $totalCollected = $restaurantPaymentsReceived + $detailedIncome->sum('paid_amount');

        return compact(
            'restaurantSales',
            'roomServiceSales',
            'roomNightRevenue',
            'hotelAddOns',
            'hotelPaymentsReceived',
            'hotelRefunds',
            'restaurantPaymentsReceived',
            'hotelExpenses',
            'hotelExpensesPaid',
            'hotelExpensesUnpaid',
            'hotelExpensesByDept',
            'hotelOutstanding',
            'totalRevenue',
            'totalCollected'
        );
    }

    // ──────────────────────────────────────────────
    //  Daily breakdown
    // ──────────────────────────────────────────────
    public function getDailyBreakdownProperty(): \Illuminate\Support\Collection
    {
        $branchId = branch()->id;
        $from = $this->startDate;
        $to   = $this->endDate;

        // Restaurant sales per day
        $restaurantByDay = Order::where('branch_id', $branchId)
            ->whereNull('hotel_reservation_id')
            ->whereIn('status', ['paid', 'payment_due'])
            ->whereBetween(DB::raw('DATE(date_time)'), [$from, $to])
            ->groupBy(DB::raw('DATE(date_time)'))
            ->select(DB::raw('DATE(date_time) as day'), DB::raw('SUM(total) as amount'))
            ->get()->keyBy('day');

        // Room-service per day
        $roomServiceByDay = Order::where('branch_id', $branchId)
            ->whereNotNull('hotel_reservation_id')
            ->whereIn('status', OrderFolioSettlement::hotelRevenueStatuses())
            ->whereBetween(DB::raw('DATE(date_time)'), [$from, $to])
            ->groupBy(DB::raw('DATE(date_time)'))
            ->select(DB::raw('DATE(date_time) as day'), DB::raw('SUM(total) as amount'))
            ->get()->keyBy('day');

        // Room charges per day (exclude restaurant lines — counted via room-service orders above)
        $roomChargesByDay = RoomCharge::where('branch_id', $branchId)
            ->where('charge_type', '!=', RoomCharge::TYPE_RESTAURANT)
            ->whereBetween(DB::raw('DATE(charge_date)'), [$from, $to])
            ->groupBy(DB::raw('DATE(charge_date)'))
            ->select(DB::raw('DATE(charge_date) as day'), DB::raw('SUM(amount) as amount'))
            ->get()->keyBy('day');

        // Expenses per day (HasBranch scope auto-applies)
        $expensesByDay = HotelExpense::whereIn('status', ['paid', 'pending'])
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
            $rSales   = 0.0;
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

    public function exportExcel()
    {
        abort_unless(user_can('view_unified_finance_report'), 403);

        if ($this->activeTab === 'income_expense') {
            $filename = 'income-expense-report-' . $this->startDate . '_to_' . $this->endDate . '.xlsx';
            return Excel::download(
                new \Modules\Hotel\Exports\IncomeExpenseReportExport(
                    $this->summary,
                    $this->detailedIncomeRows,
                    $this->detailedExpenses,
                    $this->startDate,
                    $this->endDate,
                    (int) restaurant()->currency_id,
                    (string) (restaurant()->name ?? ''),
                ),
                $filename,
            );
        } elseif ($this->activeTab === 'cash_flow') {
            $filename = 'cash-flow-report-' . $this->startDate . '_to_' . $this->endDate . '.xlsx';
            return Excel::download(
                new \Modules\Hotel\Exports\CashFlowReportExport(
                    $this->cashFlowSummary,
                    $this->detailedCashInflow,
                    $this->detailedCashOutflow,
                    $this->startDate,
                    $this->endDate,
                    (int) restaurant()->currency_id,
                    (string) (restaurant()->name ?? ''),
                ),
                $filename,
            );
        }

        $filename = 'finance-report-' . $this->startDate . '_to_' . $this->endDate . '.xlsx';

        return Excel::download(
            new UnifiedFinanceReportExport(
                $this->summary,
                $this->dailyBreakdown,
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
        abort_unless(user_can('view_unified_finance_report'), 403);

        if ($this->activeTab === 'income_expense') {
            $pdf = Pdf::loadView('hotel::reports.income-expense-report-export', [
                'summary' => $this->summary,
                'detailedIncome' => $this->detailedIncomeRows,
                'detailedExpenses' => $this->detailedExpenses,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'currencyId' => (int) restaurant()->currency_id,
                'propertyName' => (string) (restaurant()->name ?? ''),
            ])->setPaper('A4', 'landscape');

            $filename = 'income-expense-report-' . $this->startDate . '_to_' . $this->endDate . '.pdf';
            return response()->streamDownload(fn () => print($pdf->output()), $filename);
        } elseif ($this->activeTab === 'cash_flow') {
            $pdf = Pdf::loadView('hotel::reports.cash-flow-report-export', [
                'summary' => $this->cashFlowSummary,
                'detailedInflow' => $this->detailedCashInflow,
                'detailedOutflow' => $this->detailedCashOutflow,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'currencyId' => (int) restaurant()->currency_id,
                'propertyName' => (string) (restaurant()->name ?? ''),
            ])->setPaper('A4', 'landscape');

            $filename = 'cash-flow-report-' . $this->startDate . '_to_' . $this->endDate . '.pdf';
            return response()->streamDownload(fn () => print($pdf->output()), $filename);
        }

        $pdf = Pdf::loadView('hotel::reports.unified-finance-report-export', [
            'summary' => $this->summary,
            'dailyBreakdown' => $this->dailyBreakdown,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'currencyId' => (int) restaurant()->currency_id,
            'propertyName' => (string) (restaurant()->name ?? ''),
        ])->setPaper('A4', 'landscape');

        $filename = 'finance-report-' . $this->startDate . '_to_' . $this->endDate . '.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $filename);
    }

    // ──────────────────────────────────────────────
    //  Detailed Income & Expenses
    // ──────────────────────────────────────────────
    public function getDetailedIncomeProperty(): \Illuminate\Support\Collection
    {
        return Reservation::with(['guest', 'room.roomType', 'charges'])
            ->where('branch_id', branch()->id)
            ->whereBetween('check_in_date', [$this->startDate, $this->endDate])
            ->whereNotIn('status', [Reservation::STATUS_CANCELLED, Reservation::STATUS_NO_SHOW])
            ->orderBy('check_in_date', 'desc')
            ->get();
    }

    public function getDetailedIncomeRowsProperty(): \Illuminate\Support\Collection
    {
        $rows = collect();
        $currencyId = restaurant()->currency_id;

        foreach ($this->detailedIncome as $res) {
            $chargeSummary = [];
            foreach ($res->charges as $c) {
                $typeLabel = '';
                if ($c->charge_type === RoomCharge::TYPE_ROOM_NIGHT) {
                    $typeLabel = 'Room Charge';
                } elseif ($c->charge_type === RoomCharge::TYPE_LAUNDRY) {
                    $typeLabel = 'Laundry';
                } elseif ($c->charge_type === RoomCharge::TYPE_MINIBAR) {
                    $typeLabel = 'Minibar';
                } else {
                    $typeLabel = ucwords(str_replace('_', ' ', $c->charge_type));
                }
                if (!isset($chargeSummary[$typeLabel])) {
                    $chargeSummary[$typeLabel] = 0.0;
                }
                $chargeSummary[$typeLabel] += (float)$c->amount;
            }

            $paid = (float)$res->paid_amount;
            $unpaid = (float)$res->balance_due;
            $total = (float)$res->total_amount;

            foreach ($chargeSummary as $label => $amount) {
                $ratio = $total > 0 ? ($amount / $total) : 0;
                $chargePaid = $paid * $ratio;
                $chargeUnpaid = $unpaid * $ratio;

                $rows->push((object)[
                    'date' => $res->check_in_date,
                    'reservation_number' => $res->reservation_number,
                    'guest_name' => $res->guest?->name ?? '—',
                    'room_number' => $res->room?->room_number ?? '—',
                    'room_type' => $res->room?->roomType?->name ?? '—',
                    'charge_details' => $label,
                    'amount' => $amount,
                    'paid' => $chargePaid,
                    'unpaid' => $chargeUnpaid,
                ]);
            }
        }
        return $rows;
    }

    public function getDetailedExpensesProperty(): \Illuminate\Support\Collection
    {
        $expenses = HotelExpense::whereIn('status', ['paid', 'pending'])
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->get();

        // Add restaurant dues (orders billed to active reservations in this range)
        $branchId = branch()->id;
        $reservations = Reservation::with(['charges', 'room'])
            ->where('branch_id', $branchId)
            ->whereBetween('check_in_date', [$this->startDate, $this->endDate])
            ->whereNotIn('status', [Reservation::STATUS_CANCELLED, Reservation::STATUS_NO_SHOW])
            ->get();

        foreach ($reservations as $res) {
            $restaurantCharges = $res->charges->where('charge_type', RoomCharge::TYPE_RESTAURANT);
            foreach ($restaurantCharges as $charge) {
                $isPaid = $res->balance_due <= 0;
                $pseudoExpense = new HotelExpense([
                    'expense_date' => $res->check_in_date,
                    'title' => 'Restaurant Folio Transfer: ' . $res->reservation_number,
                    'description' => 'Restaurant order charged to room ' . ($res->room?->room_number ?? ''),
                    'amount' => $charge->amount,
                    'department' => 'restaurant',
                    'payment_method' => 'room_folio',
                    'status' => $isPaid ? 'paid' : 'pending',
                ]);
                $pseudoExpense->id = 999000 + $charge->id;
                $expenses->push($pseudoExpense);
            }
        }

        return $expenses->sortByDesc('expense_date');
    }

    public function getCashFlowSummaryProperty(): array
    {
        $branchId = branch()->id;
        $from = $this->startDate . ' 00:00:00';
        $to   = $this->endDate   . ' 23:59:59';

        $payments = HotelPayment::where('branch_id', $branchId)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $totalInflow = (float) $payments->where('payment_type', '!=', HotelPayment::TYPE_REFUND)->sum('amount') 
                       - (float) $payments->where('payment_type', HotelPayment::TYPE_REFUND)->sum('amount');

        $totalOutflow = (float) $this->detailedExpenses->where('status', 'paid')->sum('amount');

        return [
            'totalInflow' => $totalInflow,
            'totalOutflow' => $totalOutflow,
            'netCashFlow' => $totalInflow - $totalOutflow,
        ];
    }

    public function getDetailedCashInflowProperty(): \Illuminate\Support\Collection
    {
        $branchId = branch()->id;
        $from = $this->startDate . ' 00:00:00';
        $to   = $this->endDate   . ' 23:59:59';

        $payments = HotelPayment::with(['reservation.guest', 'reservation.room.roomType'])
            ->where('branch_id', $branchId)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $charges = RoomCharge::with(['reservation.guest', 'reservation.room.roomType'])
            ->where('branch_id', $branchId)
            ->whereBetween('charge_date', [$from, $to])
            ->get();

        $ledger = collect();

        foreach ($payments as $payment) {
            $res = $payment->reservation;
            $details = ucwords(str_replace('_', ' ', $payment->payment_method));
            if ($payment->reference_number) {
                $details .= ' (Ref: ' . $payment->reference_number . ')';
            }
            if ($payment->notes) {
                $details .= ' - ' . $payment->notes;
            }

            $amount = (float) $payment->amount;
            $isRefund = $payment->payment_type === HotelPayment::TYPE_REFUND;

            $ledger->push((object)[
                'date' => $payment->created_at,
                'reservation_number' => $res?->reservation_number ?? '—',
                'guest_name' => $res?->guest?->name ?? '—',
                'room_number' => $res?->room?->room_number ?? '—',
                'room_type' => $res?->room?->roomType?->name ?? '—',
                'description' => $details,
                'debit' => $isRefund ? $amount : 0.0,
                'credit' => !$isRefund ? $amount : 0.0,
                'type' => $isRefund ? 'Refund' : 'Payment',
                'badge' => $isRefund ? 'refund' : 'payment',
            ]);
        }

        foreach ($charges as $charge) {
            $res = $charge->reservation;
            
            $typeLabel = '';
            if ($charge->charge_type === RoomCharge::TYPE_ROOM_NIGHT) {
                $typeLabel = 'Room Charge';
            } elseif ($charge->charge_type === RoomCharge::TYPE_LAUNDRY) {
                $typeLabel = 'Laundry';
            } elseif ($charge->charge_type === RoomCharge::TYPE_MINIBAR) {
                $typeLabel = 'Minibar';
            } else {
                $typeLabel = ucwords(str_replace('_', ' ', $charge->charge_type));
            }
            if ($charge->description) {
                $typeLabel .= ' - ' . $charge->getDisplayDescription();
            }

            $ledger->push((object)[
                'date' => $charge->charge_date,
                'reservation_number' => $res?->reservation_number ?? '—',
                'guest_name' => $res?->guest?->name ?? '—',
                'room_number' => $res?->room?->room_number ?? '—',
                'room_type' => $res?->room?->roomType?->name ?? '—',
                'description' => $typeLabel,
                'debit' => (float)$charge->amount,
                'credit' => 0.0,
                'type' => 'Charge',
                'badge' => 'charge',
            ]);
        }

        return $ledger->sortByDesc('date');
    }

    public function getDetailedCashOutflowProperty(): \Illuminate\Support\Collection
    {
        return $this->detailedExpenses->where('status', 'paid');
    }

    public function render()
    {
        return view('hotel::livewire.reports.unified-finance-report', [
            'summary'             => $this->summary,
            'dailyBreakdown'      => $this->dailyBreakdown,
            'detailedIncome'      => $this->detailedIncomeRows,
            'detailedExpenses'    => $this->detailedExpenses,
            'cashFlowSummary'     => $this->cashFlowSummary,
            'detailedCashInflow'  => $this->detailedCashInflow,
            'detailedCashOutflow' => $this->detailedCashOutflow,
            'currencyId'          => restaurant()->currency_id,
            'activeTab'           => $this->activeTab,
            'showIncomeDetails'   => $this->showIncomeDetails,
            'showExpenseDetails'  => $this->showExpenseDetails,
            'showCashInflowDetails' => $this->showCashInflowDetails,
            'showCashOutflowDetails' => $this->showCashOutflowDetails,
        ])->layout('layouts.app');
    }
}
