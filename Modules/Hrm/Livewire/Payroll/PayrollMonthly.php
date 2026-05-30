<?php

namespace Modules\Hrm\Livewire\Payroll;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Hrm\Entities\AttendanceLog;
use Modules\Hrm\Entities\Employee;
use Modules\Hrm\Entities\Holiday;
use Modules\Hrm\Entities\LeaveRequest;
use Modules\Hrm\Entities\PayrollAdjustment;
use Modules\Hrm\Entities\HrmSetting;
use Modules\Hrm\Exports\PayrollImportTemplateExport;
use Modules\Hrm\Exports\PayrollMonthlyExport;
use Modules\Hrm\Imports\PayrollMonthlyImport;

class PayrollMonthly extends Component
{
    use WithPagination, AuthorizesRequests, WithFileUploads;

    public ?int $branchId = null;
    public array $branches = [];
    public ?int $departmentId = null;
    public ?int $designationId = null;
    public array $departments = [];
    public array $designations = [];

    public string $month = '';
    public string $search = '';

    public bool $showAdjustModal = false;
    public ?int $adjustEmployeeId = null;

    public float $additional_pay = 0;
    public float $advance = 0;
    public float $epf = 0;
    public float $etf = 0;
    public float $time_deduction = 0;
    public float $credit_purchase = 0;
    public float $other_deduction = 0;
    public ?string $payment_date = null;
    public ?string $note = null;

    public $importFile;
    public ?string $importMessage = null;

    protected $queryString = ['branchId', 'month', 'search', 'departmentId', 'designationId'];

    private function normalizeMonth(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^\d{4}-\d{1,2}$/', $value) === 1) {
            // Fix: Parse with explicit day to avoid month overflow
            // Extract year and month, then create date with day = 01
            if (preg_match('/^(\d{4})-(\d{1,2})$/', $value, $matches)) {
                $year = $matches[1];
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                return Carbon::createFromFormat('Y-m-d', "$year-$month-01")->format('Y-m');
            }
        }

