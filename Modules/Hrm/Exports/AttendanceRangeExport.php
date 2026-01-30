<?php

namespace Modules\Hrm\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceRangeExport implements FromArray, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return [
            'date',
            'branch',
            'employee_name',
            'staff_code',
            'shift',
            'status',
            'clock_in_at',
            'clock_out_at',
            'late_minutes',
            'work_duration',
            'note',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function map($row): array
    {
        return [
            $row['date'] ?? null,
            $row['branch'] ?? null,
            $row['employee_name'] ?? null,
            $row['staff_code'] ?? null,
            $row['shift'] ?? null,
            $row['status'] ?? null,
            $row['clock_in_at'] ?? null,
            $row['clock_out_at'] ?? null,
            $row['late_minutes'] ?? null,
            $row['work_duration'] ?? null,
            $row['note'] ?? null,
        ];
    }
}
