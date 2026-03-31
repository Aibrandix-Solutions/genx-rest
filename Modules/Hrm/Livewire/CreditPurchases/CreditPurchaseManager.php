<?php
namespace Modules\Hrm\Livewire\CreditPurchases;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hrm\Entities\CreditPayment;
use Modules\Hrm\Entities\CreditPurchase;
use Modules\Hrm\Entities\Employee;

class CreditPurchaseManager extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $branch_id;
    public $employee_id;
    public $purchase_date;
    public $description = '';
    public $amount;
    public $category = '';
    public $auto_deduct_from_salary = false;
    public $auto_deduct_amount;
    public $status = 'all'; // all, pending, partial, paid

    public $showForm = false;
    public $editingId = null;
    public $searchTerm = '';
    public $perPage = 20;

    // Payment recording
    public $showPaymentForm = false;
    public $paymentCreditPurchaseId = null;
    public $paymentAmount;
    public $paymentMethod = 'cash';
    public $paymentReference = '';
    public $paymentNotes = '';

    public function mount()
    {
        $this->authorize('Manage Payroll');
        $this->branch_id = auth()->user()?->branch_id;
        $this->purchase_date = now()->toDateString();
    }

    public function openForm($creditPurchaseId = null)
    {
        $this->authorize('Manage Payroll');

        if ($creditPurchaseId) {
            $purchase = CreditPurchase::query()
                ->where('restaurant_id', restaurant()->id)
                ->findOrFail((int) $creditPurchaseId);
            $this->editingId = $purchase->id;
            $this->employee_id = $purchase->employee_id;
            $this->purchase_date = $purchase->purchase_date->toDateString();
            $this->description = $purchase->description;
            $this->amount = $purchase->amount;
            $this->category = $purchase->category;
            $this->auto_deduct_from_salary = $purchase->auto_deduct_from_salary;
            $this->auto_deduct_amount = $purchase->auto_deduct_amount;
        } else {
            $this->resetForm();
        }

        $this->showForm = true;
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingId = null;
        $this->employee_id = null;
        $this->purchase_date = now()->toDateString();
        $this->description = '';
        $this->amount = null;
        $this->category = '';
        $this->auto_deduct_from_salary = false;
        $this->auto_deduct_amount = null;
    }

    public function save()
    {
        $this->authorize('Manage Payroll');

        $this->validate([
            'employee_id' => ['required', Rule::exists('hrm_employees', 'id')->where(fn ($q) => $q->where('restaurant_id', restaurant()->id))],
            'purchase_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category' => ['nullable', 'string', 'max:100'],
            'auto_deduct_from_salary' => ['boolean'],
            'auto_deduct_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($this->editingId) {
            $creditPurchase = CreditPurchase::query()
                ->where('restaurant_id', restaurant()->id)
                ->findOrFail((int) $this->editingId);
            $creditPurchase->update([
                'employee_id' => $this->employee_id,
                'purchase_date' => $this->purchase_date,
                'description' => $this->description,
                'amount' => $this->amount,
                'category' => $this->category,
                'auto_deduct_from_salary' => $this->auto_deduct_from_salary,
                'auto_deduct_amount' => $this->auto_deduct_from_salary ? $this->auto_deduct_amount : null,
            ]);

            $this->dispatch('alert', type: 'success', message: 'Credit purchase updated successfully');
        } else {
            CreditPurchase::create([
                'restaurant_id' => restaurant()->id,
                'employee_id' => $this->employee_id,
                'purchase_date' => $this->purchase_date,
                'description' => $this->description,
                'amount' => $this->amount,
                'category' => $this->category,
                'auto_deduct_from_salary' => $this->auto_deduct_from_salary,
                'auto_deduct_amount' => $this->auto_deduct_from_salary ? $this->auto_deduct_amount : null,
                'created_by' => auth()->id(),
            ]);

            $this->dispatch('alert', type: 'success', message: 'Credit purchase recorded successfully');
        }

        $this->closeForm();
        $this->resetPage();
    }

    public function delete($creditPurchaseId)
    {
        $this->authorize('Manage Payroll');

        CreditPurchase::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail((int) $creditPurchaseId)
            ->delete();
        $this->dispatch('alert', type: 'success', message: 'Credit purchase deleted');
        $this->resetPage();
    }

    public function openPaymentForm($creditPurchaseId)
    {
        $this->authorize('Manage Payroll');

        $creditPurchase = CreditPurchase::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail((int) $creditPurchaseId);
        $this->paymentCreditPurchaseId = $creditPurchase->id;
        $this->paymentAmount = $creditPurchase->remaining_balance;
        $this->paymentMethod = 'cash';
        $this->paymentReference = '';
        $this->paymentNotes = '';
        $this->showPaymentForm = true;
    }

    public function closePaymentForm()
    {
        $this->showPaymentForm = false;
        $this->paymentCreditPurchaseId = null;
        $this->paymentAmount = null;
        $this->paymentMethod = 'cash';
        $this->paymentReference = '';
        $this->paymentNotes = '';
    }

    public function recordPayment()
    {
        $this->authorize('Manage Payroll');

        $this->validate([
            'paymentAmount' => ['required', 'numeric', 'min:0.01'],
            'paymentMethod' => ['required', 'string'],
            'paymentReference' => ['nullable', 'string', 'max:100'],
            'paymentNotes' => ['nullable', 'string'],
        ]);

        $creditPurchase = CreditPurchase::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail((int) $this->paymentCreditPurchaseId);
        $creditPurchase->recordPayment(
            $this->paymentAmount,
            $this->paymentMethod,
            $this->paymentReference,
            $this->paymentNotes,
            auth()->id()
        );

        $this->dispatch('alert', type: 'success', message: 'Payment recorded successfully');
        $this->closePaymentForm();
        $this->resetPage();
    }

    public function approveCreditPurchase($creditPurchaseId)
    {
        $this->authorize('Manage Payroll');

        $creditPurchase = CreditPurchase::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail((int) $creditPurchaseId);
        $creditPurchase->approve(auth()->id());

        $this->dispatch('alert', type: 'success', message: 'Credit purchase approved');
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('Manage Payroll');

        $query = CreditPurchase::query()
            ->where('restaurant_id', restaurant()->id)
            ->with(['employee', 'payments', 'createdBy', 'approvedBy']);

        if ($this->branch_id) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $this->branch_id));
        }

        if ($this->employee_id) {
            $query->where('employee_id', $this->employee_id);
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('description', 'like', "%{$this->searchTerm}%")
                    ->orWhereHas('employee', fn($e) => $e->where('name', 'like', "%{$this->searchTerm}%"));
            });
        }

        $creditPurchases = $query->latest('purchase_date')->paginate($this->perPage);
        $employees = Employee::where('restaurant_id', restaurant()->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $employeeIds = $employees->pluck('id')->all();
        $posDueByEmployee = [];

        if (!empty($employeeIds)) {
            $posDueByEmployee = DB::table('customers as c')
                ->leftJoin('orders as o', function ($join) {
                    $join->on('o.customer_id', '=', 'c.id')
                        ->where('o.status', '=', 'payment_due');
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
                ->pluck('due', 'employee_id')
                ->toArray();
        }

        return view('hrm::livewire.credit-purchases.manager', [
            'creditPurchases' => $creditPurchases,
            'employees' => $employees,
            'posDueByEmployee' => $posDueByEmployee,
        ])->layout('layouts.app');
    }
}
