<?php

namespace Modules\Hotel\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantSettlementPayment extends Model
{
    protected $table = 'hotel_restaurant_settlement_payments';

    protected $fillable = [
        'restaurant_id',
        'hotel_branch_id',
        'restaurant_branch_id',
        'reservation_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'remarks',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(RestaurantSettlementAllocation::class, 'settlement_payment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
