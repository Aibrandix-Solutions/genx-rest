<?php

namespace Modules\Hotel\Livewire\Expense;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\HotelExpense;
use Illuminate\Support\Facades\DB;

class HotelExpenseList extends Component
{
    use WithPagination, WithFileUploads, LivewireAlert;

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
    public $department      = 'other';
    public $description     = '';
    public $amount          = '';
    public $expense_date    = '';
    public $payment_method  = 'cash';
    public $vendor          = '';
    public $receipt_number  = '';
    public $status          = 'paid';

    // --- Delete Confirm ---
    public $pendingDeleteId = null;

    protected function rules(): array
    {
        return [
            'title'          => 'required|string|max:255',
            'department'     => 'required|string',
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
        $this->showModal = true;
    }

    public function openEdit($id)
    {
        abort_unless(user_can('edit_hotel_expense'), 403);
        $expense = HotelExpense::findOrFail($id);

        $this->editingId      = $expense->id;
        $this->title          = $expense->title;
        $this->department     = $expense->department;
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
        $this->department     = 'other';
        $this->description    = '';
        $this->amount         = '';
        $this->expense_date   = Carbon::today()->format('Y-m-d');
        $this->payment_method = 'cash';
        $this->vendor         = '';
        $this->receipt_number = '';
        $this->status         = 'paid';
        $this->resetErrorBag();
    }

    public function render()
    {
        $expenses = HotelExpense::when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('title', 'like', '%' . $this->search . '%')
                       ->orWhere('vendor', 'like', '%' . $this->search . '%')
                       ->orWhere('receipt_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->departmentFilter !== 'all', fn($q) => $q->where('department', $this->departmentFilter))
            ->when($this->dateFrom, fn($q) => $q->where('expense_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->where('expense_date', '<=', $this->dateTo))
            ->latest('expense_date')
            ->paginate(15);

        return view('hotel::livewire.expense.hotel-expense-list', [
            'expenses'    => $expenses,
            'departments' => HotelExpense::DEPARTMENTS,
            'methods'     => HotelExpense::PAYMENT_METHODS,
        ])->layout('layouts.app');
    }
}
