<?php

namespace Modules\Hrm\Entities;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAdjustment extends Model
{
    use HasFactory;
    use HasRestaurant;

    protected $table = 'hrm_payroll_adjustments';

    protected $guarded = [];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'additional_pay' => 'decimal:2',
        'advance' => 'decimal:2',
        'epf' => 'decimal:2',
        'etf' => 'decimal:2',
        'time_deduction' => 'decimal:2',
        'credit_purchase' => 'decimal:2',
        'other_deduction' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
