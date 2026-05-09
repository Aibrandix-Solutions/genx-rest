<?php

namespace Modules\Hrm\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceImportTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    use Exportable;

    public function headings(): array
    {
        return [
            'date',
            'staff_code',
            'status',
            'shift',
            'clock_in_at',
            'clock_out_at',
            'late_minutes',
            'note',
            // Optional fallback for advanced users (DB id)
            'employee_id',
        ];
    }

    public function array(): array
    {
        return [
            [
                'date' => now()->toDateString(),
                'staff_code' => 'EMP001',
                'status' => 'present',
                'shift' => '',
                'clock_in_at' => now()->format('Y-m-d H:i:s'),
                'clock_out_at' => now()->addHours(8)->format('Y-m-d H:i:s'),
                'late_minutes' => 0,
                'note' => '',
                'employee_id' => '',
            ],
        ];
    }
}
