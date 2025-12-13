<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardSetting extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'enable_reward_point' => 'boolean',
        'amount_spend_for_unit_point' => 'decimal:2',
        'minimum_order_total_to_earn' => 'decimal:2',
        'maximum_points_per_order' => 'integer',
        'redeem_amount_per_unit_point' => 'decimal:2',
        'minimum_order_total_to_redeem' => 'decimal:2',
        'minimum_redeem_point' => 'integer',
        'maximum_redeem_point_per_order' => 'integer',
        'reward_point_expiry_period' => 'integer',
        'reward_point_expiry_period_unit' => 'string',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get or create reward settings for a restaurant
     */
    public static function getForRestaurant($restaurantId): self
    {
        return static::firstOrCreate(
            ['restaurant_id' => $restaurantId],
            [
                'enable_reward_point' => false,
                'reward_point_display_name' => 'Reward',
                'amount_spend_for_unit_point' => 1.00,
                'minimum_order_total_to_earn' => 1.00,
                'maximum_points_per_order' => null,
                'redeem_amount_per_unit_point' => 1.00,
                'minimum_order_total_to_redeem' => 1.00,
                'minimum_redeem_point' => null,
                'maximum_redeem_point_per_order' => null,
                'reward_point_expiry_period' => 12,
                'reward_point_expiry_period_unit' => 'month',
            ]
        );
    }
}

