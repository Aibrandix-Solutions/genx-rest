<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Helper\Common;

class ItemReportExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected string $startDateTime, $endDateTime;
    protected string $startTime, $endTime, $timezone, $searchTerm;
    protected $headingDateTime, $headingEndDateTime, $headingStartTime, $headingEndTime;

    public function __construct(string $startDateTime, string $endDateTime, string $startTime, string $endTime, string $timezone, ?string $searchTerm = '')
    {
        $this->startDateTime = $startDateTime;
        $this->endDateTime = $endDateTime;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->timezone = $timezone;
        $this->searchTerm = $searchTerm ?? '';

        $this->headingDateTime = Carbon::parse($startDateTime)->setTimezone($timezone)->format('Y-m-d');
        $this->headingEndDateTime = Carbon::parse($endDateTime)->setTimezone($timezone)->format('Y-m-d');
        $this->headingStartTime = Carbon::parse($startTime)->setTimezone($timezone)->format('h:i A');
        $this->headingEndTime = Carbon::parse($endTime)->setTimezone($timezone)->format('h:i A');
    }

    public function headings(): array
    {
        $headingTitle = $this->headingDateTime === $this->headingEndDateTime
            ? __('modules.report.salesDataFor') . " {$this->headingDateTime}, " . __('modules.report.timePeriod') . " {$this->headingStartTime} - {$this->headingEndTime}"
            : __('modules.report.salesDataFrom') . " {$this->headingDateTime} " . __('app.to') . " {$this->headingEndDateTime}, " . __('modules.report.timePeriodEachDay') . " {$this->headingStartTime} - {$this->headingEndTime}";

        return [
            [__('menu.itemReport') . ' ' . $headingTitle],
            [
                __('modules.menu.itemName'),
                __('modules.menu.categoryName'),
                __('modules.report.quantitySold'),
                __('modules.report.sellingPrice'),
                __('modules.report.totalRevenue'),
            ]
        ];
    }
    public function map($item): array
    {
        $itemName = $item->item_name;

        if (!empty($item->variation)) {
            $itemName .= ' (' . $item->variation . ')';
        }

        return [
            $itemName,
            $this->getTranslatedText($item->category_name),
            (int) $item->quantity_sold,
            currency_format($item->sold_unit_price, restaurant()->currency_id),
            currency_format($item->total_revenue, restaurant()->currency_id),
        ];
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

    public function defaultStyles(Style $defaultStyle)
    {
        return $defaultStyle
            ->getFont()
            ->setName('Arial');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true, 'name' => 'Arial'], 'fill'  => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'f5f5f5'),
            ]],
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->leftJoin('menu_item_variations', 'menu_item_variations.id', '=', 'order_items.menu_item_variation_id')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'menu_items.item_category_id')
            ->join('branches', 'branches.id', '=', 'orders.branch_id')
            ->where('branches.restaurant_id', restaurant()->id)
            ->whereBetween('orders.date_time', [$this->startDateTime, $this->endDateTime])
            ->where('orders.status', 'paid')
            ->where(function ($q) {
                if ($this->startTime < $this->endTime) {
                    $q->whereRaw('TIME(orders.date_time) BETWEEN ? AND ?', [$this->startTime, $this->endTime]);
                } else {
                    $q->where(function ($sub) {
                        $sub->whereRaw('TIME(orders.date_time) >= ?', [$this->startTime])
                            ->orWhereRaw('TIME(orders.date_time) <= ?', [$this->endTime]);
                    });
                }
            });

        if ($this->searchTerm) {
            $safeTerm = Common::safeString($this->searchTerm);

            $query->where(function ($q) use ($safeTerm) {
                $q->where('menu_items.item_name', 'like', '%' . $safeTerm . '%')
                    ->orWhere('item_categories.category_name', 'like', '%' . $safeTerm . '%')
                    ->orWhere('menu_item_variations.variation', 'like', '%' . $safeTerm . '%');
            });
        }

        return $query
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
    }
}
