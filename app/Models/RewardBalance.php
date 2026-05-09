<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewardBalance extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'points_balance' => 'integer',
        'points_held' => 'integer',
        'last_accrual_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(RewardTransaction::class, 'customer_id', 'customer_id')
            ->where('restaurant_id', $this->restaurant_id);
    }

    /**
     * Get available points (balance minus held)
     */
    public function getAvailablePointsAttribute(): int
    {
        return max(0, $this->points_balance - $this->points_held);
    }

    /**
     * Lifetime points earned from orders (earn transactions only).
     */
    public function getTotalEarnedAttribute(): int
    {
        return (int) RewardTransaction::query()
            ->where('customer_id', $this->customer_id)
            ->where('restaurant_id', $this->restaurant_id)
            ->where('type', 'earn')
            ->sum('points');
    }

    /**
     * Lifetime points redeemed (absolute sum of redeem transactions).
     */
    public function getTotalRedeemedAttribute(): int
    {
        $sum = (int) RewardTransaction::query()
            ->where('customer_id', $this->customer_id)
            ->where('restaurant_id', $this->restaurant_id)
            ->where('type', 'redeem')
            ->sum('points');

        return (int) abs($sum);
    }

    /**
     * Get or create reward balance for a customer
     */
    public static function getForCustomer($customerId, $restaurantId): self
    {
        return static::firstOrCreate(
            [
                'customer_id' => $customerId,
                'restaurant_id' => $restaurantId,
            ],
            [
                'points_balance' => 0,
                'points_held' => 0,
            ]
        );
    }

    /**
     * Add points to balance
     */
    public function addPoints(int $points): void
    {
        $this->increment('points_balance', $points);
        $this->update(['last_accrual_at' => now()]);
    }

    /**
     * Deduct points from balance
     */
    public function deductPoints(int $points): void
    {
        $this->decrement('points_balance', max(0, min($points, $this->points_balance)));
    }

    /**
     * Hold points during checkout
     */
    public function holdPoints(int $points): void
    {
        $available = $this->available_points;
        $toHold = min($points, $available);
        $this->increment('points_held', $toHold);
    }

    /**
     * Release held points
     */
    public function releasePoints(int $points): void
    {
        $this->decrement('points_held', max(0, min($points, $this->points_held)));
    }
}

