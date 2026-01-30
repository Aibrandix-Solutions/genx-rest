<?php

namespace Modules\Hrm\Entities;

use App\Models\Branch;
use App\Models\User;
use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
