<?php

namespace Modules\Hotel\Livewire\Expense;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\HotelExpense;
use Modules\Hotel\Entities\HotelExpenseDepartment;
use Illuminate\Support\Facades\DB;

class HotelExpenseList extends Component
{
    use WithPagination, WithFileUploads, LivewireAlert;

    // --- Tabs ---
    public $activeTab = 'expenses'; // expenses | departments

    // --- Filters ---
    public $search         = '';
    public $statusFilter   = 'all';
    public $departmentFilter = 'all';
    public $dateFrom       = '';
    public $dateTo         = '';

    // --- Create / Edit Form ---
    public $showModal       = false;
    public $editingId       = null;

    public $title           = '';
    public $department_id   = '';
    public $description     = '';
    public $amount          = '';
    public $expense_date    = '';
    public $payment_method  = 'cash';
    public $vendor          = '';
    public $receipt_number  = '';
    public $status          = 'paid';

    // --- Delete Confirm ---
    public $pendingDeleteId = null;

    // --- Department Form ---
    public $showDeptModal = false;
    public $editingDeptId = null;
    public $deptName = '';
    public $deptDescription = '';
    public $pendingDeleteDeptId = null;

    protected function rules(): array
    {
        return [
            'title'          => 'required|string|max:255',
            'department_id'  => 'required|integer|exists:hotel_expense_departments,id',
            'description'    => 'nullable|string|max:1000',
            'amount'         => 'required|numeric|min:0.01',
            'expense_date'   => 'required|date',
            'payment_method' => 'required|string',
            'vendor'         => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:100',
            'status'         => 'required|in:paid,pending,cancelled',
        ];
    }

    public function mount()
    {
        abort_unless(user_can('view_hotel_expenses'), 403);
        $this->expense_date = Carbon::today()->format('Y-m-d');
        $this->dateFrom     = Carbon::today()->startOfMonth()->format('Y-m-d');
        $this->dateTo       = Carbon::today()->format('Y-m-d');
    }

    // --- Summary Totals ---

    public function getSummaryProperty()
    {
        $query = HotelExpense::whereBetween('expense_date', [$this->dateFrom ?: '2000-01-01', $this->dateTo ?: now()->toDateString()]);

        return [
            'total'    => $query->sum('amount'),
            'paid'     => $query->clone()->where('status', 'paid')->sum('amount'),
            'pending'  => $query->clone()->where('status', 'pending')->sum('amount'),
            'count'    => $query->clone()->count(),
        ];
    }

    // --- CRUD ---

    public function openCreate()
    {
        abort_unless(user_can('create_hotel_expense'), 403);
        $this->resetForm();
        
        $otherDeptId = HotelExpenseDepartment::where('restaurant_id', restaurant()->id)
            ->where('slug', 'other')
            ->value('id');
        $this->department_id = $otherDeptId ?: HotelExpenseDepartment::where('restaurant_id', restaurant()->id)->value('id') ?: '';

        $this->showModal = true;
    }

    public function openEdit($id)
    {
        abort_unless(user_can('edit_hotel_expense'), 403);
        $expense = HotelExpense::findOrFail($id);

        $this->editingId      = $expense->id;
        $this->title          = $expense->title;
        $this->department_id  = $expense->department_id;
        $this->description    = $expense->description;
        $this->amount         = $expense->amount;
        $this->expense_date   = $expense->expense_date->format('Y-m-d');
        $this->payment_method = $expense->payment_method;
        $this->vendor         = $expense->vendor;
        $this->receipt_number = $expense->receipt_number;
        $this->status         = $expense->status;

        $this->showModal = true;
    }

