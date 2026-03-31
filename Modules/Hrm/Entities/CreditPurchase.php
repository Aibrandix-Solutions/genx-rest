<?php

namespace Modules\Hrm\Entities;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreditPurchase extends Model
{
    use HasFactory;
    use HasRestaurant;

    protected $table = 'hrm_credit_purchases';

    protected $guarded = [];

    protected $casts = [
        'purchase_date' => 'date',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'auto_deduct_amount' => 'decimal:2',
        'is_approved' => 'boolean',
        'auto_deduct_from_salary' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function payments()
    {
        return $this->hasMany(CreditPayment::class, 'credit_purchase_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Accessor: Get remaining balance
     */
    public function getRemainingBalanceAttribute()
    {
        return max(0, $this->amount - $this->paid_amount);
    }

    /**
     * Approve the credit purchase
     */
    public function approve($userId = null)
    {
        $this->update([
            'is_approved' => true,
            'approved_by' => $userId ?? auth()->id(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Record a payment
     */
    public function recordPayment(float $amount, string $method = 'cash', ?string $reference = null, ?string $notes = null, ?int $recordedBy = null)
    {
        return DB::transaction(function () use ($amount, $method, $reference, $notes, $recordedBy) {
            $payment = $this->payments()->create([
                'restaurant_id' => $this->restaurant_id,
                'employee_id' => $this->employee_id,
                'payment_date' => now(),
                'amount' => $amount,
                'payment_method' => $method,
                'reference_number' => $reference,
                'notes' => $notes,
                'recorded_by' => $recordedBy ?? auth()->id(),
            ]);

            // Update paid amount and status
            $newPaidAmount = $this->paid_amount + $amount;
            $status = $newPaidAmount >= $this->amount ? 'paid' : 'partial';

            $this->update([
                'paid_amount' => min($newPaidAmount, $this->amount),
                'status' => $status,
            ]);

            return $payment;
        });
    }

    /**
     * Get payment percentage
     */
    public function getPaymentPercentageAttribute()
    {
        return $this->amount > 0 ? round(($this->paid_amount / $this->amount) * 100, 2) : 0;
    }
}