        return $value;
    }

    private function isCompanyLevel(): bool
    {
        return $this->branchId === 0;
    }

    /** Apply branch filter to any query builder. Company level = whereNull('branch_id'). */
    private function branchFilter(): \Closure
    {
        return $this->isCompanyLevel()
            ? fn ($q) => $q->whereNull('branch_id')
            : fn ($q) => $q->where('branch_id', $this->branchId);
    }

    /** Resolves to null (company) or a real branch ID for DB writes. */
    private function effectiveBranchId(): ?int
    {
        return $this->isCompanyLevel() ? null : (int) $this->branchId;
    }

    private function resetAdjustmentForm(): void
    {
        $this->adjustEmployeeId = null;
        $this->additional_pay = 0;
        $this->advance = 0;
        $this->epf = 0;
        $this->etf = 0;
        $this->time_deduction = 0;
        $this->credit_purchase = 0;
        $this->other_deduction = 0;
        $this->payment_date = null;
        $this->note = null;
    }

    public function mount(): void
    {
        $this->authorize('Manage Payroll');

        $this->branchId = $this->branchId ?? (branch()?->id);
        $this->month = $this->normalizeMonth($this->month ?: now()->format('Y-m'));

        $this->branches = DB::table('branches')
            ->select('id', 'name')
            ->when(restaurant(), fn ($q) => $q->where('restaurant_id', restaurant()->id))
            ->orderBy('name')
            ->get()
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])
            ->all();

        $this->departments = DB::table('hrm_departments')
            ->select('id', 'name')
            ->when(restaurant(), fn ($q) => $q->where('restaurant_id', restaurant()->id))
            ->orderBy('name')
            ->get()
            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])
            ->all();

        $this->designations = DB::table('hrm_designations')
            ->select('id', 'name')
            ->when(restaurant(), fn ($q) => $q->where('restaurant_id', restaurant()->id))
            ->orderBy('name')
            ->get()
            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])
            ->all();
    }

    public function updatedMonth($value): void
    {
        if (!is_string($value) || $value === '') {
            return;
        }

        $normalized = $this->normalizeMonth($value);
        if ($normalized !== $this->month) {
            $this->month = $normalized;
        }
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['branchId', 'month', 'search', 'departmentId', 'designationId'], true)) {
            $this->resetPage();
        }
    }

    private function monthRange(): array
    {
        $month = $this->normalizeMonth($this->month);

        if (preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            // Fix: Use Y-m-d format with explicit day to avoid month overflow
            $m = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        } else {
            // Fallback for any unexpected but parseable value
            $m = Carbon::parse($month)->startOfMonth();
        }

        return [$m->copy(), $m->copy()->endOfMonth()];
    }

    private function daysInMonth(): int
    {
        [$from] = $this->monthRange();
        // Use Carbon's built-in daysInMonth property for accuracy
        return $from->daysInMonth;
    }

    private function intersectDays(string $fromDate, string $toDate, Carbon $rangeFrom, Carbon $rangeTo): int
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->startOfDay();

        if ($to->lt($rangeFrom) || $from->gt($rangeTo)) {
            return 0;
        }

        $start = $from->greaterThan($rangeFrom) ? $from : $rangeFrom->copy();
        $end = $to->lessThan($rangeTo) ? $to : $rangeTo->copy();

        return $start->diffInDays($end) + 1;
    }

    public function openAdjustModal(int $employeeId): void
    {
        $this->authorize('Manage Payroll');

        if ($this->branchId === null) {
            return;
        }

        [$from, $to] = $this->monthRange();

        $employee = Employee::query()
            ->where('restaurant_id', restaurant()->id)
            ->tap($this->branchFilter())
            ->findOrFail($employeeId);

        $adj = PayrollAdjustment::query()->firstOrNew([
            'restaurant_id' => restaurant()->id,
            'branch_id' => $this->effectiveBranchId(),
            'employee_id' => $employee->id,
            'year' => (int) $from->format('Y'),
            'month' => (int) $from->format('m'),
        ]);

        $this->adjustEmployeeId = $employee->id;
        $this->additional_pay = (float) ($adj->additional_pay ?? 0);
        $this->advance = (float) ($adj->advance ?? 0);
        
        // Auto-calculate EPF/ETF if enabled
        $epfAutoCalc = HrmSetting::get('epf_auto_calculate', false);
        $etfAutoCalc = HrmSetting::get('etf_auto_calculate', false);
        
        if ($epfAutoCalc) {
            $epfBasic = HrmSetting::get('epf_basic_salary', 0);
            $epfRate = HrmSetting::get('epf_employee_rate', 8);
            $this->epf = ($epfBasic * $epfRate) / 100;
        } else {
            $this->epf = (float) ($adj->epf ?? 0);
        }
        
        if ($etfAutoCalc) {
            $etfBasic = HrmSetting::get('etf_basic_salary', 0);
            $etfRate = HrmSetting::get('etf_employer_rate', 3);
            $this->etf = ($etfBasic * $etfRate) / 100;
        } else {
            $this->etf = (float) ($adj->etf ?? 0);
        }
        
        $this->time_deduction = (float) ($adj->time_deduction ?? 0);
        $this->credit_purchase = (float) ($adj->credit_purchase ?? 0);
        $this->other_deduction = (float) ($adj->other_deduction ?? 0);
        $this->payment_date = $adj->payment_date?->toDateString();
        $this->note = $adj->note;

        $this->showAdjustModal = true;
    }

    public function closeAdjustModal(): void
    {
        $this->authorize('Manage Payroll');

        $this->showAdjustModal = false;
        $this->resetAdjustmentForm();
    }

    public function saveAdjustment(): void
    {
        $this->authorize('Manage Payroll');

        $this->validate([
            'branchId' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value === null) {
                        $fail('Select a branch or Company Level.');
                    } elseif ($value !== 0 && !\Illuminate\Support\Facades\DB::table('branches')
                        ->where('id', $value)
                        ->where('restaurant_id', restaurant()->id)
                        ->exists()) {
                        $fail('Invalid branch selected.');
                    }
                },
            ],
            'month' => ['required', 'date_format:Y-m'],
            'adjustEmployeeId' => ['required', 'integer', Rule::exists('hrm_employees', 'id')->where(function ($q) {
                return $q->where('restaurant_id', restaurant()->id)
                    ->when(!$this->isCompanyLevel(), fn ($q2) => $q2->where('branch_id', (int) $this->branchId), fn ($q2) => $q2->whereNull('branch_id'));
            })],
            'additional_pay' => ['nullable', 'numeric', 'min:0'],
            'advance' => ['nullable', 'numeric', 'min:0'],
            'epf' => ['nullable', 'numeric', 'min:0'],
            'etf' => ['nullable', 'numeric', 'min:0'],
            'time_deduction' => ['nullable', 'numeric', 'min:0'],
            'credit_purchase' => ['nullable', 'numeric', 'min:0'],
            'other_deduction' => ['nullable', 'numeric', 'min:0'],
            'payment_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        [$from, $to] = $this->monthRange();

        PayrollAdjustment::query()->updateOrCreate([
            'restaurant_id' => restaurant()->id,
            'branch_id' => $this->effectiveBranchId(),
            'employee_id' => (int) $this->adjustEmployeeId,
            'year' => (int) $from->format('Y'),
            'month' => (int) $from->format('m'),
        ], [
            'additional_pay' => (float) $this->additional_pay,
            'advance' => (float) $this->advance,
            'epf' => (float) $this->epf,
            'etf' => (float) $this->etf,
            'time_deduction' => (float) $this->time_deduction,
            'credit_purchase' => (float) $this->credit_purchase,
            'other_deduction' => (float) $this->other_deduction,
            'payment_date' => $this->payment_date,
            'note' => $this->note,
        ]);

        $this->showAdjustModal = false;
        $this->resetAdjustmentForm();
    }

    private function buildPayrollRows(): array
    {
        $this->authorize('Manage Payroll');

        if ($this->branchId === null) {
            return [];
        }

        [$from, $to] = $this->monthRange();
        $daysInMonth = $this->daysInMonth();

        $branchName = $this->isCompanyLevel()
            ? 'Company Level'
            : DB::table('branches')->where('id', $this->branchId)->value('name');

        $employees = Employee::query()
            ->where('restaurant_id', restaurant()->id)
            ->tap($this->branchFilter())
            ->when($this->departmentId, fn ($q) => $q->where('department_id', (int) $this->departmentId))
            ->when($this->designationId, fn ($q) => $q->where('designation_id', (int) $this->designationId))
            ->when($this->search, function ($q) {
                $term = "%{$this->search}%";
                $q->where(function ($q2) use ($term) {
                    $q2->where('name', 'like', $term)
                        ->orWhere('staff_code', 'like', $term);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'staff_code', 'department_id', 'designation_id', 'basic_salary_per_day', 'basic_salary_per_month', 'is_epf_eligible']);

        $employeeIds = $employees->pluck('id')->all();

        $deptNames = DB::table('hrm_departments')
            ->whereIn('id', $employees->pluck('department_id')->filter()->unique()->all())
            ->pluck('name', 'id');
        $desigNames = DB::table('hrm_designations')
            ->whereIn('id', $employees->pluck('designation_id')->filter()->unique()->all())
            ->pluck('name', 'id');

        // POS customer due for employees as of month end
        // (sum of max(0, total - amount_paid) for payment_due orders up to $to)
        $posDueByEmployee = DB::table('customers as c')
            ->join('hrm_employees as e', 'e.id', '=', 'c.employee_id')
            ->leftJoin('orders as o', function ($join) use ($to) {
                $join->on('o.customer_id', '=', 'c.id')
                    ->where('o.status', '=', 'payment_due')
                    ->where('o.date_time', '<=', $to->copy()->endOfDay()->toDateTimeString());
            })
            ->leftJoin('branches as b', 'b.id', '=', 'o.branch_id')
            ->where('c.restaurant_id', restaurant()->id)
            ->whereIn('c.employee_id', $employeeIds)
            ->where(function ($q) {
                $q->whereNull('o.id')
                    ->orWhere('b.restaurant_id', restaurant()->id);
            })
            ->groupBy('c.employee_id')
            ->select('c.employee_id', DB::raw('SUM(CASE WHEN (o.total - o.amount_paid) > 0 THEN (o.total - o.amount_paid) ELSE 0 END) as due'))
            ->pluck('due', 'employee_id');

        $presentCounts = AttendanceLog::query()
            ->where('restaurant_id', restaurant()->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('status', ['present', 'late', 'half_day'])
            ->whereIn('employee_id', $employeeIds)
            ->select('employee_id', DB::raw('COUNT(*) as c'))
            ->groupBy('employee_id')
            ->pluck('c', 'employee_id');

        $leaveRequests = LeaveRequest::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('status', 'approved')
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('to_date', '>=', $from->toDateString())
            ->whereDate('from_date', '<=', $to->toDateString())
            ->get(['employee_id', 'from_date', 'to_date']);

        $leaveDaysByEmployee = [];
        foreach ($leaveRequests as $lr) {
            $leaveDaysByEmployee[$lr->employee_id] = ($leaveDaysByEmployee[$lr->employee_id] ?? 0)
                + $this->intersectDays($lr->from_date, $lr->to_date, $from, $to);
        }

        $holidayCount = Holiday::query()
            ->where('restaurant_id', restaurant()->id)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->where(function ($q) {
                if ((int) $this->branchId > 0) {
                    $q->whereNull('branch_id')
                        ->orWhere('branch_id', (int) $this->branchId);
                } else {
                    $q->whereNull('branch_id');
                }
            })
            ->count();

        $adjustments = PayrollAdjustment::query()
            ->where('restaurant_id', restaurant()->id)
            ->when($this->isCompanyLevel(), fn ($q) => $q->whereNull('branch_id'), fn ($q) => $q->where('branch_id', (int) $this->branchId))
            ->where('year', (int) $from->format('Y'))
            ->where('month', (int) $from->format('m'))
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy('employee_id');

        $rows = [];
        $sn = 1;

        // Pre-fetch EPF settings once outside the loop to avoid N+1 queries
        $epfAutoCalc = HrmSetting::get('epf_auto_calculate', false);
        $epfBasic = $epfAutoCalc ? HrmSetting::get('epf_basic_salary', 0) : 0;
        $epfRate = (float) HrmSetting::get('epf_employee_rate', 8);
        $epfCalculated = $epfAutoCalc ? ($epfBasic * $epfRate) / 100 : 0;

        foreach ($employees as $e) {
            $presentDays = (int) ($presentCounts[$e->id] ?? 0);
            $leaveDays = (int) ($leaveDaysByEmployee[$e->id] ?? 0);

            $basicPerDay = (float) ($e->basic_salary_per_day ?? 0);
            $monthlyBasic = $presentDays * $basicPerDay;

            $adj = $adjustments->get($e->id);

            $additionalPay = (float) ($adj?->additional_pay ?? 0);
            $advance = (float) ($adj?->advance ?? 0);
            
            // Only deduct EPF if employee is eligible
            $isEpfEligible = (bool) ($e->is_epf_eligible ?? true);

            if ($isEpfEligible && $epfAutoCalc) {
                $epf = $epfCalculated;
            } elseif ($isEpfEligible) {
                $epf = (float) ($adj?->epf ?? 0);
            } else {
                $epf = 0;
            }
            
            // NOTE: ETF is NOT deducted from employee salary - it's employer-only contribution
            // Employee ETF contribution is always 0%
            $etf = 0;
            
            $timeDeduction = (float) ($adj?->time_deduction ?? 0);
            $creditPurchaseAuto = (float) ($posDueByEmployee[$e->id] ?? 0);
            $creditPurchaseManual = (float) ($adj?->credit_purchase ?? 0);
            $creditPurchase = $creditPurchaseManual > 0 ? $creditPurchaseManual : $creditPurchaseAuto;
            $otherDeduction = (float) ($adj?->other_deduction ?? 0);

            $totalEarning = $monthlyBasic + $additionalPay;
            $totalDeduction = $advance + $epf + $timeDeduction + $creditPurchase + $otherDeduction;
            $payable = $totalEarning - $totalDeduction;

            $rows[] = [
                'employee_id' => $e->id,
                'sn' => $sn++,
                'name' => $e->name,
                'staff_code' => $e->staff_code,
                'total_of_working_days' => $daysInMonth,
                'total_leave' => $leaveDays,
                'total_working_days' => $presentDays,
                'monthly_basic_salary_per_day' => round($basicPerDay, 2),
                'monthly_basic_salary' => round($monthlyBasic, 2),
                'total_of_all_month_salary' => round($totalEarning, 2),
                'month_net_salary' => round($payable, 2),
                'total' => round($payable, 2),
                'total_earning' => round($totalEarning, 2),
                'additional_pay' => round($additionalPay, 2),
                'advance' => round($advance, 2),
                'epf' => round($epf, 2),
                'epf_rate' => round((float) $epfRate, 2),
                'etf' => round($etf, 2),
                'time_deduction' => round($timeDeduction, 2),
                'credit_purchase' => round($creditPurchase, 2),
                'other_deduction' => round($otherDeduction, 2),
                'total_of_deduction' => round($totalDeduction, 2),
                'payable_salary' => round($payable, 2),
                'payment_date' => $adj?->payment_date?->toDateString(),
                'department' => $deptNames[$e->department_id] ?? null,
                'designation' => $desigNames[$e->designation_id] ?? null,
            ];
        }

        return [
            'title' => restaurant()->name . ' - Monthly Payroll - ' . $from->format('F Y') . ' (' . ($branchName ?: 'Branch') . ')',
            'holiday_count' => $holidayCount,
            'rows' => $rows,
            'branch_name' => $branchName,
            'month_label' => $from->format('F Y'),
        ];
    }

    public function exportExcel()
    {
        $this->authorize('Manage Payroll');

        $data = $this->buildPayrollRows();
        if (!$data) {
            return;
        }

        $fileName = 'payroll-' . $this->month . '-branch-' . $this->branchId . '.xlsx';

        return Excel::download(new PayrollMonthlyExport($data['title'], $data['rows']), $fileName);
    }

    public function exportPdf()
    {
        $this->authorize('Manage Payroll');

        $data = $this->buildPayrollRows();
        if (!$data) {
            return;
        }

        $restaurant = restaurant();
        $logoPath = $restaurant->logo ? public_path('user-uploads/logo/' . $restaurant->logo) : null;

        $pdf = Pdf::loadView('hrm::payroll.monthly-pdf', [
            'title' => $data['title'],
            'rows' => $data['rows'],
            'restaurant' => $restaurant,
            'logoPath' => ($logoPath && file_exists($logoPath)) ? $logoPath : null,
            'branchName' => $data['branch_name'],
            'monthLabel' => $data['month_label'],
        ])->setPaper('a4', 'landscape');

        $fileName = 'payroll-' . $this->month . '-branch-' . $this->branchId . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    public function downloadEmployeePayslip(int $employeeId): mixed
    {
        $this->authorize('Manage Payroll');

        $data = $this->buildPayrollRows();
        if (!$data) {
            return null;
        }

        $row = collect($data['rows'])->first(fn ($r) => (int) $r['employee_id'] === $employeeId);
        if (!$row) {
            return null;
        }

        $restaurant = restaurant();
        $logoPath = $restaurant->logo ? public_path('user-uploads/logo/' . $restaurant->logo) : null;

        $pdf = Pdf::loadView('hrm::payroll.payslips-pdf', [
            'rows' => [$row],
            'restaurant' => $restaurant,
            'logoPath' => ($logoPath && file_exists($logoPath)) ? $logoPath : null,
            'branchName' => $data['branch_name'],
            'monthLabel' => $data['month_label'],
        ])->setPaper('a4', 'portrait');

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $row['name'] ?? 'employee');
        $fileName = 'payslip-' . $this->month . '-' . $safeName . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    public function exportPayslips()
    {
        $this->authorize('Manage Payroll');

        $data = $this->buildPayrollRows();
        if (!$data) {
            return;
        }

        $restaurant = restaurant();
        $logoPath = $restaurant->logo ? public_path('user-uploads/logo/' . $restaurant->logo) : null;

        $pdf = Pdf::loadView('hrm::payroll.payslips-pdf', [
            'rows' => $data['rows'],
            'restaurant' => $restaurant,
            'logoPath' => ($logoPath && file_exists($logoPath)) ? $logoPath : null,
            'branchName' => $data['branch_name'],
            'monthLabel' => $data['month_label'],
        ])->setPaper('a4', 'portrait');

        $fileName = 'payslips-' . $this->month . '-branch-' . $this->branchId . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    public function downloadImportTemplate()
    {
        $this->authorize('Manage Payroll');

        return Excel::download(new PayrollImportTemplateExport(), 'payroll-import-template.xlsx');
    }

    public function importExcel(): void
    {
        $this->authorize('Manage Payroll');

        $this->validate([
            'branchId' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value === null) {
                        $fail('Select a branch or Company Level.');
                    } elseif ((int) $value !== 0 && !DB::table('branches')
                        ->where('id', $value)
                        ->where('restaurant_id', restaurant()->id)
                        ->exists()) {
                        $fail('Invalid branch selected.');
                    }
                },
            ],
            'month' => ['required', 'date_format:Y-m'],
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        [$from] = $this->monthRange();

        $path = $this->importFile->store('imports', 'local');
        $fullPath = Storage::disk('local')->path($path);

        $import = new PayrollMonthlyImport(restaurant()->id, (int) $this->branchId, (int) $from->format('Y'), (int) $from->format('m'));
        Excel::import($import, $fullPath);

        Storage::disk('local')->delete($path);

        $r = $import->results();
        $this->importMessage = "Imported {$r['imported']} rows. Skipped {$r['skipped']} (missing employee: {$r['skipped_missing_employee']}). Failed {$r['failed']}.";

        $this->importFile = null;
    }

    public function render()
    {
        $this->authorize('Manage Payroll');

        $data = $this->branchId !== null ? $this->buildPayrollRows() : ['rows' => [], 'title' => null];

        return view('hrm::livewire.payroll.payroll-monthly', [
            'payrollRows' => $data['rows'] ?? [],
        ])->layout('layouts.app');
    }
}
