<?php

namespace Modules\Hrm\Livewire\Attendance;

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
use Modules\Hrm\Entities\Shift;
use Modules\Hrm\Exports\AttendanceDailyExport;
use Modules\Hrm\Exports\AttendanceImportTemplateExport;
use Modules\Hrm\Exports\AttendanceMonthlyMatrixExport;
use Modules\Hrm\Exports\AttendanceRangeExport;
use Modules\Hrm\Imports\AttendanceImport;

class DailyAttendance extends Component
{
    use WithPagination, AuthorizesRequests, WithFileUploads;

    public string $date = '';
    public ?int $branchId = null;
    public ?int $shiftId = null;
    public string $search = '';

    public string $tab = 'daily';

    public string $dateRangeType = 'today';
    public string $fromDate = '';
    public string $toDate = '';

    public ?int $summaryShiftId = null;

    public $importFile;

    public ?string $importMessage = null;

    public array $branches = [];

    public bool $showModal = false;
    public ?int $editingEmployeeId = null;

    public ?int $shift_id = null;
    public string $status = 'present';
    public ?string $clock_in_at = null;
    public ?string $clock_out_at = null;
    public int $late_minutes = 0;
    public ?string $note = null;

    public bool $showClearModal = false;
    public ?int $clearEmployeeId = null;

    protected $queryString = ['tab', 'dateRangeType', 'fromDate', 'toDate', 'date', 'branchId', 'shiftId', 'summaryShiftId', 'search'];

