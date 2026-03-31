<?php

namespace Modules\Hrm\Imports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Hrm\Entities\AttendanceLog;
use Modules\Hrm\Entities\Shift;

class AttendanceImport implements ToCollection, WithHeadingRow
{
    use Importable;

    private int $restaurantId;
    private int $branchId;

    private int $total = 0;
    private int $imported = 0;
    private int $skipped = 0;
    private int $failed = 0;

    private int $skippedMissingEmployee = 0;
    private int $skippedMissingDate = 0;

    public function __construct(int $restaurantId, int $branchId)
    {
        $this->restaurantId = $restaurantId;
        $this->branchId = $branchId;
    }

    public function results(): array
    {
        return [
            'total' => $this->total,
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
            'skipped_missing_employee' => $this->skippedMissingEmployee,
            'skipped_missing_date' => $this->skippedMissingDate,
        ];
    }

    private function normalizeStatus($value): string
    {
        $raw = strtolower(trim((string) $value));
        $raw = str_replace([' ', '-'], '_', $raw);

        return match ($raw) {
            'present' => 'present',
            'late' => 'late',
            'halfday', 'half_day' => 'half_day',
            'absent' => 'absent',
            'leave', 'sick_leave', 'sickleave', 'vacation', 'holiday' => 'leave',
            default => $raw !== '' ? $raw : 'present',
        };
    }

    private function parseDateTime($value, string $date): ?Carbon
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // If it's a time-only value like 09:00 or 09:00:00, attach it to the provided date.
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value)) {
            return Carbon::parse($date . ' ' . $value);
        }

        return Carbon::parse($value);
    }

    private function resolveEmployeeId($value): ?int
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (ctype_digit($raw)) {
            // Validate that the numeric ID belongs to this restaurant
            return DB::table('hrm_employees')
                ->where('restaurant_id', $this->restaurantId)
                ->where('id', (int) $raw)
                ->value('id');
        }

        // Treat as staff_code (e.g. EMP001)
        return DB::table('hrm_employees')
            ->where('restaurant_id', $this->restaurantId)
            ->where('staff_code', $raw)
            ->value('id');
    }

    private function resolveEmployeeIdFromRow($row): ?int
    {
        // Preferred: staff_code column (human-friendly)
        $staffCode = $row['staff_code'] ?? $row['staffcode'] ?? null;
        $staffCode = trim((string) $staffCode);
        if ($staffCode !== '') {
            return DB::table('hrm_employees')
                ->where('restaurant_id', $this->restaurantId)
                ->where('staff_code', $staffCode)
                ->value('id');
        }

        // Backward compatible: employee_id column can be numeric or staff_code.
        return $this->resolveEmployeeId($row['employee_id'] ?? null);
    }

    public function collection($rows)
    {
        foreach ($rows as $row) {
            try {
                $this->total++;

                $employeeId = $this->resolveEmployeeIdFromRow($row);
                if (!$employeeId) {
                    $this->skipped++;
                    $this->skippedMissingEmployee++;
                    continue;
                }

                $date = $row['date'] ?? null;
                if (!$date) {
                    $this->skipped++;
                    $this->skippedMissingDate++;
                    continue;
                }

                $date = Carbon::parse($date)->toDateString();

                $clockInAt = $this->parseDateTime($row['clock_in_at'] ?? null, $date);
                $clockOutAt = $this->parseDateTime($row['clock_out_at'] ?? null, $date);

                if ($clockInAt && $clockOutAt && $clockOutAt->lessThanOrEqualTo($clockInAt)) {
                    $clockOutAt = $clockOutAt->copy()->addDay();
                }

                $shiftId = !empty($row['shift_id']) ? (int) $row['shift_id'] : null;
                if (!$shiftId && !empty($row['shift'])) {
                    $shiftName = trim((string) $row['shift']);
                    if ($shiftName !== '') {
                        $shiftId = Shift::query()
                            ->where('restaurant_id', $this->restaurantId)
                            ->where('name', $shiftName)
                            ->where(function ($q) {
                                $q->whereNull('branch_id')
                                    ->orWhere('branch_id', $this->branchId);
                            })
                            ->value('id');
                    }
                }

                $status = $this->normalizeStatus($row['status'] ?? 'present');

                AttendanceLog::updateOrCreate(
                    [
                        'restaurant_id' => $this->restaurantId,
                        'employee_id' => $employeeId,
                        'date' => $date,
                    ],
                    [
                        'branch_id' => $this->branchId,
                        'shift_id' => $shiftId,
                        'status' => $status,
                        'clock_in_at' => $clockInAt,
                        'clock_out_at' => $clockOutAt,
                        'late_minutes' => (int) ($row['late_minutes'] ?? 0),
                        'note' => !empty($row['note']) ? (string) $row['note'] : null,
                    ]
                );

                $this->imported++;
            } catch (\Throwable $e) {
                $this->failed++;
                Log::warning('Attendance import row failed', [
                    'error' => $e->getMessage(),
                    'row' => is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : null),
                ]);
            }
        }
    }
}
