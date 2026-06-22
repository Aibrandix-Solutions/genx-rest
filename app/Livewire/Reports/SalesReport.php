<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\Tax;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\RestaurantCharge;
use App\Exports\SalesReportExport;
use App\Services\SalesReportData;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PaymentGatewayCredential;
use App\Models\User;

class SalesReport extends Component
{
    public $dateRangeType = 'currentWeek';

    public $startDate;

    public $endDate;

    public $startTime = '00:00';

    public $endTime = '23:59';

    public $currencyId;

    public $filterByWaiter = '';

    public $waiters = [];

    public $selectedWaiter = '';

    public function mount()
    {
        abort_unless(in_array('Report', restaurant_modules()), 403);
        abort_unless(user_can('Show Reports'), 403);

        $this->currencyId = restaurant()->currency_id;
        $this->dateRangeType = request()->cookie('sales_report_date_range_type', 'currentWeek');

        if ($this->dateRangeType === 'custom') {
            $defaultStartDate = now()->format('m/d/Y');
            $defaultEndDate = now()->format('m/d/Y');
            $defaultStartTime = '00:00';
            $defaultEndTime = '23:59';

            $cookieStartDate = request()->cookie('sales_report_start_date', $defaultStartDate);
            $cookieEndDate = request()->cookie('sales_report_end_date', $defaultEndDate);
            $cookieStartTime = request()->cookie('sales_report_start_time', $defaultStartTime);
            $cookieEndTime = request()->cookie('sales_report_end_time', $defaultEndTime);

            $this->startDate = $this->isValidDateFormat($cookieStartDate) ? $cookieStartDate : $defaultStartDate;
            $this->endDate = $this->isValidDateFormat($cookieEndDate) ? $cookieEndDate : $defaultEndDate;
            $this->startTime = $this->isValidTimeFormat($cookieStartTime) ? $cookieStartTime : $defaultStartTime;
            $this->endTime = $this->isValidTimeFormat($cookieEndTime) ? $cookieEndTime : $defaultEndTime;
        } else {
            $this->setDateRange();
        }

        $this->waiters = User::whereHas('roles', function ($query) {
            $query->where('name', 'Waiter_' . restaurant()->id);
        })->get();

        $this->selectedWaiter = '';
    }

    public function setDateRange()
    {
        if ($this->dateRangeType === 'custom') {
            return;
        }

        $ranges = [
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'lastWeek' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'last7Days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'currentMonth' => [now()->startOfMonth(), now()->endOfMonth()],
            'lastMonth' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'currentYear' => [now()->startOfYear(), now()->endOfYear()],
            'lastYear' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            'currentWeek' => [now()->startOfWeek(), now()->endOfWeek()],
        ];

        [$start, $end] = $ranges[$this->dateRangeType] ?? $ranges['currentWeek'];
        $this->startDate = $start->format('m/d/Y');
        $this->endDate = $end->format('m/d/Y');
        $this->filterByWaiter = '';
        $this->persistDateCookies();
    }

    private function persistDateCookies(): void
    {
        $ttl = 60 * 24 * 30;
        cookie()->queue(cookie('sales_report_date_range_type', $this->dateRangeType, $ttl));
        cookie()->queue(cookie('sales_report_start_date', $this->startDate, $ttl));
        cookie()->queue(cookie('sales_report_end_date', $this->endDate, $ttl));
        cookie()->queue(cookie('sales_report_start_time', $this->startTime, $ttl));
        cookie()->queue(cookie('sales_report_end_time', $this->endTime, $ttl));
    }

    private function isValidDateFormat(string $value, string $format = 'm/d/Y'): bool
    {
        $parsed = \DateTime::createFromFormat($format, $value);

        return $parsed !== false && $parsed->format($format) === $value;
    }

    private function isValidTimeFormat(string $value, string $format = 'H:i'): bool
    {
        $parsed = \DateTime::createFromFormat($format, $value);

        return $parsed !== false && $parsed->format($format) === $value;
    }

    private function markCustomDateRange(): void
    {
        $this->dateRangeType = 'custom';
        $this->persistDateCookies();
    }

    #[On('setStartDate')]
    public function setStartDate($start)
    {
        $this->startDate = $start;
        $this->markCustomDateRange();
    }

    #[On('setEndDate')]
    public function setEndDate($end)
    {
        $this->endDate = $end;
        $this->markCustomDateRange();
    }

    public function updatedStartDate(): void
    {
        $this->markCustomDateRange();
    }

    public function updatedEndDate(): void
    {
        $this->markCustomDateRange();
    }

    public function updatedStartTime(): void
    {
        $this->markCustomDateRange();
    }

