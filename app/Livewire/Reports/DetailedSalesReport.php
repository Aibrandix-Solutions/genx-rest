<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Order;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Models\RestaurantCharge;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PaymentGatewayCredential;
use App\Models\Payment;
use App\Models\User;
use App\Exports\DetailedSalesReportExport;

class DetailedSalesReport extends Component
{
    use WithPagination;

    public $dateRangeType = 'currentWeek';
    public $startDate;
    public $endDate;
    public $startTime = '00:00'; // Default start time
    public $endTime = '23:59';  // Default end time
    public $currencyId;
    public $filterByWaiter = '';
    public $waiters = [];
    public $selectedWaiter = '';
    public $search = '';
    public $perPage = 15;
    public $filterPaymentMethod = '';
    public $paymentMethods = [];

    public function mount()
    {
        abort_unless(in_array('Report', restaurant_modules()), 403);
        abort_unless(user_can('Show Reports'), 403);

        // Centralize currency ID
        $this->currencyId = restaurant()->currency_id;

        // Load date range type from cookie
        $this->dateRangeType = request()->cookie('detailed_sales_report_date_range_type', 'currentWeek');
        $this->setDateRange();
        // Populate waiters
        $this->waiters = User::whereHas('roles', function($query) {
            $query->where('name', 'Waiter_'.restaurant()->id);
        })->get();

        $this->selectedWaiter = '';

        // Load distinct payment methods
        $this->paymentMethods = Payment::select('payment_method')
            ->distinct()
            ->whereNotNull('payment_method')
            ->where('payment_method', '!=', 'due')
            ->pluck('payment_method')
            ->toArray();
    }

    public function setDateRange()
    {
        $ranges = [
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'lastWeek' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'last7Days' => [now()->subDays(7), now()->endOfDay()],
            'currentMonth' => [now()->startOfMonth(), now()->endOfDay()],
            'lastMonth' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'currentYear' => [now()->startOfYear(), now()->endOfDay()],
            'lastYear' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            'currentWeek' => [now()->startOfWeek(), now()->endOfWeek()],
        ];

        [$start, $end] = $ranges[$this->dateRangeType] ?? $ranges['currentWeek'];
        $this->startDate = $start->format('m/d/Y');
        $this->endDate = $end->format('m/d/Y');
        $this->filterByWaiter = '';
    }

    #[On('setStartDate')]
    public function setStartDate($start)
    {
        $this->startDate = $start;
    }

    #[On('setEndDate')]
    public function setEndDate($end)
    {
        $this->endDate = $end;
    }

    private function prepareDateTimeData()
    {
        $timezone = timezone();

        $startDateTime = Carbon::createFromFormat('m/d/Y H:i', $this->startDate . ' ' . $this->startTime, $timezone)
            ->toDateTimeString();

        $endDateTime = Carbon::createFromFormat('m/d/Y H:i', $this->endDate . ' ' . $this->endTime, $timezone)
            ->toDateTimeString();

        $startTime = Carbon::parse($this->startTime, $timezone)->format('H:i');
        $endTime = Carbon::parse($this->endTime, $timezone)->format('H:i');

        return compact('timezone', 'startDateTime', 'endDateTime', 'startTime', 'endTime');
    }

    public function updatedDateRangeType($value)
    {
        cookie()->queue(cookie('detailed_sales_report_date_range_type', $value, 60 * 24 * 30)); // 30 days
        $this->resetPage();
    }

    public function filterWaiter()
    {
        $this->filterByWaiter = $this->selectedWaiter;
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function exportReport()
    {
        if (!in_array('Export Report', restaurant_modules())) {
            $this->dispatch('showUpgradeLicense');
            return;
        }

        $dateTimeData = $this->prepareDateTimeData();

        return Excel::download(
            new DetailedSalesReportExport(
                $dateTimeData['startDateTime'],
                $dateTimeData['endDateTime'],
                $dateTimeData['startTime'],
                $dateTimeData['endTime'],
                $dateTimeData['timezone'],
                $this->filterByWaiter,
                $this->filterPaymentMethod
            ),
            'detailed-sales-report-' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    public function render()
    {
        $dateTimeData = $this->prepareDateTimeData();

        // Retrieve all taxes and charges for headers
        $charges = RestaurantCharge::all();
        $taxes = Tax::all();
        $restaurant = restaurant();
        $taxMode = $restaurant->tax_mode ?? 'order';

        // Get detailed sales report
        $query = Order::with(['payments', 'items', 'items.menuItem', 'waiter', 'customer'])
            ->whereBetween('orders.date_time', [$dateTimeData['startDateTime'], $dateTimeData['endDateTime']])
            ->whereIn('orders.status', ['paid', 'payment_due'])
            ->where(function ($q) use ($dateTimeData) {
                if ($dateTimeData['startTime'] < $dateTimeData['endTime']) {
                    $q->whereRaw('TIME(orders.date_time) BETWEEN ? AND ?', [$dateTimeData['startTime'], $dateTimeData['endTime']]);
                }
                else
                 {
                    $q->where(function ($sub) use ($dateTimeData) {
                        $sub->whereRaw('TIME(orders.date_time) >= ?', [$dateTimeData['startTime']])
                            ->orWhereRaw('TIME(orders.date_time) <= ?', [$dateTimeData['endTime']]);
                    });
                }
            });

        // Filter by waiter if selected
        if ($this->filterByWaiter) {
            $query->where('orders.waiter_id', $this->filterByWaiter);
        }

        // Filter by payment method if selected
        if ($this->filterPaymentMethod !== '') {
            if ($this->filterPaymentMethod === 'due') {
                $query->where('orders.status', 'payment_due')
                    ->whereDoesntHave('payments');
            } else {
                $query->whereHas('payments', function($q) {
                    $q->where('payment_method', $this->filterPaymentMethod);
                });
            }
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('orders.order_number', 'like', '%' . $this->search . '%')
                  ->orWhere('orders.id', 'like', '%' . $this->search . '%');
            });
        }

        $orders = $query->orderBy('orders.date_time', 'desc')->paginate($this->perPage);

        $paymentGateway = PaymentGatewayCredential::select('stripe_status', 'razorpay_status', 'flutterwave_status')
            ->where('restaurant_id', restaurant()->id)
            ->first();

        return view('livewire.reports.detailed-sales-report', [
            'orders' => $orders,
            'charges' => $charges,
            'taxes' => $taxes,
            'paymentGateway' => $paymentGateway,
            'taxMode' => $taxMode,
            'currencyId' => $this->currencyId,
            'waiters' => $this->waiters,
            'filterByWaiter' => $this->filterByWaiter,
            'paymentMethods' => $this->paymentMethods,
        ]);
    }
}

