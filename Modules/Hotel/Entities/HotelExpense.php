<?php

namespace Modules\Hotel\Entities;

use App\Models\User;
use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelExpense extends Model
{
    use HasFactory, HasBranch, SoftDeletes;

    protected $table = 'hotel_expenses';

    protected $fillable = [
        'branch_id',
        'restaurant_id',
        'title',
        'department_id',
        'description',
        'amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'expense_date',
        'due_date',
        'payment_method',
        'vendor',
        'receipt_number',
        'receipt_path',
        'status',
        'created_by_user_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'due_date'     => 'date',
        'amount'       => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid'  => 'decimal:2',
        'balance_due'  => 'decimal:2',
    ];

    const DEPARTMENTS = [
        'housekeeping'  => 'Housekeeping',
        'front_desk'    => 'Front Desk',
        'maintenance'   => 'Maintenance',
        'laundry'       => 'Laundry',
        'utilities'     => 'Utilities',
        'security'      => 'Security',
        'marketing'     => 'Marketing',
        'administration'=> 'Administration',
        'f_and_b'       => 'F&B / Restaurant',
        'other'         => 'Other',
    ];

    const PAYMENT_METHODS = [
        'cash'          => 'Cash',
        'card'          => 'Card',
        'bank_transfer' => 'Bank Transfer',
        'upi'           => 'UPI',
        'other'         => 'Other',
    ];

    const STATUS_PAID      = 'paid';
    const STATUS_PENDING   = 'pending';
    const STATUS_PARTIAL   = 'partial';
    const STATUS_CANCELLED = 'cancelled';

    /** UI labels — DB values stay paid|pending|partial|cancelled. */
    const STATUS_LABELS = [
        self::STATUS_PAID      => 'Paid',
        self::STATUS_PENDING   => 'Unpaid',
        self::STATUS_PARTIAL   => 'Partially paid',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst((string) $status);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function departmentRelation(): BelongsTo
    {
        return $this->belongsTo(HotelExpenseDepartment::class, 'department_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(HotelExpensePayment::class, 'hotel_expense_id');
    }

    public function recalculatePaymentTotals(): void
    {
        $totalAmount = (float) ($this->total_amount ?? $this->amount ?? 0);
        $amountPaid = (float) $this->payments()->sum('amount');
        $balanceDue = max($totalAmount - $amountPaid, 0);

        $status = $this->status;
        if ($status !== self::STATUS_CANCELLED) {
            if ($balanceDue <= 0 && $totalAmount > 0) {
                $status = self::STATUS_PAID;
            } elseif ($amountPaid > 0 && $balanceDue > 0) {
                $status = self::STATUS_PARTIAL;
            } else {
                $status = self::STATUS_PENDING;
            }
        }

        $this->forceFill([
            'amount' => $totalAmount,
            'total_amount' => $totalAmount,
            'amount_paid' => $amountPaid,
            'balance_due' => $balanceDue,
            'status' => $status,
        ])->save();
    }

    public function getDepartmentAttribute($value)
    {
        return $this->departmentRelation ? $this->departmentRelation->name : $value;
    }
}
