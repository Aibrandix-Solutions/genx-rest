<?php

namespace Modules\Hotel\Entities;

use App\Models\User;
use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelExpense extends Model
{
    use HasFactory, HasRestaurant, SoftDeletes;

    protected $table = 'hotel_expenses';

    protected $fillable = [
        'restaurant_id',
        'title',
        'department',
        'description',
        'amount',
        'expense_date',
        'payment_method',
        'vendor',
        'receipt_number',
        'receipt_path',
        'status',
        'created_by_user_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount'       => 'decimal:2',
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
    const STATUS_CANCELLED = 'cancelled';

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
