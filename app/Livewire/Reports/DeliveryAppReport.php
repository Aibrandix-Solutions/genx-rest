<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\DeliveryPlatform;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use App\Models\OrderType;
use App\Livewire\Reports\Concerns\HasReportBranchFilter;
use App\Services\SalesReportData;

class DeliveryAppReport extends Component
{
    use HasReportBranchFilter;

    public $dateRangeType;
    public $startDate;
    public $endDate;
    public $startTime = '00:00';
    public $endTime = '23:59';
    public $searchTerm;
    public $selectedDeliveryApp = 'all';

    public function mount()
    {
        abort_if(!in_array('Report', restaurant_modules()), 403);
        abort_if((!user_can('Show Reports')), 403);

        $this->dateRangeType = request()->cookie('delivery_app_report_date_range_type', 'currentWeek');
        $this->setDateRange();
        $this->mountReportBranchFilter();
    }

    public function updatedDateRangeType($value)
    {
        cookie()->queue(cookie('delivery_app_report_date_range_type', $value, 60 * 24 * 30));
    }

    public function setDateRange()
    {
        switch ($this->dateRangeType) {
        case 'today':
            $this->startDate = now()->startOfDay()->format('m/d/Y');
            $this->endDate = now()->startOfDay()->format('m/d/Y');
            break;

        case 'yesterday':
            $this->startDate = now()->subDay()->startOfDay()->format('m/d/Y');
            $this->endDate = now()->subDay()->endOfDay()->format('m/d/Y');
            break;

        case 'lastWeek':
            $this->startDate = now()->subWeek()->startOfWeek()->format('m/d/Y');
            $this->endDate = now()->subWeek()->endOfWeek()->format('m/d/Y');
            break;

        case 'last7Days':
            $this->startDate = now()->subDays(7)->format('m/d/Y');
            $this->endDate = now()->startOfDay()->format('m/d/Y');
            break;

        case 'currentMonth':
            $this->startDate = now()->startOfMonth()->format('m/d/Y');
            $this->endDate = now()->startOfDay()->format('m/d/Y');
            break;

        case 'lastMonth':
            $this->startDate = now()->subMonth()->startOfMonth()->format('m/d/Y');
            $this->endDate = now()->subMonth()->endOfMonth()->format('m/d/Y');
            break;

        case 'currentYear':
            $this->startDate = now()->startOfYear()->format('m/d/Y');
            $this->endDate = now()->startOfDay()->format('m/d/Y');
            break;

        case 'lastYear':
            $this->startDate = now()->subYear()->startOfYear()->format('m/d/Y');
            $this->endDate = now()->subYear()->endOfYear()->format('m/d/Y');
            break;

        default:
            $this->startDate = now()->startOfWeek()->format('m/d/Y');
            $this->endDate = now()->endOfWeek()->format('m/d/Y');
            break;
        }
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

    public function render()
    {
        $tz = timezone();

        $start = Carbon::createFromFormat('m/d/Y', $this->startDate, $tz)
            ->startOfDay()
            ->toDateTimeString();

        $end = Carbon::createFromFormat('m/d/Y', $this->endDate, $tz)
            ->endOfDay()
            ->toDateTimeString();

        $deliveryApps = DeliveryPlatform::all();
        $deliveryOrderTypes = OrderType::where('slug', 'delivery')->first();

        $deliveryAppStats = SalesReportData::ordersBaseQuery($this->branchFilter)
            ->select(
                'delivery_app_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(sub_total) as total_revenue'),
                DB::raw('SUM(delivery_fee) as total_delivery_fees'),
                DB::raw('AVG(sub_total) as avg_order_value')
            )
            ->where('orders.date_time', '>=', $start)
            ->where('orders.date_time', '<=', $end)
            ->where('orders.status', 'paid')
            ->where('orders.order_type_id', $deliveryOrderTypes->id);

        if ($this->selectedDeliveryApp !== 'all') {
            if ($this->selectedDeliveryApp === 'direct') {
                $deliveryAppStats->whereNull('orders.delivery_app_id');
            } else {
                $deliveryAppStats->where('orders.delivery_app_id', $this->selectedDeliveryApp);
            }
        }

        $deliveryAppStats = $deliveryAppStats->groupBy('delivery_app_id')->get();

        $reportData = $deliveryAppStats->map(function ($stat) use ($deliveryApps) {
            $deliveryApp = $deliveryApps->firstWhere('id', $stat->delivery_app_id);

            if (!$deliveryApp && $stat->delivery_app_id === null) {
                return [
                    'delivery_app' => (object) [
                        'id' => null,
                        'name' => __('modules.report.directDelivery'),
                        'logo_url' => null,
                        'commission_type' => 'percent',
                        'commission_value' => 0,
                    ],
                    'total_orders' => $stat->total_orders,
                    'total_revenue' => $stat->total_revenue,
                    'total_delivery_fees' => $stat->total_delivery_fees,
                    'avg_order_value' => $stat->avg_order_value,
                    'commission' => 0,
                    'net_revenue' => $stat->total_revenue,
                    'is_direct' => true,
                ];
            }

            if (!$deliveryApp) {
                return null;
            }

            $commission = 0;
            if ($deliveryApp->commission_type === 'percent') {
                $commission = ($stat->total_revenue * $deliveryApp->commission_value) / 100;
            } else {
                $commission = $deliveryApp->commission_value * $stat->total_orders;
            }

            return [
                'delivery_app' => $deliveryApp,
                'total_orders' => $stat->total_orders,
                'total_revenue' => $stat->total_revenue,
                'total_delivery_fees' => $stat->total_delivery_fees,
                'avg_order_value' => $stat->avg_order_value,
                'commission' => $commission,
                'net_revenue' => $stat->total_revenue - $commission,
                'is_direct' => false,
            ];
        })->filter()->values();

        $totalOrders = $reportData->sum('total_orders');
        $totalRevenue = $reportData->sum('total_revenue');
        $totalCommission = $reportData->sum('commission');
        $totalDeliveryFees = $reportData->sum('total_delivery_fees');
        $netRevenue = $reportData->sum('net_revenue');

        return view('livewire.reports.delivery-app-report', [
            'deliveryApps' => $deliveryApps,
            'reportData' => $reportData,
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'totalCommission' => $totalCommission,
            'totalDeliveryFees' => $totalDeliveryFees,
            'netRevenue' => $netRevenue,
            'showBranchFilter' => $this->showBranchFilter(),
            'reportBranches' => $this->reportBranches(),
        ]);
    }
}