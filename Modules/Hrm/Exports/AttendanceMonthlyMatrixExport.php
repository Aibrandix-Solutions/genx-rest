<?php

namespace Modules\Hrm\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AttendanceMonthlyMatrixExport implements FromArray, WithHeadings, ShouldAutoSize, WithCustomStartCell, WithEvents
{
    use Exportable;

    private string $title;

    /** @var array<int, string> */
    private array $dates;

    /** @var array<int, array<string, mixed>> */
    private array $rows;

    /**
     * @param array<int, string> $dates
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(string $title, array $dates, array $rows)
    {
        $this->title = $title;
        $this->dates = $dates;
        $this->rows = $rows;
    }

    public function startCell(): string
    {
        // Keep room for a styled title row.
        return 'A3';
    }

    public function headings(): array
    {
        $dayColumns = array_map(fn($d) => Carbon::parse($d)->format('m-d'), $this->dates);

        return array_merge(
            ['employee_name', 'staff_code'],
            $dayColumns,
            ['total_present', 'total_absent_leave']
        );
    }

    public function array(): array
    {
        return array_map(function (array $row) {
            $out = [
                'employee_name' => $row['employee_name'] ?? null,
                'staff_code' => $row['staff_code'] ?? null,
            ];

            foreach ($this->dates as $date) {
                $out[$date] = $row[$date] ?? '';
            }

            $out['total_present'] = $row['total_present'] ?? null;
            $out['total_absent_leave'] = $row['total_absent_leave'] ?? null;

            return $out;
        }, $this->rows);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $totalColumns = 2 + count($this->dates) + 2; // employee_name + staff_code + days + totals
                $lastColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColumns);

                $sheet->setCellValue('A1', $this->title);
                $sheet->mergeCells('A1:' . $lastColumnLetter . '1');

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(26);
            },
        ];
    }
}
