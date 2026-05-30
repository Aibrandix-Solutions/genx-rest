<?php

namespace Modules\Hrm\Livewire\Employees;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hrm\Entities\Department;
use Modules\Hrm\Entities\Designation;
use Modules\Hrm\Entities\Employee;

class EmployeesList extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';
    public ?int $branchId = null;

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $branch_id = null;
    public ?int $user_id = null;
    public ?int $department_id = null;
    public ?int $designation_id = null;

    public ?string $staff_code = null;
    public string $name = '';
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $hire_date = null;
    public string $employment_type = 'full_time';
    public string $basic_salary_per_day = '0';
    public string $basic_salary_per_month = '0';
    public string $status = 'active';
    public bool $is_epf_eligible = true;
    public ?string $note = null;

    /** IDs of extra branches where this employee also works (not the home branch) */
    public array $extraBranchIds = [];

    public bool $showDeleteModal = false;
    public ?int $deleteId = null;

    public array $branches = [];

    protected $queryString = ['search', 'branchId'];

    public function mount(): void
    {
        $this->branches = DB::table('branches')
            ->select('id', 'name')
            ->when(restaurant(), fn($q) => $q->where('restaurant_id', restaurant()->id))
            ->orderBy('name')
            ->get()
            ->map(fn($b) => ['id' => $b->id, 'name' => $b->name])
            ->all();

        $this->branchId = $this->branchId ?? (branch()?->id);
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['search', 'branchId'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->authorize('Create Employee');

        $this->resetForm();
        $this->branch_id = branch()?->id;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('Update Employee');

        $employee = Employee::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail($id);

        $this->editingId = $employee->id;
        $this->branch_id = $employee->branch_id;
        $this->user_id = $employee->user_id;
        $this->department_id = $employee->department_id;
        $this->designation_id = $employee->designation_id;
        $this->staff_code = $employee->staff_code;
        $this->name = (string) $employee->name;
        $this->email = $employee->email;
        $this->phone = $employee->phone;
        $this->hire_date = $employee->hire_date?->toDateString();
        $this->employment_type = (string) $employee->employment_type;
        $this->basic_salary_per_day = (string) ($employee->basic_salary_per_day ?? '0');
        $this->basic_salary_per_month = (string) ($employee->basic_salary_per_month ?? '0');
        $this->status = (string) $employee->status;
        $this->is_epf_eligible = (bool) ($employee->is_epf_eligible ?? true);
        $this->note = $employee->note;
        $this->extraBranchIds = $employee->extraBranches()->pluck('branches.id')->map(fn($id) => (string) $id)->all();

        $this->showModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorize('Update Employee');
        } else {
            $this->authorize('Create Employee');
        }

        $this->staff_code = $this->staff_code !== null ? trim((string) $this->staff_code) : null;
        if (!$this->staff_code) {
            $this->staff_code = Employee::generateStaffCode((int) restaurant()->id);
        }

        $this->validate([
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where(fn($q) => $q->where('restaurant_id', restaurant()->id))],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn($q) => $q->where('restaurant_id', restaurant()->id))],
            'department_id' => ['nullable', 'integer', Rule::exists('hrm_departments', 'id')->where(fn($q) => $q->where('restaurant_id', restaurant()->id))],
            'designation_id' => ['nullable', 'integer', Rule::exists('hrm_designations', 'id')->where(fn($q) => $q->where('restaurant_id', restaurant()->id))],
            'extraBranchIds' => ['nullable', 'array'],
            'extraBranchIds.*' => ['integer', Rule::exists('branches', 'id')->where(fn($q) => $q->where('restaurant_id', restaurant()->id))],
            'staff_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hrm_employees', 'staff_code')
                    ->where(fn($q) => $q->where('restaurant_id', restaurant()->id))
                    ->ignore($this->editingId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'hire_date' => ['nullable', 'date'],
            'employment_type' => ['required', 'string', 'max:50'],
            'basic_salary_per_day' => ['required', 'numeric', 'min:0'],
            'basic_salary_per_month' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
        ]);

        $employee = $this->editingId
            ? Employee::query()->where('restaurant_id', restaurant()->id)->findOrFail($this->editingId)
            : new Employee();

        $employee->restaurant_id = restaurant()->id;
        $employee->branch_id = $this->branch_id ? (int) $this->branch_id : null;
        $employee->user_id = $this->user_id;
        $employee->department_id = $this->department_id;
        $employee->designation_id = $this->designation_id;
        $employee->staff_code = $this->staff_code;
        $employee->name = $this->name;
        $employee->email = $this->email;
        $employee->phone = $this->phone;
        $employee->hire_date = $this->hire_date;
        $employee->employment_type = $this->employment_type;
        $employee->basic_salary_per_day = (float) $this->basic_salary_per_day;
        $employee->basic_salary_per_month = (float) $this->basic_salary_per_month;
        $employee->status = $this->status;
        $employee->is_epf_eligible = $this->is_epf_eligible;
        $employee->note = $this->note;
        $employee->save();

        // Sync extra branches (exclude home branch to avoid confusion)
        $extraIds = array_filter(
            array_map('intval', $this->extraBranchIds),
            fn ($id) => $id > 0 && $id !== (int) $this->branch_id
        );
        $employee->extraBranches()->sync($extraIds);

        $this->syncCustomerForEmployee($employee);

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('Delete Employee');

        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('Delete Employee');

        if (!$this->deleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $employee = Employee::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail($this->deleteId);

        Customer::query()
            ->where('employee_id', $employee->id)
            ->update([
                'employee_id' => null,
                'is_employee' => false,
            ]);

        $employee->delete();

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
        $this->user_id = null;
        $this->department_id = null;
        $this->designation_id = null;
        $this->staff_code = null;
        $this->name = '';
        $this->email = null;
        $this->phone = null;
        $this->hire_date = null;
        $this->employment_type = 'full_time';
        $this->basic_salary_per_day = '0';
        $this->basic_salary_per_month = '0';
        $this->status = 'active';
        $this->is_epf_eligible = true;
        $this->note = null;
        $this->extraBranchIds = [];
    }

    private function syncCustomerForEmployee(Employee $employee): void
    {
        $customer = Customer::query()
            ->where('employee_id', $employee->id)
            ->first();

        if (!$customer && $employee->email) {
            $customer = Customer::query()
                ->where('restaurant_id', $employee->restaurant_id)
                ->where('email', $employee->email)
                ->whereNull('employee_id')
                ->first();
        }

        if (!$customer && $employee->phone) {
            $matches = Customer::query()
                ->where('restaurant_id', $employee->restaurant_id)
                ->where('phone', $employee->phone)
                ->whereNull('employee_id')
                ->limit(2)
                ->get();

            if ($matches->count() === 1) {
                $customer = $matches->first();
            }
        }

        if (!$customer) {
            $customer = new Customer();
            $customer->restaurant_id = $employee->restaurant_id;
        }

        $customer->name = $employee->name;

        if ($employee->phone) {
            $customer->phone = $employee->phone;
        }

        if ($employee->email) {
            $customer->email = $employee->email;
        }

        $customer->is_employee = true;
        $customer->employee_id = $employee->id;
        $customer->save();
    }

    public function render()
    {
        $departments = Department::query()
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get(['id', 'name']);
        $designations = Designation::query()
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::query()
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email']);

        $employees = Employee::query()
            ->where('restaurant_id', restaurant()->id)
            ->with([
                'branch:id,name',
                'extraBranches:id,name',
                'department:id,name',
                'designation:id,name',
                'user:id,name,email',
            ])
            ->when($this->branchId !== null, fn($q) => $q->availableAtBranch($this->branchId))
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('name', 'like', "%{$this->search}%")
                        ->orWhere('staff_code', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15);

        return view('hrm::livewire.employees-list', [
            'employees' => $employees,
            'departments' => $departments,
            'designations' => $designations,
            'users' => $users,
        ])->layout('layouts.app');
    }
}
