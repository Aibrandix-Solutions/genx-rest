<?php

namespace Modules\Hotel\Livewire\Expense;

use App\Helper\Files;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Modules\Hotel\Entities\HotelExpense;
use Modules\Hotel\Entities\HotelExpenseDepartment;
use Modules\Hotel\Entities\HotelExpensePayment;

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

    protected $queryString = [
        'statusFilter' => ['except' => 'all'],
    ];

    // --- Create / Edit Form ---
    public $showModal       = false;
    public $editingId       = null;

    public $title           = '';
    public $department_id   = '';
    public $description     = '';
    public $amount          = '';
    public $expense_date    = '';
    public $due_date        = '';
    public $payment_method  = 'cash';
    public $vendor          = '';
    public $receipt_number  = '';
    public $status          = 'pending';
    public $receipt_file;

    // --- Payment Modal ---
    public $showPaymentModal = false;
    public $paymentExpenseId = null;
    public $payment_amount = '';
    public $payment_method_entry = 'cash';
    public $payment_reference_number = '';
    public $payment_paid_at = '';
    public $payment_notes = '';
    public $payment_receipt_file;

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
            'due_date'       => 'nullable|date|after_or_equal:expense_date',
            'payment_method' => 'required|string',
            'vendor'         => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:100',
            'status'         => 'required|in:paid,pending,partial,cancelled',
            'receipt_file'   => 'nullable|file|max:5120',
        ];
    }

    protected function paymentRules(): array
    {
        return [
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_method_entry' => 'required|string',
            'payment_reference_number' => 'nullable|string|max:100',
            'payment_paid_at' => 'required|date',
            'payment_notes' => 'nullable|string|max:1000',
            'payment_receipt_file' => 'nullable|file|max:5120',
        ];
    }

    public function mount()
    {
        abort_unless(user_can('view_hotel_expenses'), 403);
        $this->expense_date = Carbon::today()->format('Y-m-d');
        $this->due_date     = Carbon::today()->format('Y-m-d');
        $this->payment_paid_at = Carbon::now()->format('Y-m-d\TH:i');
        $this->dateFrom     = Carbon::today()->startOfMonth()->format('Y-m-d');
        $this->dateTo       = Carbon::today()->format('Y-m-d');
    }

    // --- Summary Totals ---

    public function getSummaryProperty()
    {
        $query = HotelExpense::whereBetween('expense_date', [$this->dateFrom ?: '2000-01-01', $this->dateTo ?: now()->toDateString()])
            ->where('status', '!=', HotelExpense::STATUS_CANCELLED);

        return [
            'total'    => $query->sum(DB::raw('COALESCE(total_amount, amount)')),
            'paid'     => $query->clone()->sum('amount_paid'),
            'pending'  => $query->clone()->sum('balance_due'),
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
        $this->amount         = $expense->total_amount ?? $expense->amount;
        $this->expense_date   = $expense->expense_date->format('Y-m-d');
        $this->due_date       = optional($expense->due_date)->format('Y-m-d');
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
        $statusPreference = $data['status'];
        unset($data['receipt_file'], $data['status']);

        $data['branch_id'] = branch()->id;
        $data['restaurant_id'] = restaurant()->id;
        $data['total_amount'] = $data['amount'];
        $data['amount_paid'] = 0;
        $data['balance_due'] = $data['amount'];
        $data['status'] = HotelExpense::STATUS_PENDING;

        if ($this->receipt_file) {
            $data['receipt_path'] = Files::uploadLocalOrS3($this->receipt_file, 'hotel-expenses');
        }

        DB::transaction(function () use ($data, $statusPreference) {
            if ($this->editingId) {
                $expense = HotelExpense::findOrFail($this->editingId);
                $data['amount_paid'] = $expense->amount_paid;
                $data['balance_due'] = max($data['total_amount'] - $data['amount_paid'], 0);
                $data['status'] = $expense->status === HotelExpense::STATUS_CANCELLED
                    ? HotelExpense::STATUS_CANCELLED
                    : HotelExpense::STATUS_PENDING;
                $expense->update($data);
            } else {
                $expense = HotelExpense::create(array_merge($data, [
                    'created_by_user_id' => auth()->id(),
                ]));
            }

            // Keep quick-entry behavior: when user marks as paid, auto-create settlement payment.
            if ($statusPreference === HotelExpense::STATUS_PAID && $expense->balance_due > 0) {
                HotelExpensePayment::create([
                    'branch_id' => $expense->branch_id,
                    'restaurant_id' => $expense->restaurant_id,
                    'hotel_expense_id' => $expense->id,
                    'amount' => $expense->balance_due,
                    'payment_method' => $expense->payment_method,
                    'reference_number' => $expense->receipt_number,
                    'paid_at' => now(),
                    'notes' => 'Auto-created from expense save (marked paid).',
                    'paid_by_user_id' => auth()->id(),
                ]);
            }

            $expense->recalculatePaymentTotals();
        });

        $this->alert('success', $this->editingId ? 'Expense updated successfully.' : 'Expense added successfully.');

        $this->showModal = false;
        $this->resetForm();
    }

    public function openPaymentModal($id)
    {
        abort_unless(user_can('edit_hotel_expense'), 403);
        $expense = HotelExpense::with('payments.paidBy')->findOrFail($id);
        if ($expense->status === HotelExpense::STATUS_CANCELLED) {
            $this->alert('error', 'Cannot record payments for cancelled expenses.');
            return;
        }

        $this->paymentExpenseId = $id;
        $this->payment_amount = $expense->balance_due > 0 ? number_format((float) $expense->balance_due, 2, '.', '') : '';
        $this->payment_method_entry = $expense->payment_method ?: 'cash';
        $this->payment_reference_number = '';
        $this->payment_paid_at = Carbon::now()->format('Y-m-d\TH:i');
        $this->payment_notes = '';
        $this->payment_receipt_file = null;
        $this->resetErrorBag();
        $this->showPaymentModal = true;
    }

    public function savePayment()
    {
        abort_unless(user_can('edit_hotel_expense'), 403);
        $data = $this->validate($this->paymentRules());

        $expense = HotelExpense::findOrFail($this->paymentExpenseId);
        if ($expense->status === HotelExpense::STATUS_CANCELLED) {
            $this->alert('error', 'Cannot record payments for cancelled expenses.');
            return;
        }

        $amount = (float) $data['payment_amount'];
        if ($amount > (float) $expense->balance_due) {
            $this->addError('payment_amount', 'Payment amount cannot exceed balance due.');
            return;
        }

        DB::transaction(function () use ($data, $expense) {
            $paymentPayload = [
                'branch_id' => $expense->branch_id,
                'restaurant_id' => $expense->restaurant_id,
                'hotel_expense_id' => $expense->id,
                'amount' => $data['payment_amount'],
                'payment_method' => $data['payment_method_entry'],
                'reference_number' => $data['payment_reference_number'],
                'paid_at' => $data['payment_paid_at'],
                'notes' => $data['payment_notes'],
                'paid_by_user_id' => auth()->id(),
            ];

            if ($this->payment_receipt_file) {
                $paymentPayload['receipt_path'] = Files::uploadLocalOrS3($this->payment_receipt_file, 'hotel-expense-payments');
            }

            HotelExpensePayment::create($paymentPayload);
            $expense->recalculatePaymentTotals();
        });

        $this->alert('success', 'Expense payment recorded.');
        $this->openPaymentModal($expense->id);
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
        $this->due_date       = Carbon::today()->format('Y-m-d');
        $this->payment_method = 'cash';
        $this->vendor         = '';
        $this->receipt_number = '';
        $this->status         = 'pending';
        $this->receipt_file   = null;
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
            ->when($this->statusFilter !== 'all', function ($q) {
                if ($this->statusFilter === 'outstanding') {
                    $q->where('status', '!=', HotelExpense::STATUS_CANCELLED)
                        ->where('balance_due', '>', 0);
                    return;
                }
                if ($this->statusFilter === HotelExpense::STATUS_PARTIAL) {
                    $q->where('status', '!=', HotelExpense::STATUS_CANCELLED)
                        ->where('amount_paid', '>', 0)
                        ->where('balance_due', '>', 0);
                    return;
                }
                $q->where('status', $this->statusFilter);
            })
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
            'selectedExpense' => $this->paymentExpenseId
                ? HotelExpense::with(['payments' => fn($query) => $query->with('paidBy')->latest('paid_at')])->find($this->paymentExpenseId)
                : null,
        ])->layout('layouts.app');
    }
}
