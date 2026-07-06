<?php

namespace Modules\Hotel\Entities;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantSettlementAllocation extends Model
{
    protected $table = 'hotel_restaurant_settlement_allocations';

    protected $fillable = [
        'settlement_payment_id',
        'restaurant_order_id',
        'restaurant_branch_id',
        'reservation_id',
        'applied_amount',
        'remaining_amount',
        'allocated_at',
        'allocated_by_user_id',
    ];

    protected $casts = [
        'allocated_at' => 'datetime',
        'applied_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function settlementPayment(): BelongsTo
    {
        return $this->belongsTo(RestaurantSettlementPayment::class, 'settlement_payment_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'restaurant_order_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by_user_id');
    }
}
