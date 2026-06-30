<?php

namespace App\Exports;

use App\Services\ReportBranchScope;
use App\Services\SalesReportData;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoryReportExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected string $startDateTime, $endDateTime, $startTime, $endTime, $timezone;
    protected string $branchFilter;
    protected $headingDateTime, $headingEndDateTime, $headingStartTime, $headingEndTime;

    public function __construct(string $startDateTime, string $endDateTime, string $startTime, string $endTime, string $timezone, string $branchFilter = ReportBranchScope::FILTER_CURRENT)
    {
        $this->startDateTime = $startDateTime;
        $this->endDateTime = $endDateTime;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->timezone = $timezone;
        $this->branchFilter = ReportBranchScope::validateFilter($branchFilter, (int) restaurant()->id);

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
            [ReportBranchScope::appendExportScope(__('menu.categoryReport') . ' ' . $headingTitle, $this->branchFilter)],
            [
            __('modules.menu.itemCategory'),
            __('modules.report.quantitySold'),
            __('modules.order.amount'),
            ]
        ];
    }

    public function map($item): array
    {
        return [
            $item->category_name,
            $item->quantity_sold ?: 0,
            currency_format($item->total_revenue, restaurant()->currency_id),
        ];
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
        $dateTimeData = [
            'startDateTime' => $this->startDateTime,
            'endDateTime' => $this->endDateTime,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
        ];

        return SalesReportData::fetchCategoryReportRows($dateTimeData, $this->branchFilter);
    }

}
