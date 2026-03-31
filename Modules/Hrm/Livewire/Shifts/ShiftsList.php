<?php

namespace Modules\Hrm\Livewire\Shifts;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hrm\Entities\Employee;
use Modules\Hrm\Entities\Shift;
use Modules\Hrm\Entities\ShiftAssignment;

class ShiftsList extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';
    public ?int $branchFilterId = null;
    public array $branches = [];

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $branch_id = null; // null = all branches
    public string $name = '';
    public string $start_time = '09:00';
    public string $end_time = '18:00';
    public int $break_minutes = 0;
    public int $grace_minutes = 0;
    public bool $is_active = true;

    public bool $showDeleteModal = false;
    public ?int $deleteId = null;

    // Shift assignments
    public bool $showAssignModal = false;
    public ?int $assignEditingId = null;
    public bool $showAssignDeleteModal = false;
    public ?int $assignDeleteId = null;

    public ?int $assign_branch_id = null;
    public ?int $assign_employee_id = null;
    public ?int $assign_shift_id = null;
    public ?string $assign_from_date = null;
    public ?string $assign_to_date = null;

    protected $queryString = ['search', 'branchFilterId'];

    public function mount(): void
    {
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
        if (in_array($name, ['search', 'branchFilterId'], true)) {
            $this->resetPage();
        }
    }

    public function createAssignment(): void
    {
        $this->authorize('Manage Shift Assignments');

        $this->resetAssignmentForm();
        $this->assign_branch_id = branch()?->id;
        $this->assign_from_date = now()->toDateString();
        $this->assign_to_date = now()->toDateString();
        $this->showAssignModal = true;
    }

    public function editAssignment(int $id): void
    {
        $this->authorize('Manage Shift Assignments');

        $a = ShiftAssignment::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail($id);

        $this->assignEditingId = $a->id;
        $this->assign_branch_id = (int) $a->branch_id;
        $this->assign_employee_id = (int) $a->employee_id;
        $this->assign_shift_id = (int) $a->shift_id;
        $this->assign_from_date = $a->from_date?->toDateString();
        $this->assign_to_date = $a->to_date?->toDateString();

        $this->showAssignModal = true;
    }

    public function saveAssignment(): void
    {
        $this->authorize('Manage Shift Assignments');

        $this->validate([
            'assign_branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where(fn($q) => $q->where('restaurant_id', restaurant()->id))],
            'assign_employee_id' => ['required', 'integer', Rule::exists('hrm_employees', 'id')->where(fn($q) => $q->where('restaurant_id', restaurant()->id))],
            'assign_shift_id' => ['required', 'integer', Rule::exists('hrm_shifts', 'id')->where(fn($q) => $q->where('restaurant_id', restaurant()->id))],
            'assign_from_date' => ['required', 'date'],
            'assign_to_date' => ['required', 'date', 'after_or_equal:assign_from_date'],
        ]);

        $overlap = ShiftAssignment::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('employee_id', (int) $this->assign_employee_id)
            ->when($this->assignEditingId, fn($q) => $q->where('id', '!=', $this->assignEditingId))
            ->where(function ($q) {
                $q->whereBetween('from_date', [$this->assign_from_date, $this->assign_to_date])
                    ->orWhereBetween('to_date', [$this->assign_from_date, $this->assign_to_date])
                    ->orWhere(function ($q2) {
                        $q2->where('from_date', '<=', $this->assign_from_date)
                            ->where('to_date', '>=', $this->assign_to_date);
                    });
            })
            ->exists();

        if ($overlap) {
            $this->addError('assign_from_date', 'This employee already has a shift assignment overlapping this date range.');
            return;
        }

        $assignment = $this->assignEditingId
            ? ShiftAssignment::query()->where('restaurant_id', restaurant()->id)->findOrFail($this->assignEditingId)
            : new ShiftAssignment();

        $assignment->restaurant_id = restaurant()->id;
        $assignment->branch_id = (int) $this->assign_branch_id;
        $assignment->employee_id = (int) $this->assign_employee_id;
        $assignment->shift_id = (int) $this->assign_shift_id;
        $assignment->from_date = $this->assign_from_date;
        $assignment->to_date = $this->assign_to_date;
        $assignment->save();

        $this->showAssignModal = false;
        $this->resetAssignmentForm();
    }

    public function confirmDeleteAssignment(int $id): void
    {
        $this->authorize('Manage Shift Assignments');

        $this->assignDeleteId = $id;
        $this->showAssignDeleteModal = true;
    }

    public function deleteAssignment(): void
    {
        $this->authorize('Manage Shift Assignments');

        if (!$this->assignDeleteId) {
            $this->showAssignDeleteModal = false;
            return;
        }

        $assignment = ShiftAssignment::query()
            ->where('id', $this->assignDeleteId)
            ->where('restaurant_id', restaurant()->id)
            ->first();

        if ($assignment) {
            $assignment->delete();
        }
        $this->showAssignDeleteModal = false;
        $this->assignDeleteId = null;
    }

    public function cancelDeleteAssignment(): void
    {
        $this->showAssignDeleteModal = false;
        $this->assignDeleteId = null;
    }

    public function create(): void
    {
        $this->authorize('Create Shift');

        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('Update Shift');

        $shift = Shift::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail($id);

        $this->editingId = $shift->id;
        $this->branch_id = $shift->branch_id;
        $this->name = (string) $shift->name;
        $this->start_time = substr((string) $shift->getRawOriginal('start_time'), 0, 5);
        $this->end_time = substr((string) $shift->getRawOriginal('end_time'), 0, 5);
        $this->break_minutes = (int) $shift->break_minutes;
        $this->grace_minutes = (int) $shift->grace_minutes;
        $this->is_active = (bool) $shift->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorize('Update Shift');
        } else {
            $this->authorize('Create Shift');
        }

        $this->validate([
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where(fn($q) => $q->where('restaurant_id', restaurant()->id))],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hrm_shifts', 'name')
                    ->where(function ($q) {
                        $q->where('restaurant_id', restaurant()->id);
                        if ($this->branch_id) {
                            $q->where('branch_id', $this->branch_id);
                        } else {
                            $q->whereNull('branch_id');
                        }
                    })
                    ->ignore($this->editingId),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_minutes' => ['required', 'integer', 'min:0'],
            'grace_minutes' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $shift = $this->editingId
            ? Shift::query()->where('restaurant_id', restaurant()->id)->findOrFail($this->editingId)
            : new Shift();

        $shift->restaurant_id = restaurant()->id;
        $shift->branch_id = $this->branch_id;
        $shift->name = $this->name;
        $shift->start_time = $this->start_time;
        $shift->end_time = $this->end_time;
        $shift->break_minutes = $this->break_minutes;
        $shift->grace_minutes = $this->grace_minutes;
        $shift->is_active = $this->is_active;
        $shift->save();

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('Delete Shift');

        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('Delete Shift');

        if (!$this->deleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $shift = Shift::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail($this->deleteId);
        $shift->delete();

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
        $this->name = '';
        $this->start_time = '09:00';
        $this->end_time = '18:00';
        $this->break_minutes = 0;
        $this->grace_minutes = 0;
        $this->is_active = true;
    }

    private function resetAssignmentForm(): void
    {
        $this->assignEditingId = null;
        $this->assign_branch_id = null;
        $this->assign_employee_id = null;
        $this->assign_shift_id = null;
        $this->assign_from_date = null;
        $this->assign_to_date = null;
    }

    public function render()
    {
        $shifts = Shift::query()
            ->where('restaurant_id', restaurant()->id)
            ->with(['branch:id,name'])
            ->when($this->branchFilterId !== null, function ($q) {
                if ($this->branchFilterId === 0) {
                    $q->whereNull('branch_id');
                } else {
                    $q->where('branch_id', $this->branchFilterId);
                }
            })
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        $assignEmployees = collect();
        $assignShifts = collect();
        if ($this->assign_branch_id) {
            $assignEmployees = Employee::query()
                ->where('restaurant_id', restaurant()->id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'staff_code', 'branch_id']);

            $assignShifts = Shift::query()
                ->where('is_active', true)
                ->where('restaurant_id', restaurant()->id)
                ->where(function ($q) {
                    $q->whereNull('branch_id')->orWhere('branch_id', $this->assign_branch_id);
                })
                ->orderBy('name')
                ->get(['id', 'name', 'branch_id']);
        }

        $assignments = ShiftAssignment::query()
            ->where('restaurant_id', restaurant()->id)
            ->with(['employee:id,name,staff_code', 'shift:id,name'])
            ->when($this->branchFilterId, fn($q) => $q->where('branch_id', $this->branchFilterId))
            ->orderByDesc('from_date')
            ->paginate(15, ['*'], 'assignmentsPage');

        return view('hrm::livewire.shifts-list', [
            'shifts' => $shifts,
            'assignments' => $assignments,
            'assignEmployees' => $assignEmployees,
            'assignShifts' => $assignShifts,
        ])->layout('layouts.app');
    }
}
