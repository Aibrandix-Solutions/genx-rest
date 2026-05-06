<?php

namespace Modules\Hrm\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PayrollMonthlyExport implements FromArray, WithHeadings, ShouldAutoSize, WithCustomStartCell, WithEvents
{
    use Exportable;

    private string $title;

    /** @var array<int, array<string, mixed>> */
    private array $rows;

    /** @param array<int, array<string, mixed>> $rows */
    public function __construct(string $title, array $rows)
    {
        $this->title = $title;
        $this->rows = $rows;
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function headings(): array
    {
        return [
            'S/N',
            'NAME',
            'STAFF CODE',
            'TOTAL OF WORKING DAYS',
            'TOTAL LEAVE',
            'TOTAL WORKING DAYS',
            'MONTHLY BASIC SALARY PER DAY',
            'MONTHLY BASIC SALARY',
            'ADDITIONAL PAY',
            'TOTAL EARNING',
            'ADVANCE',
            'EPF (8%)',
            'ETF (EMPLOYER)',
            'TIME DEDUCTION',
            'CREDIT PURCHASE',
            'OTHER DEDUCTION',
            'TOTAL DEDUCTION',
            'PAYABLE SALARY',
            'PAYMENT DATE',
        ];
    }

    public function array(): array
    {
        return array_map(function (array $r) {
            return [
                $r['sn'] ?? null,
                $r['name'] ?? null,
                $r['staff_code'] ?? null,
                $r['total_of_working_days'] ?? null,
                $r['total_leave'] ?? null,
                $r['total_working_days'] ?? null,
                $r['monthly_basic_salary_per_day'] ?? null,
                $r['monthly_basic_salary'] ?? null,
                $r['additional_pay'] ?? null,
                $r['total_earning'] ?? null,
                $r['advance'] ?? null,
                $r['epf'] ?? null,
                $r['etf'] ?? null,
                $r['time_deduction'] ?? null,
                $r['credit_purchase'] ?? null,
                $r['other_deduction'] ?? null,
                $r['total_of_deduction'] ?? null,
                $r['payable_salary'] ?? null,
                $r['payment_date'] ?? null,
            ];
        }, $this->rows);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastColumn = 'S'; // 19 columns

                $sheet->mergeCells('A1:' . $lastColumn . '1');
                $sheet->setCellValue('A1', $this->title);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('A3:' . $lastColumn . '3')->getFont()->setBold(true);
                $sheet->getStyle('A3:' . $lastColumn . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Freeze heading row
                $sheet->freezePane('A4');
            },
        ];
    }
}