    public function mount(): void
    {
        if (!$this->dateRangeType) {
            $this->dateRangeType = 'today';
        }

        // If user lands directly on All tab, default its range to current month.
        if ($this->tab === 'all' && $this->dateRangeType === 'today') {
            $this->dateRangeType = 'currentMonth';
        }

        $this->setDateRange();
        $this->date = $this->date ?: ($this->toDate ?: now()->toDateString());
        $this->branchId = $this->branchId ?? (branch()?->id);

        $this->branches = DB::table('branches')
            ->select('id', 'name')
            ->when(restaurant(), fn($q) => $q->where('restaurant_id', restaurant()->id))
            ->orderBy('name')
            ->get()
            ->map(fn($b) => ['id' => $b->id, 'name' => $b->name])
            ->all();
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['tab', 'dateRangeType', 'fromDate', 'toDate', 'date', 'branchId', 'shiftId', 'summaryShiftId', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function updatedDateRangeType(): void
    {
        if ($this->dateRangeType === 'custom') {
            return;
        }

        $this->setDateRange();

        if ($this->tab === 'daily' || $this->tab === 'byShift') {
            $this->date = $this->toDate;
        }
    }

    public function updatedTab(string $value): void
    {
        if ($value === 'daily') {
            $this->date = now()->toDateString();
        }

        if ($value === 'all') {
            $this->dateRangeType = 'currentMonth';
            $this->setDateRange();
        }
    }

    public function updatedFromDate(): void
    {
        $this->dateRangeType = 'custom';
    }

    public function updatedToDate(): void
    {
        $this->dateRangeType = 'custom';
    }

    private function setDateRange(): void
    {
        switch ($this->dateRangeType) {
            case 'today':
                $this->fromDate = now()->toDateString();
                $this->toDate = now()->toDateString();
                break;
            case 'yesterday':
                $this->fromDate = now()->subDay()->toDateString();
                $this->toDate = now()->subDay()->toDateString();
                break;
            case 'currentWeek':
                $this->fromDate = now()->startOfWeek()->toDateString();
                $this->toDate = now()->endOfWeek()->toDateString();
                break;
            case 'lastWeek':
                $this->fromDate = now()->subWeek()->startOfWeek()->toDateString();
                $this->toDate = now()->subWeek()->endOfWeek()->toDateString();
                break;
            case 'last7Days':
                $this->fromDate = now()->subDays(6)->toDateString();
                $this->toDate = now()->toDateString();
                break;
            case 'currentMonth':
                $this->fromDate = now()->startOfMonth()->toDateString();
                $this->toDate = now()->endOfMonth()->toDateString();
                break;
            case 'lastMonth':
                $this->fromDate = now()->subMonth()->startOfMonth()->toDateString();
                $this->toDate = now()->subMonth()->endOfMonth()->toDateString();
                break;
            default:
                $this->fromDate = now()->toDateString();
                $this->toDate = now()->toDateString();
                break;
        }
    }

    private function isPresentStatus(?string $status): bool
    {
        return in_array((string) $status, ['present', 'late', 'half_day'], true);
    }

    private function isAbsentStatus(?string $status): bool
    {
        return in_array((string) $status, ['absent', 'leave'], true);
    }

    private function isCompanyLevel(): bool
    {
        return $this->branchId === 0;
    }

    /** Apply the selected branch filter to any query builder. */
    private function branchFilter(): \Closure
    {
        return $this->isCompanyLevel()
            ? fn ($q) => $q->whereNull('branch_id')
            : fn ($q) => $q->where('branch_id', $this->branchId);
    }

    private function effectiveBranchId(): ?int
    {
        return $this->isCompanyLevel() ? null : (int) $this->branchId;
    }

    private function formatDuration(?Carbon $clockIn, ?Carbon $clockOut): ?string
    {
        if (!$clockIn || !$clockOut) {
            return null;
        }

        $minutes = $clockIn->diffInMinutes($clockOut, false);
        if ($minutes <= 0) {
            return null;
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return $hours . 'h ' . $mins . 'm';
        }
        if ($hours > 0) {
            return $hours . 'h';
        }
        return $mins . 'm';
    }

    public function open(int $employeeId): void
    {
        $this->authorize('Manage Attendance');

        $this->editingEmployeeId = $employeeId;
        $this->resetModalForm();

        $log = AttendanceLog::query()
            ->where('employee_id', $employeeId)
            ->where('date', $this->date)
            ->when($this->branchId !== null, fn($q) => $q->tap($this->branchFilter()))
            ->first();

        if ($log) {
            $this->shift_id = $log->shift_id;
            $this->status = (string) $log->status;
            $this->clock_in_at = $log->clock_in_at?->format('Y-m-d\TH:i');
            $this->clock_out_at = $log->clock_out_at?->format('Y-m-d\TH:i');
            $this->late_minutes = (int) $log->late_minutes;
            $this->note = $log->note;
        } else {
            $this->shift_id = $this->shiftId;
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('Manage Attendance');

        $this->validate([
            'date' => ['required', 'date'],
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
            'editingEmployeeId' => ['required', 'integer', Rule::exists('hrm_employees', 'id')->where(fn($q) => $q->where('restaurant_id', restaurant()->id))],
            'shift_id' => ['nullable', 'integer', Rule::exists('hrm_shifts', 'id')->where(fn($q) => $q->where('restaurant_id', restaurant()->id))],
            'status' => ['required', 'string', 'max:50'],
            'clock_in_at' => ['nullable', 'date'],
            'clock_out_at' => ['nullable', 'date', 'after:clock_in_at'],
            'late_minutes' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        $employee = Employee::query()->findOrFail($this->editingEmployeeId);

        AttendanceLog::updateOrCreate(
            [
                'restaurant_id' => restaurant()->id,
                'employee_id' => $employee->id,
                'date' => $this->date,
            ],
            [
                'branch_id' => $this->effectiveBranchId(),
                'shift_id' => $this->shift_id,
                'clock_in_at' => $this->clock_in_at ? Carbon::parse($this->clock_in_at) : null,
                'clock_out_at' => $this->clock_out_at ? Carbon::parse($this->clock_out_at) : null,
                'status' => $this->status,
                'late_minutes' => $this->late_minutes,
                'note' => $this->note,
            ]
        );

        $this->showModal = false;
        $this->editingEmployeeId = null;
    }

    public function exportExcel()
    {
        $this->authorize('Manage Attendance');

        if ($this->branchId === null) {
            return null;
        }

        $branchName = $this->isCompanyLevel()
            ? 'Company Level'
            : DB::table('branches')->where('id', $this->branchId)->value('name');

        $employees = Employee::query()
            ->where('restaurant_id', restaurant()->id)
            ->availableAtBranch($this->branchId)
            ->orderBy('name')
            ->get(['id', 'name', 'staff_code']);

        $logsByEmployee = AttendanceLog::query()
            ->with('shift:id,name')
            ->where('restaurant_id', restaurant()->id)
            ->tap($this->branchFilter())
            ->where('date', $this->date)
            ->get()
            ->keyBy('employee_id');

        $isPastDate = Carbon::parse($this->date)->startOfDay()->lt(now()->startOfDay());

        $rows = $employees->map(function ($e) use ($logsByEmployee, $branchName, $isPastDate) {
            /** @var \Modules\Hrm\Entities\AttendanceLog|null $log */
            $log = $logsByEmployee->get($e->id);
            $duration = $log ? $this->formatDuration($log->clock_in_at, $log->clock_out_at) : null;
            $status = $log?->status ?? ($isPastDate ? 'absent' : null);

            return [
                'date' => $this->date,
                'branch' => $branchName,
                'employee_id' => $e->id,
                'employee_name' => $e->name,
                'staff_code' => $e->staff_code,
                'shift' => $log?->shift?->name,
                'status' => $status,
                'clock_in_at' => $log?->clock_in_at?->format('Y-m-d H:i:s'),
                'clock_out_at' => $log?->clock_out_at?->format('Y-m-d H:i:s'),
                'late_minutes' => $log?->late_minutes,
                'work_duration' => $duration,
                'note' => $log?->note,
            ];
        })->all();

        return Excel::download(new AttendanceDailyExport($rows), 'attendance-' . $this->date . '.xlsx');
    }

    public function exportRangeExcel()
    {
        $this->authorize('Manage Attendance');

        if ($this->branchId === null) {
            return null;
        }

        $from = $this->fromDate ?: $this->date;
        $to = $this->toDate ?: $this->date;

        $branchName = $this->isCompanyLevel()
            ? 'Company Level'
            : DB::table('branches')->where('id', $this->branchId)->value('name');

        $logs = AttendanceLog::query()
            ->with(['employee:id,name,staff_code', 'shift:id,name'])
            ->where('restaurant_id', restaurant()->id)
            ->tap($this->branchFilter())
            ->whereBetween('date', [$from, $to])
            ->when($this->shiftId, fn($q) => $q->where('shift_id', $this->shiftId))
            ->when($this->search, function ($q) {
                $q->whereHas('employee', function ($q2) {
                    $q2->where('name', 'like', "%{$this->search}%")
                        ->orWhere('staff_code', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('date')
            ->orderBy('employee_id')
            ->get();

        $rows = $logs->map(function (AttendanceLog $log) use ($branchName) {
            return [
                'date' => $log->date?->format('Y-m-d') ?? (string) $log->getRawOriginal('date'),
                'branch' => $branchName,
                'employee_name' => $log->employee?->name,
                'staff_code' => $log->employee?->staff_code,
                'shift' => $log->shift?->name,
                'status' => $log->status,
                'clock_in_at' => $log->clock_in_at?->format('Y-m-d H:i:s'),
                'clock_out_at' => $log->clock_out_at?->format('Y-m-d H:i:s'),
                'late_minutes' => $log->late_minutes,
                'work_duration' => $this->formatDuration($log->clock_in_at, $log->clock_out_at),
                'note' => $log->note,
            ];
        })->all();

        $fileName = 'attendance-' . $from . '-to-' . $to . '.xlsx';
        return Excel::download(new AttendanceRangeExport($rows), $fileName);
    }

    public function exportMonthlyExcel()
    {
        $this->authorize('Manage Attendance');

        if ($this->branchId === null) {
            return null;
        }

        $from = Carbon::parse($this->fromDate ?: now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($this->toDate ?: now()->endOfMonth()->toDateString())->endOfDay();

        if ($from->format('Y-m') !== $to->format('Y-m')) {
            $this->addError('fromDate', 'Monthly export requires a single month range.');
            return null;
        }

        $branchName = $this->isCompanyLevel()
            ? 'Company Level'
            : DB::table('branches')->where('id', $this->branchId)->value('name');

        $employees = Employee::query()
            ->where('restaurant_id', restaurant()->id)
            ->availableAtBranch($this->branchId)
            ->orderBy('name')
            ->get(['id', 'name', 'staff_code']);

        $logs = AttendanceLog::query()
            ->where('restaurant_id', restaurant()->id)
            ->tap($this->branchFilter())
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['employee_id', 'date', 'status']);

        $statusByEmployeeDate = $logs
            ->groupBy('employee_id')
            ->map(fn($rows) => $rows->keyBy(fn($l) => $l->date?->format('Y-m-d') ?? (string) $l->getRawOriginal('date')));

        $dates = [];
        for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
            $dates[] = $d->toDateString();
        }

        $today = now()->startOfDay();

        $presentTotalsPerDay = array_fill_keys($dates, 0);

        $rows = [];
        foreach ($employees as $employee) {
            $presentCount = 0;
            $absentCount = 0;

            $row = [
                'employee_name' => $employee->name,
                'staff_code' => $employee->staff_code,
            ];

            foreach ($dates as $date) {
                $status = $statusByEmployeeDate->get($employee->id)?->get($date)?->status;
                if (!$status && Carbon::parse($date)->startOfDay()->lt($today)) {
                    $status = 'absent';
                }
                $code = $this->statusCode($status);
                $row[$date] = $code;

                if ($this->isPresentStatus($status)) {
                    $presentCount++;
                    $presentTotalsPerDay[$date]++;
                }
                if ($this->isAbsentStatus($status)) {
                    $absentCount++;
                }
            }

            $row['total_present'] = $presentCount;
            $row['total_absent_leave'] = $absentCount;
            $rows[] = $row;
        }

        $totalsRow = [
            'employee_name' => 'TOTAL PRESENT',
            'staff_code' => '',
        ];
        foreach ($dates as $date) {
            $totalsRow[$date] = $presentTotalsPerDay[$date] ?? 0;
        }
        $totalsRow['total_present'] = array_sum($presentTotalsPerDay);
        $totalsRow['total_absent_leave'] = '';
        $rows[] = $totalsRow;

        $monthLabel = $from->format('F Y');
        $title = 'Monthly Attendance Report - ' . $monthLabel . ' (' . $branchName . ')';
        $fileName = 'attendance-monthly-' . $from->format('Y-m') . '.xlsx';

        return Excel::download(new AttendanceMonthlyMatrixExport($title, $dates, $rows), $fileName);
    }

    private function statusCode(?string $status): string
    {
        return match ((string) $status) {
            'present' => 'P',
            'late' => 'LT',
            'half_day' => 'HD',
            'absent' => 'A',
            'leave' => 'LV',
            '' => '',
            default => strtoupper((string) $status),
        };
    }

    public function downloadImportTemplate()
    {
        $this->authorize('Manage Attendance');

        return Excel::download(new AttendanceImportTemplateExport(), 'attendance-import-template.xlsx');
    }

    public function importExcel(): void
    {
        $this->authorize('Manage Attendance');

        $this->validate([
            'branchId' => ['required', 'integer', Rule::exists('branches', 'id')->where(fn($q) => $q->where('restaurant_id', restaurant()->id))],
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $path = $this->importFile->store('imports', 'local');
        $fullPath = Storage::disk('local')->path($path);

        $import = new AttendanceImport(restaurant()->id, (int) $this->branchId);
        Excel::import($import, $fullPath);

        Storage::disk('local')->delete($path);

        $r = $import->results();
        $this->importMessage = "Imported {$r['imported']} rows. Skipped {$r['skipped']} (missing employee: {$r['skipped_missing_employee']}, missing date: {$r['skipped_missing_date']}). Failed {$r['failed']}.";

        $this->importFile = null;
    }

    public function confirmClear(int $employeeId): void
    {
        $this->authorize('Manage Attendance');

        $this->clearEmployeeId = $employeeId;
        $this->showClearModal = true;
    }

    public function clear(): void
    {
        $this->authorize('Manage Attendance');

        if (!$this->clearEmployeeId || $this->branchId === null) {
            $this->showClearModal = false;
            return;
        }

        AttendanceLog::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('employee_id', $this->clearEmployeeId)
            ->tap($this->branchFilter())
            ->where('date', $this->date)
            ->delete();

        $this->showClearModal = false;
        $this->clearEmployeeId = null;
    }

    public function cancelClear(): void
    {
        $this->showClearModal = false;
        $this->clearEmployeeId = null;
    }

    private function resetModalForm(): void
    {
        $this->shift_id = null;
        $this->status = 'present';
        $this->clock_in_at = null;
        $this->clock_out_at = null;
        $this->late_minutes = 0;
        $this->note = null;
    }

    public function render()
    {
        $shifts = Shift::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('is_active', true)
            ->when($this->branchId !== null, function ($q) {
                if ($this->isCompanyLevel()) {
                    $q->whereNull('branch_id');
                } else {
                    $q->where(function ($q2) {
                        $q2->whereNull('branch_id')
                            ->orWhere('branch_id', $this->branchId);
                    });
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);

        $employees = collect();
        $attendanceByEmployee = [];
        $workDurationByEmployee = [];

        $rangeLogs = null;
        $allMatrixDates = [];
        $allMatrixRows = [];
        $allTotalsPerDay = [];

        $summaryShift = [
            'present' => collect(),
            'absent' => collect(),
        ];

        $byDateSummary = [];

        if ($this->branchId !== null) {
            $employees = Employee::query()
                ->where('restaurant_id', restaurant()->id)
                ->availableAtBranch($this->branchId)
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(25);

            $attendanceByEmployee = AttendanceLog::query()
                ->with('shift:id,name')
                ->where('restaurant_id', restaurant()->id)
                ->tap($this->branchFilter())
                ->where('date', $this->date)
                ->get()
                ->keyBy('employee_id');

            $workDurationByEmployee = $attendanceByEmployee
                ->map(fn($log) => $this->formatDuration($log->clock_in_at, $log->clock_out_at) ?: '—')
                ->all();

            $attendanceByEmployee = $attendanceByEmployee->all();

            if ($this->tab === 'byShift' && $this->summaryShiftId) {
                $logs = AttendanceLog::query()
                    ->with('employee:id,name')
                    ->where('restaurant_id', restaurant()->id)
                    ->tap($this->branchFilter())
                    ->where('date', $this->date)
                    ->where('shift_id', $this->summaryShiftId)
                    ->get();

                $summaryShift = [
                    'present' => $logs->filter(fn($l) => $this->isPresentStatus($l->status))->pluck('employee.name')->values(),
                    'absent' => $logs->filter(fn($l) => $this->isAbsentStatus($l->status))->pluck('employee.name')->values(),
                ];
            }

            if ($this->tab === 'byDate') {
                $from = $this->fromDate ?: $this->date;
                $to = $this->toDate ?: $this->date;

                $logs = AttendanceLog::query()
                    ->with('employee:id,name')
                    ->where('restaurant_id', restaurant()->id)
                    ->tap($this->branchFilter())
                    ->whereBetween('date', [$from, $to])
                    ->orderBy('date')
                    ->get();

                $byDateSummary = $logs
                    ->groupBy(fn($l) => $l->date?->format('Y-m-d') ?? (string) $l->getRawOriginal('date'))
                    ->map(function ($items) {
                        $present = $items->filter(fn($l) => $this->isPresentStatus($l->status))->pluck('employee.name')->filter()->values();
                        $absent = $items->filter(fn($l) => $this->isAbsentStatus($l->status))->pluck('employee.name')->filter()->values();

                        return [
                            'present_count' => $present->count(),
                            'absent_count' => $absent->count(),
                            'present_names' => $present,
                            'absent_names' => $absent,
                        ];
                    })
                    ->toArray();
            }

            if ($this->tab === 'all') {
                $from = $this->fromDate ?: $this->date;
                $to = $this->toDate ?: $this->date;

                $fromC = Carbon::parse($from)->startOfDay();
                $toC = Carbon::parse($to)->startOfDay();
                if ($toC->lt($fromC)) {
                    [$fromC, $toC] = [$toC, $fromC];
                }

                $days = $fromC->diffInDays($toC) + 1;

                // For weekly/monthly ranges, matrix view is more readable.
                // Keep a safety fallback to the log list for large ranges.
                if ($days <= 31) {
                    for ($d = $fromC->copy(); $d->lte($toC); $d->addDay()) {
                        $allMatrixDates[] = $d->toDateString();
                    }

                    $today = now()->startOfDay();

                    $employeesAll = Employee::query()
                        ->where('restaurant_id', restaurant()->id)
                        ->availableAtBranch($this->branchId)
                        ->when($this->search, function ($q) {
                            $q->where('name', 'like', "%{$this->search}%")
                                ->orWhere('staff_code', 'like', "%{$this->search}%");
                        })
                        ->orderBy('name')
                        ->get(['id', 'name', 'staff_code']);

                    $logs = AttendanceLog::query()
                        ->where('restaurant_id', restaurant()->id)
                        ->tap($this->branchFilter())
                        ->whereBetween('date', [$fromC->toDateString(), $toC->toDateString()])
                        ->when($this->shiftId, fn($q) => $q->where('shift_id', $this->shiftId))
                        ->get(['employee_id', 'date', 'status']);

                    $statusByEmployeeDate = $logs
                        ->groupBy('employee_id')
                        ->map(fn($rows) => $rows->keyBy(fn($l) => $l->date?->format('Y-m-d') ?? (string) $l->getRawOriginal('date')));

                    $allTotalsPerDay = array_fill_keys($allMatrixDates, 0);

                    foreach ($employeesAll as $employee) {
                        $presentCount = 0;
                        $absentCount = 0;

                        $row = [
                            'employee_name' => $employee->name,
                            'staff_code' => $employee->staff_code,
                        ];

                        foreach ($allMatrixDates as $dateKey) {
                            $status = $statusByEmployeeDate->get($employee->id)?->get($dateKey)?->status;
                            if (!$status && Carbon::parse($dateKey)->startOfDay()->lt($today)) {
                                $status = 'absent';
                            }
                            $row[$dateKey] = $this->statusCode($status);

                            if ($this->isPresentStatus($status)) {
                                $presentCount++;
                                $allTotalsPerDay[$dateKey]++;
                            }
                            if ($this->isAbsentStatus($status)) {
                                $absentCount++;
                            }
                        }

                        $row['total_present'] = $presentCount;
                        $row['total_absent_leave'] = $absentCount;
                        $allMatrixRows[] = $row;
                    }
                } else {
                    $rangeLogs = AttendanceLog::query()
                        ->with(['employee:id,name,staff_code', 'shift:id,name'])
                        ->where('restaurant_id', restaurant()->id)
                        ->tap($this->branchFilter())
                        ->whereBetween('date', [$from, $to])
                        ->when($this->shiftId, fn($q) => $q->where('shift_id', $this->shiftId))
                        ->when($this->search, function ($q) {
                            $q->whereHas('employee', function ($q2) {
                                $q2->where('name', 'like', "%{$this->search}%")
                                    ->orWhere('staff_code', 'like', "%{$this->search}%");
                            });
                        })
                        ->orderByDesc('date')
                        ->paginate(25);
                }
            }
        }

        return view('hrm::livewire.daily-attendance', [
            'employees' => $employees,
            'shifts' => $shifts,
            'attendanceByEmployee' => $attendanceByEmployee,
            'workDurationByEmployee' => $workDurationByEmployee,
            'rangeLogs' => $rangeLogs,
            'allMatrixDates' => $allMatrixDates,
            'allMatrixRows' => $allMatrixRows,
            'allTotalsPerDay' => $allTotalsPerDay,
            'summaryShift' => $summaryShift,
            'byDateSummary' => $byDateSummary,
        ])->layout('layouts.app');
    }
}
