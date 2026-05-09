<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Exports\ItemReportExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ItemReport extends Component
{

    public $dateRangeType;
    public $startDate;
    public $endDate;
    public $startTime = '00:00'; // Default start time
    public $endTime = '23:59';  // Default end time
    public $searchTerm;

    public function mount()
    {
        abort_if(!in_array('Report', restaurant_modules()), 403);
        abort_if((!user_can('Show Reports')), 403);

        $this->dateRangeType = request()->cookie('item_report_date_range_type', 'currentWeek');
        $this->setDateRange();
    }

    public function updatedDateRangeType($value)
    {
        cookie()->queue(cookie('item_report_date_range_type', $value, 60 * 24 * 30)); // 30 days
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

    public function exportReport()
    {
        if (!in_array('Export Report', restaurant_modules())) {
            $this->dispatch('showUpgradeLicense');
        } else {
            $data = $this->prepareDateTimeData();

            return Excel::download(
                new ItemReportExport($data['startDateTime'], $data['endDateTime'], $data['startTime'], $data['endTime'], $data['timezone'], $this->searchTerm),
                'item-report-' . now()->toDateTimeString() . '.xlsx'
            );
        }
    }

    private function prepareDateTimeData()
    {
        $timezone = timezone();

        $startFallback = now($timezone)->startOfDay();
        $endFallback = now($timezone)->endOfDay();

        $startDateTime = $this->parseDateTimeOrFallback($this->startDate, $this->startTime, $timezone, $startFallback);
        $endDateTime = $this->parseDateTimeOrFallback($this->endDate, $this->endTime, $timezone, $endFallback);

        $startTime = $this->normalizeTime($this->startTime, $timezone, '00:00');
        $endTime = $this->normalizeTime($this->endTime, $timezone, '23:59');

        return compact('timezone', 'startDateTime', 'endDateTime', 'startTime', 'endTime');
    }

    private function parseDateTimeOrFallback($date, $time, $timezone, Carbon $fallback): string
    {
        $date = trim((string)$date);
        $time = trim((string)$time);

        if ($date === '' || $time === '') {
            return $fallback->toDateTimeString();
        }

        try {
            return Carbon::createFromFormat('m/d/Y H:i', "{$date} {$time}", $timezone)
                ->toDateTimeString();
        } catch (\Throwable $e) {
            return $fallback->toDateTimeString();
        }
    }

    private function normalizeTime($time, $timezone, string $fallback): string
    {
        try {
            return Carbon::parse($time, $timezone)->format('H:i');
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    /**
     * Convert a translatable JSON value into the active locale text.
     */
    private function getTranslatedText($value): string
    {
        if (is_array($value)) {
            $translations = $value;
        } else {
            $decoded = json_decode((string) $value, true);
            $translations = is_array($decoded) ? $decoded : null;
        }

        if (!$translations) {
            return (string) ($value ?? '');
        }

        $locale = app()->getLocale();

        return (string) (
            $translations[$locale]
            ?? $translations['en']
            ?? $translations['eng']
            ?? reset($translations)
            ?? ''
        );
    }

    public function render()
    {
        $dateTimeData = $this->prepareDateTimeData();

        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->leftJoin('menu_item_variations', 'menu_item_variations.id', '=', 'order_items.menu_item_variation_id')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'menu_items.item_category_id')
            ->join('branches', 'branches.id', '=', 'orders.branch_id')
            ->where('branches.restaurant_id', restaurant()->id)
            ->whereBetween('orders.date_time', [$dateTimeData['startDateTime'], $dateTimeData['endDateTime']])
            ->where('orders.status', 'paid')
            ->where(function ($q) use ($dateTimeData) {
                if ($dateTimeData['startTime'] < $dateTimeData['endTime']) {
                    $q->whereRaw('TIME(orders.date_time) BETWEEN ? AND ?', [$dateTimeData['startTime'], $dateTimeData['endTime']]);
                } else {
                    $q->where(function ($sub) use ($dateTimeData) {
                        $sub->whereRaw('TIME(orders.date_time) >= ?', [$dateTimeData['startTime']])
                            ->orWhereRaw('TIME(orders.date_time) <= ?', [$dateTimeData['endTime']]);
                    });
                }
            });

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('menu_items.item_name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('item_categories.category_name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('menu_item_variations.variation', 'like', '%' . $this->searchTerm . '%');
            });
        }

        $reportRows = $query
            ->select(
                'order_items.menu_item_id',
                'order_items.menu_item_variation_id',
                'menu_items.item_name',
                'item_categories.category_name',
                'menu_item_variations.variation',
                'order_items.price as sold_unit_price',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.amount) as total_revenue')
            )
            ->groupBy(
                'order_items.menu_item_id',
                'order_items.menu_item_variation_id',
                'menu_items.item_name',
                'item_categories.category_name',
                'menu_item_variations.variation',
                'order_items.price'
            )
            ->orderBy('menu_items.item_name')
            ->orderBy('menu_item_variations.variation')
            ->orderBy('order_items.price')
            ->get();

        $reportRows = $reportRows->map(function ($row) {
            $row->category_name = $this->getTranslatedText($row->category_name);
            return $row;
        });

        $totalRevenue = $reportRows->sum('total_revenue');
        $totalQuantitySold = $reportRows->sum('quantity_sold');

        return view('livewire.reports.item-report', [
            'reportRows' => $reportRows,
            'totalRevenue' => $totalRevenue,
            'totalQuantitySold' => $totalQuantitySold,
        ]);
    }

}