    public function updatedEndTime(): void
    {
        $this->markCustomDateRange();
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

    public function exportReport()
    {
        if (! in_array('Export Report', restaurant_modules())) {
            $this->dispatch('showUpgradeLicense');

            return;
        }

        $dateTimeData = $this->prepareDateTimeData();

        return Excel::download(
            new SalesReportExport(
                $dateTimeData['startDateTime'],
                $dateTimeData['endDateTime'],
                $dateTimeData['startTime'],
                $dateTimeData['endTime'],
                $dateTimeData['timezone'],
            ),
            'sales-report-' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    public function updatedDateRangeType($value)
    {
        cookie()->queue(cookie('sales_report_date_range_type', $value, 60 * 24 * 30));

        if ($value !== 'custom') {
            $this->setDateRange();
        }
    }

    public function filterWaiter()
    {
        $this->filterByWaiter = $this->selectedWaiter;
    }

    public function render()
    {
        $dateTimeData = $this->prepareDateTimeData();

        $charges = RestaurantCharge::all();
        $taxes = Tax::all();
        $restaurant = restaurant();
        $taxMode = $restaurant->tax_mode ?? 'order';
        $waiterId = $this->filterByWaiter ? (int) $this->filterByWaiter : null;

        $dailyRows = SalesReportData::fetchDailyAggregates($dateTimeData, $waiterId);
        $outstandingData = SalesReportData::fetchOutstandingByDate($dateTimeData, $waiterId);

        $groupedData = $dailyRows->map(function ($item) use ($charges, $taxes, $outstandingData) {
            $outstandingInfo = $outstandingData->get($item->date);
            $chargeAmounts = [];

            foreach ($charges as $charge) {
                $chargeAmounts[$charge->charge_name] = DB::table('order_charges')
                    ->join('orders', 'order_charges.order_id', '=', 'orders.id')
                    ->join('restaurant_charges', 'order_charges.charge_id', '=', 'restaurant_charges.id')
                    ->where('order_charges.charge_id', $charge->id)
                    ->whereIn('orders.status', SalesReportData::reportOrderStatuses())
                    ->whereDate('orders.date_time', $item->date)
                    ->where('orders.branch_id', branch()->id)
                    ->sum(DB::raw('CASE WHEN restaurant_charges.charge_type = "percent"
                THEN (restaurant_charges.charge_value / 100) * GREATEST(0, (orders.sub_total + COALESCE((SELECT SUM(amount) FROM order_extras WHERE order_extras.order_id = orders.id), 0)) - COALESCE(orders.discount_amount, 0))
                ELSE restaurant_charges.charge_value END')) ?? 0;
            }

            $taxAmounts = [];
            $totalTaxAmount = 0;
            $taxDetails = [];

            foreach ($taxes as $tax) {
                $taxAmounts[$tax->tax_name] = 0;
                $taxDetails[$tax->tax_name] = [
                    'name' => $tax->tax_name,
                    'percent' => $tax->tax_percent,
                    'total_amount' => 0,
                    'items_count' => 0,
                ];
            }

            $itemTaxData = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                ->join('menu_item_tax', 'menu_items.id', '=', 'menu_item_tax.menu_item_id')
                ->join('taxes', 'menu_item_tax.tax_id', '=', 'taxes.id')
                ->whereIn('orders.status', SalesReportData::reportOrderStatuses())
                ->where('orders.branch_id', branch()->id)
                ->whereDate('orders.date_time', $item->date)
                ->select(
                    'taxes.tax_name',
                    'taxes.tax_percent',
                    'order_items.tax_amount',
                    'order_items.quantity',
                    'order_items.order_id',
                    'menu_items.id as menu_item_id'
                )
                ->get();

            if ($itemTaxData->isNotEmpty()) {
                $orderItemGroups = $itemTaxData->groupBy(['order_id', 'menu_item_id']);

                foreach ($orderItemGroups as $menuItems) {
                    foreach ($menuItems as $itemTaxes) {
                        $totalTaxPercent = $itemTaxes->sum('tax_percent');
                        $orderItemTaxAmount = $itemTaxes->first()->tax_amount ?? 0;

                        foreach ($itemTaxes as $taxItem) {
                            $taxName = $taxItem->tax_name;
                            $taxPercent = $taxItem->tax_percent;
                            $proportionalAmount = $totalTaxPercent > 0
                                ? ($orderItemTaxAmount * ($taxPercent / $totalTaxPercent))
                                : 0;

                            $taxAmounts[$taxName] += $proportionalAmount;
                            $taxDetails[$taxName]['total_amount'] += $proportionalAmount;
                            $taxDetails[$taxName]['items_count'] += $taxItem->quantity;
                        }
                    }
                }
            }

            $orderTaxData = DB::table('order_taxes')
                ->join('orders', 'order_taxes.order_id', '=', 'orders.id')
                ->join('taxes', 'order_taxes.tax_id', '=', 'taxes.id')
                ->whereIn('orders.status', SalesReportData::reportOrderStatuses())
                ->where('orders.branch_id', branch()->id)
                ->whereDate('orders.date_time', $item->date)
                ->select(
                    'taxes.tax_name',
                    'taxes.tax_percent',
                    'orders.sub_total',
                    'orders.discount_amount',
                    'orders.id as order_id'
                )
                ->get();

            if ($orderTaxData->isNotEmpty()) {
                foreach ($orderTaxData as $orderTax) {
                    $taxName = $orderTax->tax_name;
                    $taxAmount = ($orderTax->tax_percent / 100) * ($orderTax->sub_total - ($orderTax->discount_amount ?? 0));

                    $taxAmounts[$taxName] += $taxAmount;
                    $taxDetails[$taxName]['total_amount'] += $taxAmount;
                    $taxDetails[$taxName]['items_count'] += 1;
                }
            }

            if ($itemTaxData->isEmpty() && $orderTaxData->isEmpty()) {
                foreach ($taxes as $tax) {
                    $itemTaxAmount = DB::table('order_items')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->join('menu_item_tax', 'order_items.menu_item_id', '=', 'menu_item_tax.menu_item_id')
                        ->join('taxes', 'menu_item_tax.tax_id', '=', 'taxes.id')
                        ->where('taxes.id', $tax->id)
                        ->whereIn('orders.status', SalesReportData::reportOrderStatuses())
                        ->where('orders.branch_id', branch()->id)
                        ->whereDate('orders.date_time', $item->date)
                        ->sum(DB::raw('
                            CASE
                                WHEN (SELECT COUNT(*) FROM menu_item_tax WHERE menu_item_id = order_items.menu_item_id) > 1
                                THEN (order_items.tax_amount * (taxes.tax_percent /
                                    (SELECT SUM(t.tax_percent) FROM menu_item_tax mit
                                    JOIN taxes t ON mit.tax_id = t.id
                                    WHERE mit.menu_item_id = order_items.menu_item_id)
                                ))
                                ELSE COALESCE(order_items.tax_amount, 0)
                            END
                        ')) ?? 0;

                    $taxAmounts[$tax->tax_name] += $itemTaxAmount;
                    $taxDetails[$tax->tax_name]['total_amount'] += $itemTaxAmount;
                }
            }

            $totalTaxAmount = array_sum($taxAmounts);
            $ordersTotal = $item->orders_total ?? 0;

            return [
                'date' => $item->date,
                'total_orders' => $item->total_orders,
                'total_amount' => $ordersTotal,
                'total_excluding_tip' => $ordersTotal - ($item->tip_amount ?? 0),
                'discount_amount' => $item->discount_amount ?? 0,
                'tip_amount' => $item->tip_amount ?? 0,
                'delivery_fee' => $item->delivery_fee ?? 0,
                'cash_amount' => $item->cash_amount ?? 0,
                'card_amount' => $item->card_amount ?? 0,
                'upi_amount' => $item->upi_amount ?? 0,
                'bank_transfer_amount' => $item->bank_transfer_amount ?? 0,
                'razorpay_amount' => $item->razorpay_amount ?? 0,
                'stripe_amount' => $item->stripe_amount ?? 0,
                'flutterwave_amount' => $item->flutterwave_amount ?? 0,
                'outstanding_orders' => $outstandingInfo->outstanding_orders ?? 0,
                'outstanding_amount' => $outstandingInfo->outstanding_amount ?? 0,
                'charges' => $chargeAmounts,
                'taxes' => $taxAmounts,
                'tax_details' => $taxDetails,
                'total_tax_amount' => $totalTaxAmount,
            ];
        });

        $allTaxes = [];
        foreach ($groupedData as $item) {
            if (isset($item['tax_details']) && is_array($item['tax_details'])) {
                foreach ($item['tax_details'] as $taxName => $taxDetail) {
                    if (! isset($allTaxes[$taxName])) {
                        $allTaxes[$taxName] = [
                            'name' => $taxName,
                            'percent' => $taxDetail['percent'] ?? 0,
                            'total_amount' => 0,
                            'items_count' => 0,
                        ];
                    }
                    $allTaxes[$taxName]['total_amount'] += $taxDetail['total_amount'] ?? 0;
                    $allTaxes[$taxName]['items_count'] += $taxDetail['items_count'] ?? 0;
                }
            } elseif (isset($item['taxes']) && is_array($item['taxes'])) {
                foreach ($item['taxes'] as $taxName => $taxAmount) {
                    if (! isset($allTaxes[$taxName])) {
                        $taxPercent = $taxes->where('tax_name', $taxName)->first()->tax_percent ?? 0;
                        $allTaxes[$taxName] = [
                            'name' => $taxName,
                            'percent' => $taxPercent,
                            'total_amount' => 0,
                            'items_count' => 1,
                        ];
                    }
                    $allTaxes[$taxName]['total_amount'] += $taxAmount;
                }
            }
        }

        $paymentGateway = PaymentGatewayCredential::select('stripe_status', 'razorpay_status', 'flutterwave_status')
            ->where('restaurant_id', restaurant()->id)
            ->first();

        return view('livewire.reports.sales-report', [
            'menuItems' => $groupedData,
            'charges' => $charges,
            'taxes' => $taxes,
            'paymentGateway' => $paymentGateway,
            'taxMode' => $taxMode,
            'allTaxes' => $allTaxes,
            'currencyId' => $this->currencyId,
            'waiters' => $this->waiters,
            'filterByWaiter' => $this->filterByWaiter,
        ]);
    }
}
