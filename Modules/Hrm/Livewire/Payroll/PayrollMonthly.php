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

        if (!$this->branchId) {
            return;
        }

        [$from, $to] = $this->monthRange();

        $employee = Employee::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('branch_id', (int) $this->branchId)
            ->findOrFail($employeeId);

        $adj = PayrollAdjustment::query()->firstOrNew([
            'restaurant_id' => restaurant()->id,
            'branch_id' => (int) $this->branchId,
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
            'branchId' => ['required', 'integer', Rule::exists('branches', 'id')->where(fn ($q) => $q->where('restaurant_id', restaurant()->id))],
            'month' => ['required', 'date_format:Y-m'],
            'adjustEmployeeId' => ['required', 'integer', Rule::exists('hrm_employees', 'id')->where(function ($q) {
                return $q->where('restaurant_id', restaurant()->id)
                    ->where('branch_id', (int) $this->branchId);
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
            'branch_id' => (int) $this->branchId,
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

        if (!$this->branchId) {
            return [];
        }

        [$from, $to] = $this->monthRange();
        $daysInMonth = $this->daysInMonth();

        $branchName = DB::table('branches')->where('id', $this->branchId)->value('name');

        $employees = Employee::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('branch_id', (int) $this->branchId)
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
            ->get(['id', 'name', 'staff_code', 'basic_salary_per_day', 'basic_salary_per_month', 'is_epf_eligible']);

        $employeeIds = $employees->pluck('id')->all();

        $presentCounts = AttendanceLog::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('branch_id', (int) $this->branchId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('status', ['present', 'late', 'half_day'])
            ->whereIn('employee_id', $employeeIds)
            ->select('employee_id', DB::raw('COUNT(*) as c'))
            ->groupBy('employee_id')
            ->pluck('c', 'employee_id');

        $leaveRequests = LeaveRequest::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('branch_id', (int) $this->branchId)
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
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', (int) $this->branchId);
            })
            ->count();

        $adjustments = PayrollAdjustment::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('branch_id', (int) $this->branchId)
            ->where('year', (int) $from->format('Y'))
            ->where('month', (int) $from->format('m'))
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy('employee_id');

        $rows = [];
        $sn = 1;

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
            
            // Auto-calculate EPF if enabled and employee is eligible
            $epfAutoCalc = HrmSetting::get('epf_auto_calculate', false);
            
            if ($isEpfEligible && $epfAutoCalc) {
                $epfBasic = HrmSetting::get('epf_basic_salary', 0);
                $epfRate = HrmSetting::get('epf_employee_rate', 8);
                $epf = ($epfBasic * $epfRate) / 100;
            } elseif ($isEpfEligible) {
                $epf = (float) ($adj?->epf ?? 0);
            } else {
                $epf = 0;
            }
            
            // NOTE: ETF is NOT deducted from employee salary - it's employer-only contribution
            // Employee ETF contribution is always 0%
            $etf = 0;
            
            $timeDeduction = (float) ($adj?->time_deduction ?? 0);
            $creditPurchase = (float) ($adj?->credit_purchase ?? 0);
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
                'etf' => round($etf, 2),
                'time_deduction' => round($timeDeduction, 2),
                'credit_purchase' => round($creditPurchase, 2),
                'other_deduction' => round($otherDeduction, 2),
                'total_of_deduction' => round($totalDeduction, 2),
                'payable_salary' => round($payable, 2),
                'payment_date' => $adj?->payment_date?->toDateString(),
            ];
        }

        return [
            'title' => 'Monthly Payroll - ' . $from->format('F Y') . ' (' . ($branchName ?: 'Branch') . ')',
            'holiday_count' => $holidayCount,
            'rows' => $rows,
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

        $pdf = Pdf::loadView('hrm::payroll.monthly-pdf', [
            'title' => $data['title'],
            'rows' => $data['rows'],
        ])->setPaper('a4', 'landscape');

        $fileName = 'payroll-' . $this->month . '-branch-' . $this->branchId . '.pdf';

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
            'branchId' => ['required', 'integer', Rule::exists('branches', 'id')->where(fn ($q) => $q->where('restaurant_id', restaurant()->id))],
            'month' => ['required', 'date_format:Y-m'],
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        [$from] = $this->monthRange();

        $path = $this->importFile->store('imports', 'local');
        $fullPath = Storage::disk('local')->path($path);

        $import = new PayrollMonthlyImport(restaurant()->id, (int) $this->branchId, (int) $from->format('Y'), (int) $from->format('m'));
        Excel::import($import, $fullPath);

        $r = $import->results();
        $this->importMessage = "Imported {$r['imported']} rows. Skipped {$r['skipped']} (missing employee: {$r['skipped_missing_employee']}). Failed {$r['failed']}.";

        $this->importFile = null;
    }

    public function render()
    {
        $this->authorize('Manage Payroll');

        $data = $this->branchId ? $this->buildPayrollRows() : ['rows' => [], 'title' => null];

        return view('hrm::livewire.payroll.payroll-monthly', [
            'payrollRows' => $data['rows'] ?? [],
        ])->layout('layouts.app');
    }
}
