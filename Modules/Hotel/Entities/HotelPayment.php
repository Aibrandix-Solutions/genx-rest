<?php

namespace Modules\Hotel\Entities;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelPayment extends Model
{
    use HasFactory;

    protected $table = 'hotel_payments';

    protected $fillable = [
        'reservation_id',
        'branch_id',
        'amount',
        'payment_method',
        'payment_type',
        'reference_number',
        'notes',
        'received_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    const TYPE_ADVANCE = 'advance';
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_SETTLEMENT = 'settlement';
    const TYPE_REFUND = 'refund';

    const METHOD_CASH = 'cash';
    const METHOD_CARD = 'card';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_UPI = 'upi';
    const METHOD_OTHER = 'other';

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }
}
