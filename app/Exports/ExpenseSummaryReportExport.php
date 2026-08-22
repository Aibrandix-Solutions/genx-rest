<?php

namespace App\Exports;

use App\Services\ReportBranchScope;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseSummaryReportExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected string $branchFilter;
    public $totalAmount;

    public function __construct($startDate, $endDate, string $branchFilter = ReportBranchScope::FILTER_CURRENT)
    {
        $this->startDate = Carbon::createFromFormat('m/d/Y', $startDate)->toDateString();
        $this->endDate = Carbon::createFromFormat('m/d/Y', $endDate)->toDateString();
        $this->branchFilter = ReportBranchScope::validateFilter($branchFilter, (int) restaurant()->id);

        $this->totalAmount = ReportBranchScope::expensesBaseQuery($this->branchFilter)
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->sum('amount');
    }

    public function headings(): array
    {
        return [
            [ReportBranchScope::appendExportScope(
                __('modules.expenses.reports.expenseSummaryReport') . ' ' . $this->startDate . ' - ' . $this->endDate,
                $this->branchFilter
            )],
            [
                'Category',
                'Total Expense',
                'Percentage of Total',
            ]
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->category->name,
            number_format($expense->total_amount, 2), // Summed amount per category
            $this->totalAmount > 0 ? number_format(($expense->total_amount / $this->totalAmount) * 100, 2) . '%' : '0%',
        ];
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return $defaultStyle->getFont()->setName('Arial');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'name' => 'Arial'], 'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'f5f5f5'],
            ]],
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ReportBranchScope::expensesBaseQuery($this->branchFilter)
            ->with(ReportBranchScope::eagerLoadExpenseCategoryForReport())
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->selectRaw('expense_category_id, SUM(amount) as total_amount')
            ->groupBy('expense_category_id')
            ->get();
    }
    
}
