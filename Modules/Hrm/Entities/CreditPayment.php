<?php

namespace Modules\Hrm\Entities;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditPayment extends Model
{
    use HasFactory;
    use HasRestaurant;

    protected $table = 'hrm_credit_payments';

    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function creditPurchase()
    {
        return $this->belongsTo(CreditPurchase::class, 'credit_purchase_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'recorded_by');
    }
}