    public function save()
    {
        if ($this->editingId) {
            abort_unless(user_can('edit_hotel_expense'), 403);
        } else {
            abort_unless(user_can('create_hotel_expense'), 403);
        }

        $data = $this->validate();
        $data['branch_id']     = branch()->id;
        $data['restaurant_id'] = restaurant()->id;

        if ($this->editingId) {
            HotelExpense::where('id', $this->editingId)
                ->update($data);
            $this->alert('success', 'Expense updated successfully.');
        } else {
            HotelExpense::create(array_merge($data, [
                'created_by_user_id' => auth()->id(),
            ]));
            $this->alert('success', 'Expense added successfully.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete($id)
    {
        abort_unless(user_can('delete_hotel_expense'), 403);
        $this->pendingDeleteId = $id;
        $this->alert('warning', 'Delete this expense record?', [
            'showConfirmButton' => true,
            'showCancelButton'  => true,
            'confirmButtonText' => 'Yes, Delete',
            'cancelButtonText'  => 'No',
            'onConfirmed'       => 'deleteExpenseConfirmed',
        ]);
    }

    #[\Livewire\Attributes\On('deleteExpenseConfirmed')]
    public function deleteExpense($id = null)
    {
        $id = $id ?? $this->pendingDeleteId;
        abort_unless(user_can('delete_hotel_expense'), 403);

        HotelExpense::where('id', $id)->delete();

        $this->pendingDeleteId = null;
        $this->alert('success', 'Expense deleted.');
    }

    private function resetForm()
    {
        $this->editingId      = null;
        $this->title          = '';
        $this->department_id  = '';
        $this->description    = '';
        $this->amount         = '';
        $this->expense_date   = Carbon::today()->format('Y-m-d');
        $this->payment_method = 'cash';
        $this->vendor         = '';
        $this->receipt_number = '';
        $this->status         = 'paid';
        $this->resetErrorBag();
    }

    // --- Department CRUD ---

    public function openCreateDept()
    {
        abort_unless(user_can('create_hotel_expense'), 403);
        $this->resetDeptForm();
        $this->showDeptModal = true;
    }

    public function openEditDept($id)
    {
        abort_unless(user_can('edit_hotel_expense'), 403);
        $dept = HotelExpenseDepartment::findOrFail($id);
        if ($dept->is_system) {
            $this->alert('error', 'System departments cannot be modified.');
            return;
        }

        $this->editingDeptId = $dept->id;
        $this->deptName = $dept->name;
        $this->deptDescription = $dept->description;
        $this->showDeptModal = true;
    }

    public function saveDept()
    {
        if ($this->editingDeptId) {
            abort_unless(user_can('edit_hotel_expense'), 403);
        } else {
            abort_unless(user_can('create_hotel_expense'), 403);
        }

        $this->validate([
            'deptName' => 'required|string|max:255',
            'deptDescription' => 'nullable|string|max:1000',
        ]);

        $data = [
            'name' => $this->deptName,
            'description' => $this->deptDescription,
            'branch_id' => branch()->id,
            'restaurant_id' => restaurant()->id,
        ];

        if ($this->editingDeptId) {
            $dept = HotelExpenseDepartment::findOrFail($this->editingDeptId);
            if ($dept->is_system) {
                return;
            }
            $dept->update($data);
            $this->alert('success', 'Department updated successfully.');
        } else {
            HotelExpenseDepartment::create($data);
            $this->alert('success', 'Department created successfully.');
        }

        $this->showDeptModal = false;
        $this->resetDeptForm();
    }

    public function confirmDeleteDept($id)
    {
        abort_unless(user_can('delete_hotel_expense'), 403);
        
        $dept = HotelExpenseDepartment::findOrFail($id);
        if ($dept->is_system) {
            $this->alert('error', 'System departments cannot be deleted.');
            return;
        }

        // Check for linked expenses
        $hasExpenses = HotelExpense::where('department_id', $id)->exists();
        if ($hasExpenses) {
            $this->alert('error', 'This department has expenses linked to it and cannot be deleted.');
            return;
        }

        $this->pendingDeleteDeptId = $id;
        $this->alert('warning', 'Delete this department?', [
            'showConfirmButton' => true,
            'showCancelButton'  => true,
            'confirmButtonText' => 'Yes, Delete',
            'cancelButtonText'  => 'No',
            'onConfirmed'       => 'deleteDeptConfirmed',
        ]);
    }

    #[\Livewire\Attributes\On('deleteDeptConfirmed')]
    public function deleteDept($id = null)
    {
        $id = $id ?? $this->pendingDeleteDeptId;
        abort_unless(user_can('delete_hotel_expense'), 403);

        $dept = HotelExpenseDepartment::findOrFail($id);
        if ($dept->is_system) {
            return;
        }

        $dept->delete();
        $this->pendingDeleteDeptId = null;
        $this->alert('success', 'Department deleted.');
    }

    private function resetDeptForm()
    {
        $this->editingDeptId = null;
        $this->deptName = '';
        $this->deptDescription = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $restId = restaurant()->id;

        $expenses = HotelExpense::with('departmentRelation')
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('title', 'like', '%' . $this->search . '%')
                       ->orWhere('vendor', 'like', '%' . $this->search . '%')
                       ->orWhere('receipt_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->departmentFilter !== 'all', fn($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->dateFrom, fn($q) => $q->where('expense_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->where('expense_date', '<=', $this->dateTo))
            ->latest('expense_date')
            ->paginate(15);

        $departmentsList = HotelExpenseDepartment::where('restaurant_id', $restId)
            ->orderBy('name')
            ->get();

        $allDepartments = [];
        if ($this->activeTab === 'departments') {
            $allDepartments = HotelExpenseDepartment::where('restaurant_id', $restId)
                ->orderBy('is_system', 'desc')
                ->orderBy('name')
                ->get();
        }

        return view('hotel::livewire.expense.hotel-expense-list', [
            'expenses'       => $expenses,
            'departments'    => $departmentsList->pluck('name', 'id')->toArray(),
            'allDepartments' => $allDepartments,
            'methods'        => HotelExpense::PAYMENT_METHODS,
        ])->layout('layouts.app');
    }
}
