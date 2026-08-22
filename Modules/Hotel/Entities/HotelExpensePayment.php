<?php

namespace Modules\Hotel\Entities;

use App\Models\User;
use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelExpensePayment extends Model
{
    use HasFactory, HasBranch;

    protected $table = 'hotel_expense_payments';

    protected $fillable = [
        'branch_id',
        'restaurant_id',
        'hotel_expense_id',
        'amount',
        'payment_method',
        'reference_number',
        'paid_at',
        'notes',
        'receipt_path',
        'paid_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(HotelExpense::class, 'hotel_expense_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }
}
