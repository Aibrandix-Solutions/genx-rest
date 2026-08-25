<?php

namespace App\Livewire\Dashboard;

use App\Models\Order;
use App\Services\SalesReportData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class WeeklySalesChart extends Component
{
    public function render()
    {
        $startOfMonth = now()->startOfMonth()->startOfDay();
        $tillToday = now()->endOfDay();

        $startOfLastMonth = now()->copy()->subMonth()->startOfMonth()->startOfDay();
        $endOfLastMonth = now()->copy()->subMonth()->endOfMonth()->endOfDay();

        $statuses = SalesReportData::reportOrderStatuses();

        $salesByDate = Order::query()
            ->select(
                DB::raw('DATE(orders.date_time) as date'),
                DB::raw('SUM(orders.total) as total_sales')
            )
            ->whereBetween('orders.date_time', [
                $startOfMonth->toDateTimeString(),
                $tillToday->toDateTimeString(),
            ])
            ->whereIn('orders.status', $statuses)
            ->groupBy(DB::raw('DATE(orders.date_time)'))
            ->orderBy('date')
            ->pluck('total_sales', 'date');

        $salesData = $this->buildDailySeries($startOfMonth, now()->startOfDay(), $salesByDate);

        $monthlyEarnings = Order::query()
            ->whereBetween('orders.date_time', [
                $startOfMonth->toDateTimeString(),
                $tillToday->toDateTimeString(),
            ])
            ->whereIn('orders.status', $statuses)
            ->sum('total');

        $previousEarnings = Order::query()
            ->whereBetween('orders.date_time', [
                $startOfLastMonth->toDateTimeString(),
                $endOfLastMonth->toDateTimeString(),
            ])
            ->whereIn('orders.status', $statuses)
            ->sum('total');

        $orderDifference = ($monthlyEarnings - $previousEarnings);
        $percentChange = (($orderDifference / ($previousEarnings == 0 ? 1 : $previousEarnings)) * 100);

        $chartElementId = 'dashboard-weekly-sales-chart-'.$this->getId();

        $chartConfig = [
            'elementId' => $chartElementId,
            'categories' => $salesData
                ->map(fn ($row) => Carbon::parse($row->date)->translatedFormat('d M'))
                ->values()
                ->all(),
            'values' => $salesData->pluck('total_sales')->map(fn ($v) => (float) $v)->values()->all(),
            'seriesName' => __('modules.dashboard.earnings'),
            'color' => restaurant()->theme_hex,
            'currency' => currency(),
        ];

        return view('livewire.dashboard.weekly-sales-chart', [
            'salesData' => $salesData,
            'monthlyEarnings' => $monthlyEarnings,
            'percentChange' => $percentChange,
            'chartConfig' => $chartConfig,
            'chartElementId' => $chartElementId,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<string, mixed>  $salesByDate
     */
    private function buildDailySeries(Carbon $from, Carbon $to, Collection $salesByDate): Collection
    {
        $series = collect();

        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            $series->push((object) [
                'date' => $key,
                'total_sales' => (float) ($salesByDate[$key] ?? 0),
            ]);
        }

        return $series;
    }
}
