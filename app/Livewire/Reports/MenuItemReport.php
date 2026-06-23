<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\MenuItem;
use App\Models\ItemCategory;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class MenuItemReport extends Component
{
    use WithPagination;

    public $dateRangeType = 'currentWeek';
    public $startDate;
    public $endDate;
    public $startTime = '00:00';
    public $endTime   = '23:59';
    public $searchTerm = '';
    public $filterCategoryId = '';
    public $categories = [];

    // Sales detail modal (Alpine-driven — no round-trip)
    // Data is embedded in the page at render time

    public function mount()
    {
        abort_if(!in_array('Report', restaurant_modules()), 403);
        abort_if(!user_can('Show Reports'), 403);

        $this->dateRangeType = request()->cookie('menu_item_report_date_range_type', 'currentWeek');
        $this->setDateRange();

        $this->categories = ItemCategory::where('branch_id', branch()->id)
            ->orderBy('category_name')
            ->get();
    }

    public function updatedDateRangeType($value)
    {
        cookie()->queue(cookie('menu_item_report_date_range_type', $value, 60 * 24 * 30));
        $this->resetPage();
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function updatedFilterCategoryId()
    {
        $this->resetPage();
    }

    public function setDateRange()
    {
        $ranges = [
            'today'        => [now()->startOfDay(),              now()->endOfDay()],
            'yesterday'    => [now()->subDay()->startOfDay(),    now()->subDay()->endOfDay()],
            'currentWeek'  => [now()->startOfWeek(),             now()->endOfWeek()],
            'lastWeek'     => [now()->subWeek()->startOfWeek(),  now()->subWeek()->endOfWeek()],
            'last7Days'    => [now()->subDays(7),                now()->endOfDay()],
            'currentMonth' => [now()->startOfMonth(),            now()->endOfDay()],
            'lastMonth'    => [now()->subMonth()->startOfMonth(),now()->subMonth()->endOfMonth()],
            'currentYear'  => [now()->startOfYear(),             now()->endOfDay()],
            'lastYear'     => [now()->subYear()->startOfYear(),  now()->subYear()->endOfYear()],
        ];

        [$start, $end] = $ranges[$this->dateRangeType] ?? $ranges['currentWeek'];
        $this->startDate = $start->format('m/d/Y');
        $this->endDate   = $end->format('m/d/Y');
        $this->resetPage();
    }

    #[On('setStartDate')]
    public function setStartDate($start)
    {
        $this->startDate = $start;
        $this->resetPage();
    }

    #[On('setEndDate')]
    public function setEndDate($end)
    {
        $this->endDate = $end;
        $this->resetPage();
    }

    private function prepareDateTimeData(): array
    {
        $timezone = timezone();
        $startFallback = now($timezone)->startOfDay();
        $endFallback   = now($timezone)->endOfDay();

        $startDateTime = $this->parseDateTimeOrFallback($this->startDate, $this->startTime, $timezone, $startFallback);
        $endDateTime   = $this->parseDateTimeOrFallback($this->endDate,   $this->endTime,   $timezone, $endFallback);
        $startTime     = $this->normalizeTime($this->startTime, $timezone, '00:00');
        $endTime       = $this->normalizeTime($this->endTime,   $timezone, '23:59');

        return compact('timezone', 'startDateTime', 'endDateTime', 'startTime', 'endTime');
    }

    private function parseDateTimeOrFallback($date, $time, $timezone, Carbon $fallback): string
    {
        $date = trim((string) $date);
        $time = trim((string) $time);
        if ($date === '' || $time === '') {
            return $fallback->toDateTimeString();
        }
        try {
            return Carbon::createFromFormat('m/d/Y H:i', "{$date} {$time}", $timezone)->toDateTimeString();
        } catch (\Throwable) {
            return $fallback->toDateTimeString();
        }
    }

    private function normalizeTime($time, $timezone, string $fallback): string
    {
        try {
            return Carbon::parse($time, $timezone)->format('H:i');
        } catch (\Throwable) {
            return $fallback;
        }
    }

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
        $dt = $this->prepareDateTimeData();

        // ── All menu items for this branch (paginated) ──────────────────────
        $itemsQuery = MenuItem::withoutGlobalScopes()
            ->where('menu_items.branch_id', branch()->id)
            ->leftJoin('item_categories', 'item_categories.id', '=', 'menu_items.item_category_id')
            ->select(
                'menu_items.id',
                'menu_items.item_name',
                'menu_items.item_code',
                'menu_items.price',
                'menu_items.is_available',
                'item_categories.category_name',
            );

        if ($this->searchTerm) {
            $itemsQuery->where(function ($q) {
                $q->where('menu_items.item_name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('menu_items.item_code', 'like', '%' . $this->searchTerm . '%');
            });
        }

        if ($this->filterCategoryId) {
            $itemsQuery->where('menu_items.item_category_id', $this->filterCategoryId);
        }

        $items = $itemsQuery->orderBy('menu_items.item_name')->paginate(15);

        // ── Sales summary per item within the selected date range ────────────
        $menuItemIds = $items->pluck('id');

        $salesByItem = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.menu_item_id', $menuItemIds)
            ->where('orders.status', 'paid')
            ->whereBetween('orders.date_time', [$dt['startDateTime'], $dt['endDateTime']])
            ->where(function ($q) use ($dt) {
                if ($dt['startTime'] < $dt['endTime']) {
                    $q->whereRaw('TIME(orders.date_time) BETWEEN ? AND ?', [$dt['startTime'], $dt['endTime']]);
                } else {
                    $q->where(function ($sub) use ($dt) {
                        $sub->whereRaw('TIME(orders.date_time) >= ?', [$dt['startTime']])
                            ->orWhereRaw('TIME(orders.date_time) <= ?', [$dt['endTime']]);
                    });
                }
            })
            ->select(
                'order_items.menu_item_id',
                DB::raw('SUM(order_items.quantity) as qty_sold'),
                DB::raw('SUM(order_items.amount)   as revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
            )
            ->groupBy('order_items.menu_item_id')
            ->get()
            ->keyBy('menu_item_id');

        // ── Sales detail rows per item (for the Alpine modal) ────────────────
        $salesDetailByItem = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.menu_item_id', $menuItemIds)
            ->where('orders.status', 'paid')
            ->whereBetween('orders.date_time', [$dt['startDateTime'], $dt['endDateTime']])
            ->where(function ($q) use ($dt) {
                if ($dt['startTime'] < $dt['endTime']) {
                    $q->whereRaw('TIME(orders.date_time) BETWEEN ? AND ?', [$dt['startTime'], $dt['endTime']]);
                } else {
                    $q->where(function ($sub) use ($dt) {
                        $sub->whereRaw('TIME(orders.date_time) >= ?', [$dt['startTime']])
                            ->orWhereRaw('TIME(orders.date_time) <= ?', [$dt['endTime']]);
                    });
                }
            })
            ->select(
                'order_items.menu_item_id',
                'orders.id as order_id',
                'orders.order_number',
                'orders.date_time',
                'order_items.quantity',
                'order_items.price',
                'order_items.amount',
            )
            ->orderBy('orders.date_time', 'desc')
            ->get()
            ->groupBy('menu_item_id')
            ->map(fn($rows) => $rows->map(fn($r) => [
                'order_id'     => $r->order_id,
                'order_number' => $r->order_number,
                'date'         => \Carbon\Carbon::parse($r->date_time)->format('M d, Y h:i A'),
                'qty'          => $r->quantity,
                'price'        => currency_format($r->price, restaurant()->currency_id),
                'amount'       => currency_format($r->amount, restaurant()->currency_id),
            ])->values());

        // Translate category names
        $items->getCollection()->transform(function ($item) {
            $item->category_name = $this->getTranslatedText($item->category_name ?? '');
            return $item;
        });

        // Overall totals for the stat cards (across all pages, not just current page)
        $totals = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items as mi', 'mi.id', '=', 'order_items.menu_item_id')
            ->where('mi.branch_id', branch()->id)
            ->where('orders.status', 'paid')
            ->whereBetween('orders.date_time', [$dt['startDateTime'], $dt['endDateTime']])
            ->where(function ($q) use ($dt) {
                if ($dt['startTime'] < $dt['endTime']) {
                    $q->whereRaw('TIME(orders.date_time) BETWEEN ? AND ?', [$dt['startTime'], $dt['endTime']]);
                } else {
                    $q->where(function ($sub) use ($dt) {
                        $sub->whereRaw('TIME(orders.date_time) >= ?', [$dt['startTime']])
                            ->orWhereRaw('TIME(orders.date_time) <= ?', [$dt['endTime']]);
                    });
                }
            })
            ->selectRaw('SUM(order_items.quantity) as total_qty, SUM(order_items.amount) as total_revenue')
            ->first();

        return view('livewire.reports.menu-item-report', [
            'items'             => $items,
            'salesByItem'       => $salesByItem,
            'salesDetailByItem' => $salesDetailByItem,
            'totalRevenue'      => $totals->total_revenue ?? 0,
            'totalQtySold'      => $totals->total_qty ?? 0,
            'startDate'         => $this->startDate,
            'endDate'           => $this->endDate,
            'startTime'         => $dt['startTime'],
            'endTime'           => $dt['endTime'],
        ]);
    }
}
