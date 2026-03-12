<?php

namespace Modules\Hrm\Livewire\Leave;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hrm\Entities\Employee;
use Modules\Hrm\Entities\LeaveRequest;
use Modules\Hrm\Entities\LeaveType;

class LeaveRequestsList extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';
    public string $status = '';
    public ?string $from = null;
    public ?string $to = null;

    public array $branches = [];
    public ?int $branchId = null;

    public array $employees = [];

    public array $leaveTypes = [];

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $branch_id = null;
    public ?int $employee_id = null;
    public ?int $leave_type_id = null;
    public ?string $from_date = null;
    public ?string $to_date = null;
    public string $request_status = 'approved';
    public ?string $reason = null;
    public ?string $note = null;

    public bool $showDeleteModal = false;
    public ?int $deleteId = null;

    protected $queryString = ['search', 'status', 'from', 'to', 'branchId'];

    public function mount(): void
    {
        $this->branchId = $this->branchId ?? (branch()?->id);

        $this->branches = DB::table('branches')
            ->select('id', 'name')
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get()
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])
            ->all();

        $this->refreshLeaveTypes();
        $this->refreshEmployees();
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['search', 'status', 'from', 'to', 'branchId'], true)) {
            $this->resetPage();
        }

        if ($name === 'branchId') {
            $this->refreshLeaveTypes();
        }

        if ($name === 'branch_id') {
            $this->refreshEmployees();
        }
    }

    private function refreshLeaveTypes(): void
    {
        $this->leaveTypes = LeaveType::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'max_per_year'])
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'max_per_year' => (int) $t->max_per_year])
            ->all();
    }

    private function refreshEmployees(): void
    {
        $branchId = $this->branch_id ?: $this->branchId;

        $this->employees = Employee::query()
            ->where('restaurant_id', restaurant()->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', (int) $branchId))
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'staff_code'])
            ->map(fn ($e) => ['id' => $e->id, 'name' => $e->name, 'staff_code' => $e->staff_code])
            ->all();
    }

    public function create(): void
    {
        $this->authorize('Manage Leave Requests');

        $this->resetForm();
        $this->branch_id = $this->branchId ?? branch()?->id;
        $this->refreshEmployees();
        $this->from_date = now()->toDateString();
        $this->to_date = now()->toDateString();
        $this->request_status = 'approved';
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('Manage Leave Requests');

        $r = LeaveRequest::query()->with(['employee', 'leaveType'])->findOrFail($id);

        $this->editingId = $r->id;
        $this->branch_id = (int) $r->branch_id;
        $this->refreshEmployees();
        $this->employee_id = (int) $r->employee_id;
        $this->leave_type_id = (int) $r->leave_type_id;
        $this->from_date = $r->from_date?->toDateString();
        $this->to_date = $r->to_date?->toDateString();
        $this->request_status = (string) $r->status;
        $this->reason = $r->reason;
        $this->note = $r->note;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('Manage Leave Requests');

        $this->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where(fn ($q) => $q->where('restaurant_id', restaurant()->id))],
            'employee_id' => ['required', 'integer', Rule::exists('hrm_employees', 'id')->where(fn ($q) => $q->where('restaurant_id', restaurant()->id))],
            'leave_type_id' => ['required', 'integer', Rule::exists('hrm_leave_types', 'id')->where(fn ($q) => $q->where('restaurant_id', restaurant()->id))],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'request_status' => ['required', 'string', Rule::in(['pending', 'approved', 'rejected', 'cancelled'])],
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $employee = Employee::query()->findOrFail((int) $this->employee_id);
        if ((int) $employee->branch_id !== (int) $this->branch_id) {
            $this->addError('employee_id', 'Selected employee is not in the selected branch.');
            return;
        }

        $leaveType = LeaveType::query()->where('restaurant_id', restaurant()->id)->findOrFail((int) $this->leave_type_id);

        // Enforce max_per_year as max leave DAYS per calendar year (0 = unlimited)
        if ((int) $leaveType->max_per_year > 0) {
            $year = (int) date('Y', strtotime((string) $this->from_date));
            $from = \Carbon\Carbon::parse($this->from_date)->startOfDay();
            $to = \Carbon\Carbon::parse($this->to_date)->startOfDay();
            $requestedDays = (int) $from->diffInDays($to) + 1;

            $alreadyApprovedDays = LeaveRequest::query()
                ->where('restaurant_id', restaurant()->id)
                ->where('employee_id', (int) $this->employee_id)
                ->where('leave_type_id', (int) $this->leave_type_id)
                ->where('status', 'approved')
                ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                ->whereYear('from_date', $year)
                ->get(['from_date', 'to_date'])
                ->sum(function ($row) {
                    $from = \Carbon\Carbon::parse($row->from_date)->startOfDay();
                    $to = \Carbon\Carbon::parse($row->to_date)->startOfDay();

                    return (int) $from->diffInDays($to) + 1;
                });

            if (($alreadyApprovedDays + $requestedDays) > (int) $leaveType->max_per_year && $this->request_status === 'approved') {
                $this->addError('to_date', 'Leave limit exceeded for this leave type (max per year).');
                return;
            }
        }

        $r = $this->editingId
            ? LeaveRequest::query()->findOrFail($this->editingId)
            : new LeaveRequest();

        $r->restaurant_id = restaurant()->id;
        $r->branch_id = (int) $this->branch_id;
        $r->employee_id = (int) $this->employee_id;
        $r->leave_type_id = (int) $this->leave_type_id;
        $r->from_date = $this->from_date;
        $r->to_date = $this->to_date;
        $r->status = $this->request_status;
        $r->reason = $this->reason;
        $r->note = $this->note;

        if (!$r->exists) {
            $r->created_by = user()->id;
        }

        if ($this->request_status === 'approved') {
            $r->approved_by = user()->id;
            $r->approved_at = now();
        } else {
            $r->approved_by = null;
            $r->approved_at = null;
        }

        $r->save();

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('Manage Leave Requests');

        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('Manage Leave Requests');

        if (!$this->deleteId) {
            $this->showDeleteModal = false;
            return;
        }

        LeaveRequest::query()->where('id', $this->deleteId)->delete();

        $this->showDeleteModal = false;
        $this->deleteId = null;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deleteId = null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->branch_id = null;
        $this->employee_id = null;
        $this->leave_type_id = null;
        $this->from_date = null;
        $this->to_date = null;
        $this->request_status = 'approved';
        $this->reason = null;
        $this->note = null;
        $this->employees = [];
    }

    public function render()
    {
        $rows = LeaveRequest::query()
            ->with(['employee:id,name,staff_code', 'leaveType:id,name'])
            ->where('restaurant_id', restaurant()->id)
            ->when($this->branchId, fn ($q) => $q->where('branch_id', (int) $this->branchId))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->from, fn ($q) => $q->whereDate('to_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('from_date', '<=', $this->to))
            ->when($this->search, function ($q) {
                $q->whereHas('employee', function ($q2) {
                    $q2->where('name', 'like', "%{$this->search}%")
                        ->orWhere('staff_code', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('from_date')
            ->paginate(15);

        return view('hrm::livewire.leave.leave-requests-list', [
            'rows' => $rows,
        ])->layout('layouts.app');
    }
}
