<?php

namespace Modules\Hrm\Entities;

use App\Models\Branch;
use App\Models\User;
use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Employee extends Model
{
    use HasFactory;
    use HasRestaurant;

    protected $table = 'hrm_employees';

    protected $guarded = [];

    protected $casts = [
        'hire_date' => 'date',
        'basic_salary_per_day' => 'decimal:2',
        'basic_salary_per_month' => 'decimal:2',
    ];

    public static function generateStaffCode(int $restaurantId): string
    {
        $start = (int) DB::table('hrm_employees')
            ->where('restaurant_id', $restaurantId)
            ->max('id');
        $start = max($start, 0) + 1;

        for ($i = 0; $i < 200; $i++) {
            $candidate = 'EMP' . str_pad((string) ($start + $i), 3, '0', STR_PAD_LEFT);

            $exists = DB::table('hrm_employees')
                ->where('restaurant_id', $restaurantId)
                ->where('staff_code', $candidate)
                ->exists();

            if (!$exists) {
                return $candidate;
            }
        }

        return 'EMP' . now()->format('ymdHis') . strtoupper(Str::random(2));
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Additional branches where this employee also works.
     * Home branch (branch_id) is NOT included here.
     */
    public function extraBranches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'hrm_employee_branch_access', 'employee_id', 'branch_id');
    }

    /**
     * Scope: employees whose home branch OR any extra branch matches $branchId.
     * Pass 0 to get company-level employees (branch_id IS NULL).
     */
    public function scopeAvailableAtBranch(Builder $query, int $branchId): Builder
    {
        if ($branchId === 0) {
            return $query->whereNull('branch_id');
        }

        return $query->where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
              ->orWhereHas('extraBranches', fn ($b) => $b->where('branches.id', $branchId));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function creditPurchases()
    {
        return $this->hasMany(CreditPurchase::class, 'employee_id');
    }

    /**
     * Get total credit purchases balance (unpaid)
     */
    public function getTotalCreditBalanceAttribute()
    {
        return $this->creditPurchases()
            ->where('status', '!=', 'paid')
            ->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount'));
    }

    /**
     * Get pending auto-deductions for this employee
     */
    public function getPendingAutoDeductionsAttribute()
    {
        return $this->creditPurchases()
            ->where('auto_deduct_from_salary', true)
            ->where('status', '!=', 'paid')
            ->sum('auto_deduct_amount');
    }
}
