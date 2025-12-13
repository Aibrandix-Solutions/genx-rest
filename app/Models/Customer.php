<?php

namespace App\Models;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use App\Models\BaseModel;

class Customer extends BaseModel
{
    use HasFactory;
    use Notifiable;
    use HasRestaurant;
    use Notifiable;

    protected $guarded = ['id'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->orderBy('id', 'desc');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->orderBy('id', 'desc');
    }

    public function payments(): HasMany
    {
        return $this->hasManyThrough(Payment::class, Order::class);
    }

    public function rewardBalance(): HasMany
    {
        return $this->hasMany(RewardBalance::class);
    }

    public function rewardTransactions(): HasMany
    {
        return $this->hasMany(RewardTransaction::class);
    }

    /**
     * Get reward balance for current restaurant
     */
    public function getRewardBalance($restaurantId = null): ?RewardBalance
    {
        $restaurantId = $restaurantId ?? restaurant()->id;
        return $this->rewardBalance()->where('restaurant_id', $restaurantId)->first();
    }

    /**
     * Calculate outstanding balance for this customer
     * Sum of (order.total - order.amount_paid) for all payment_due orders
     */
    public function getOutstandingBalanceAttribute(): float
    {
        return $this->orders()
            ->where('status', 'payment_due')
            ->get()
            ->sum(function ($order) {
                return max(0, (float)$order->total - (float)$order->amount_paid);
            });
    }

    /**
     * Calculate total sales for this customer
     */
    public function getTotalSalesAttribute(): float
    {
        return (float)$this->orders()
            ->whereIn('status', ['paid', 'payment_due'])
            ->sum('total');
    }

    /**
     * Get last order date
     */
    public function getLastOrderDateAttribute()
    {
        $lastOrder = $this->orders()->latest('date_time')->first();
        return $lastOrder ? $lastOrder->date_time : null;
    }

    public function routeNotificationForVonage($notification)
    {
        if (!is_null($this->phone) && !is_null($this->phone_code)) {
            return '+' . $this->phone_code . $this->phone;
        }

        return null;
    }

    public function routeNotificationForMsg91($notification)
    {
        if (!is_null($this->phone) && !is_null($this->phone_code)) {
            return $this->phone_code . $this->phone;
        }

        return null;
    }

}
