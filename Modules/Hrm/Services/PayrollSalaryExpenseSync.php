<?php

namespace Modules\Hrm\Services;

use App\Models\ExpenseCategory;
use App\Models\Expenses;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Hotel\Entities\HotelExpense;
use Modules\Hotel\Entities\HotelExpenseDepartment;
use Modules\Hrm\Entities\Employee;
use Modules\Hrm\Entities\PayrollAdjustment;
use Modules\Hrm\Support\Workplace;

class PayrollSalaryExpenseSync
{
    /**
     * Create/update/remove the linked salary expense when payroll payment date changes.
     */
    public function sync(PayrollAdjustment $adjustment, float $payableAmount): void
    {
        $employee = Employee::query()->find($adjustment->employee_id);
        if (! $employee) {
            return;
        }

        $hasPayment = filled($adjustment->payment_date) && $payableAmount > 0;

        if (! $hasPayment) {
            $this->clearLinkedExpenses($adjustment);

            return;
        }

        // Hotel staff → hotel expenses only when Hotel module is enabled; otherwise restaurant ledger.
        if (Workplace::isHotel($employee->workplace) && Workplace::hotelAvailable()) {
            $this->clearRestaurantExpense($adjustment);
            $this->syncHotelExpense($adjustment, $employee, $payableAmount);

            return;
        }

        $this->clearHotelExpense($adjustment);
        $this->syncRestaurantExpense($adjustment, $employee, $payableAmount);
    }

    protected function syncRestaurantExpense(PayrollAdjustment $adjustment, Employee $employee, float $amount): void
    {
        $branchId = $adjustment->branch_id
            ?: $employee->branch_id
            ?: branch()?->id
            ?: DB::table('branches')->where('restaurant_id', $employee->restaurant_id)->value('id');

        if (! $branchId) {
            return;
        }

        $categoryId = $this->resolveSalariesCategoryId((int) $branchId);
        if (! $categoryId) {
            return;
        }

        $paymentDate = Carbon::parse($adjustment->payment_date)->toDateString();
        $title = sprintf(
            'Salary - %s (%s %d)',
            $employee->name,
            Carbon::createFromDate((int) $adjustment->year, (int) $adjustment->month, 1)->format('M'),
            (int) $adjustment->year
        );

        $payload = [
            'branch_id' => (int) $branchId,
            'expense_category_id' => $categoryId,
            'expense_title' => $title,
            'description' => 'Auto-posted from HRM payroll (Restaurant workplace)',
            'amount' => round($amount, 2),
            'expense_date' => $paymentDate,
            'payment_status' => 'paid',
            'payment_date' => $paymentDate,
            'payment_method' => 'cash',
        ];

        if ($adjustment->restaurant_expense_id) {
            $expense = Expenses::withoutGlobalScopes()->find($adjustment->restaurant_expense_id);
            if ($expense) {
                $expense->update($payload);

                return;
            }
        }

        $expense = Expenses::withoutGlobalScopes()->create($payload);
        $adjustment->restaurant_expense_id = $expense->id;
        $adjustment->saveQuietly();
    }

    protected function syncHotelExpense(PayrollAdjustment $adjustment, Employee $employee, float $amount): void
    {
        $branchId = $adjustment->branch_id
            ?: $employee->branch_id
            ?: branch()?->id
            ?: DB::table('branches')->where('restaurant_id', $employee->restaurant_id)->value('id');

        if (! $branchId) {
            return;
        }

        $departmentId = $this->resolveHotelSalariesDepartmentId(
            (int) $employee->restaurant_id,
            (int) $branchId
        );

        $paymentDate = Carbon::parse($adjustment->payment_date)->toDateString();
        $title = sprintf(
            'Salary - %s (%s %d)',
            $employee->name,
            Carbon::createFromDate((int) $adjustment->year, (int) $adjustment->month, 1)->format('M'),
            (int) $adjustment->year
        );

        $payload = [
            'branch_id' => (int) $branchId,
            'restaurant_id' => (int) $employee->restaurant_id,
            'title' => $title,
            'department_id' => $departmentId,
            'description' => 'Auto-posted from HRM payroll (Hotel workplace)',
            'amount' => round($amount, 2),
            'expense_date' => $paymentDate,
            'payment_method' => 'cash',
            'vendor' => $employee->name,
            'status' => HotelExpense::STATUS_PAID,
            'created_by_user_id' => auth()->id(),
        ];

        if ($adjustment->hotel_expense_id) {
            $expense = HotelExpense::withoutGlobalScopes()->find($adjustment->hotel_expense_id);
            if ($expense) {
                $expense->update($payload);

                return;
            }
        }

        $expense = HotelExpense::withoutGlobalScopes()->create($payload);
        $adjustment->hotel_expense_id = $expense->id;
        $adjustment->saveQuietly();
    }

    protected function clearLinkedExpenses(PayrollAdjustment $adjustment): void
    {
        $this->clearRestaurantExpense($adjustment);
        $this->clearHotelExpense($adjustment);
    }

    protected function clearRestaurantExpense(PayrollAdjustment $adjustment): void
    {
        if ($adjustment->restaurant_expense_id) {
            Expenses::withoutGlobalScopes()->where('id', $adjustment->restaurant_expense_id)->delete();
            $adjustment->restaurant_expense_id = null;
            $adjustment->saveQuietly();
        }
    }

    protected function clearHotelExpense(PayrollAdjustment $adjustment): void
    {
        if ($adjustment->hotel_expense_id) {
            HotelExpense::withoutGlobalScopes()->where('id', $adjustment->hotel_expense_id)->delete();
            $adjustment->hotel_expense_id = null;
            $adjustment->saveQuietly();
        }
    }

    protected function resolveSalariesCategoryId(int $branchId): ?int
    {
        $category = ExpenseCategory::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->where('name', 'Salaries')
            ->first();

        if ($category) {
            return (int) $category->id;
        }

        $category = new ExpenseCategory();
        $category->forceFill([
            'branch_id' => $branchId,
            'name' => 'Salaries',
            'is_active' => true,
        ])->save();

        return (int) $category->id;
    }

    protected function resolveHotelSalariesDepartmentId(int $restaurantId, int $branchId): ?int
    {
        $dept = HotelExpenseDepartment::withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->where(function ($q) {
                $q->where('slug', 'salaries')
                    ->orWhere('name', 'Salaries')
                    ->orWhere('name', 'Payroll');
            })
            ->first();

        if ($dept) {
            return (int) $dept->id;
        }

        $dept = new HotelExpenseDepartment();
        $dept->forceFill([
            'restaurant_id' => $restaurantId,
            'branch_id' => $branchId,
            'name' => 'Salaries',
            'slug' => 'salaries',
            'description' => 'Staff salary expenses from HRM payroll',
            'is_system' => true,
        ])->save();

        return (int) $dept->id;
    }
}
