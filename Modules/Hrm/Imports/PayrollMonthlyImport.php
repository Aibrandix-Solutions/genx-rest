<?php

namespace Modules\Hrm\Imports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Modules\Hrm\Entities\PayrollAdjustment;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PayrollMonthlyImport implements ToCollection, WithHeadingRow
{
    private int $restaurantId;
    private int $branchId;
    private int $year;
    private int $month;

    /** @var array<string,int> */
    private array $results = [
        'imported' => 0,
        'skipped' => 0,
        'skipped_missing_employee' => 0,
        'failed' => 0,
    ];

    public function __construct(int $restaurantId, int $branchId, int $year, int $month)
    {
        $this->restaurantId = $restaurantId;
        $this->branchId = $branchId;
        $this->year = $year;
        $this->month = $month;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                $staffCode = trim((string) ($row['staff_code'] ?? ''));
                if ($staffCode === '') {
                    $this->results['skipped']++;
                    $this->results['skipped_missing_employee']++;
                    continue;
                }

                $employeeId = DB::table('hrm_employees')
                    ->where('restaurant_id', $this->restaurantId)
                    ->where('branch_id', $this->branchId)
                    ->where('staff_code', $staffCode)
                    ->value('id');

                if (!$employeeId) {
                    $this->results['skipped']++;
                    $this->results['skipped_missing_employee']++;
                    continue;
                }

                $money = static fn ($v): float => max(0, (float) ($v ?? 0));

                $rawPaymentDate = $row['payment_date'] ?? null;
                $paymentDate = null;
                if ($rawPaymentDate !== null && $rawPaymentDate !== '') {
                    if (is_numeric($rawPaymentDate)) {
                        $paymentDate = ExcelDate::excelToDateTimeObject((float) $rawPaymentDate)->format('Y-m-d');
                    } else {
                        $paymentDate = Carbon::parse((string) $rawPaymentDate)->toDateString();
                    }
                }

                $data = [
                    'additional_pay' => $money($row['additional_pay'] ?? 0),
                    'advance' => $money($row['advance'] ?? 0),
                    'epf' => $money($row['epf'] ?? 0),
                    'etf' => $money($row['etf'] ?? 0),
                    'time_deduction' => $money($row['time_deduction'] ?? 0),
                    'credit_purchase' => $money($row['credit_purchase'] ?? 0),
                    'other_deduction' => $money($row['other_deduction'] ?? 0),
                    'payment_date' => $paymentDate,
                    'note' => $row['note'] ?? null,
                ];

                PayrollAdjustment::query()->updateOrCreate([
                    'restaurant_id' => $this->restaurantId,
                    'branch_id' => $this->branchId,
                    'employee_id' => (int) $employeeId,
                    'year' => $this->year,
                    'month' => $this->month,
                ], $data);

                $this->results['imported']++;
            } catch (\Throwable $e) {
                $this->results['failed']++;
            }
        }
    }

    /** @return array<string,int> */
    public function results(): array
    {
        return $this->results;
    }
}
