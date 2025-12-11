<?php

namespace App\Services;

use App\Models\RewardSetting;
use App\Models\RewardBalance;
use App\Models\RewardTransaction;
use App\Models\Order;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardPointsService
{
    /**
     * Calculate points earned from an order
     */
    public function calculatePointsEarned(Order $order): int
    {
        $settings = RewardSetting::getForRestaurant($order->restaurant_id);

        if (!$settings->enable_reward_point) {
            return 0;
        }

        // Check minimum order total
        if ($order->total < $settings->minimum_order_total_to_earn) {
            return 0;
        }

        // Calculate points: order total / amount_spend_for_unit_point
        $points = floor($order->total / $settings->amount_spend_for_unit_point);

        // Apply maximum points per order limit
        if ($settings->maximum_points_per_order && $points > $settings->maximum_points_per_order) {
            $points = $settings->maximum_points_per_order;
        }

        return max(0, (int)$points);
    }

    /**
     * Award points to customer for a completed order
     */
    public function awardPoints(Order $order): ?RewardTransaction
    {
        if (!$order->customer_id) {
            return null;
        }

        $settings = RewardSetting::getForRestaurant($order->restaurant_id);

        if (!$settings->enable_reward_point) {
            return null;
        }

        // Check if points already awarded for this order
        $existing = RewardTransaction::where('order_id', $order->id)
            ->where('type', 'earn')
            ->first();

        if ($existing) {
            return $existing;
        }

        $points = $this->calculatePointsEarned($order);

        if ($points <= 0) {
            return null;
        }

        try {
            DB::transaction(function () use ($order, $points, $settings) {
                // Get or create balance
                $balance = RewardBalance::getForCustomer($order->customer_id, $order->restaurant_id);

                // Calculate expiry date
                $expiresAt = null;
                if ($settings->reward_point_expiry_period > 0) {
                    $unit = $settings->reward_point_expiry_period_unit ?? 'month';
                    if ($unit === 'year') {
                        $expiresAt = Carbon::now()->addYears($settings->reward_point_expiry_period);
                    } else {
                        $expiresAt = Carbon::now()->addMonths($settings->reward_point_expiry_period);
                    }
                }

                // Create transaction
                $transaction = RewardTransaction::create([
                    'customer_id' => $order->customer_id,
                    'restaurant_id' => $order->restaurant_id,
                    'order_id' => $order->id,
                    'type' => 'earn',
                    'points' => $points,
                    'amount_value' => $order->total,
                    'description' => "Earned {$points} {$settings->reward_point_display_name} points from order #{$order->order_number}",
                    'expires_at' => $expiresAt,
                ]);

                // Update balance
                $balance->addPoints($points);

                return $transaction;
            });

            return RewardTransaction::where('order_id', $order->id)
                ->where('type', 'earn')
                ->first();
        } catch (\Exception $e) {
            Log::error('Error awarding reward points: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate maximum redeemable points for an order
     */
    public function calculateMaxRedeemablePoints(Order $order, Customer $customer): int
    {
        $settings = RewardSetting::getForRestaurant($order->restaurant_id);

        if (!$settings->enable_reward_point) {
            return 0;
        }

        // Check minimum order total to redeem
        if ($order->total < $settings->minimum_order_total_to_redeem) {
            return 0;
        }

        // Get available balance
        $balance = $customer->getRewardBalance($order->restaurant_id);
        if (!$balance) {
            return 0;
        }

        $availablePoints = $balance->available_points;

        // Apply maximum redeem limit
        if ($settings->maximum_redeem_point_per_order) {
            $availablePoints = min($availablePoints, $settings->maximum_redeem_point_per_order);
        }

        // Calculate max based on order total (can't redeem more than order value)
        $maxDiscountFromPoints = $availablePoints * $settings->redeem_amount_per_unit_point;
        $maxPointsByOrderTotal = floor($order->total / $settings->redeem_amount_per_unit_point);

        return min($availablePoints, $maxPointsByOrderTotal);
    }

    /**
     * Calculate discount amount from points
     */
    public function calculateDiscountFromPoints(int $points, $restaurantId): float
    {
        $settings = RewardSetting::getForRestaurant($restaurantId);
        return $points * $settings->redeem_amount_per_unit_point;
    }

    /**
     * Redeem points for an order
     */
    public function redeemPoints(Order $order, Customer $customer, int $points): ?RewardTransaction
    {
        $settings = RewardSetting::getForRestaurant($order->restaurant_id);

        if (!$settings->enable_reward_point) {
            return null;
        }

        // Validate minimum redeem points
        if ($settings->minimum_redeem_point && $points < $settings->minimum_redeem_point) {
            throw new \Exception("Minimum redeem points is {$settings->minimum_redeem_point}");
        }

        // Validate maximum
        $maxRedeemable = $this->calculateMaxRedeemablePoints($order, $customer);
        if ($points > $maxRedeemable) {
            throw new \Exception("Maximum redeemable points is {$maxRedeemable}");
        }

        // Get balance
        $balance = RewardBalance::getForCustomer($customer->id, $order->restaurant_id);
        if ($balance->available_points < $points) {
            throw new \Exception("Insufficient points. Available: {$balance->available_points}");
        }

        // Calculate discount
        $discountAmount = $this->calculateDiscountFromPoints($points, $order->restaurant_id);

        try {
            DB::transaction(function () use ($order, $customer, $points, $discountAmount, $settings, $balance) {
                // Create redemption transaction
                $transaction = RewardTransaction::create([
                    'customer_id' => $customer->id,
                    'restaurant_id' => $order->restaurant_id,
                    'order_id' => $order->id,
                    'type' => 'redeem',
                    'points' => -$points, // Negative for redemption
                    'amount_value' => $discountAmount,
                    'description' => "Redeemed {$points} {$settings->reward_point_display_name} points for discount of " . currency_format($discountAmount, restaurant()->currency_id),
                ]);

                // Deduct from balance
                $balance->deductPoints($points);

                return $transaction;
            });

            return RewardTransaction::where('order_id', $order->id)
                ->where('type', 'redeem')
                ->latest()
                ->first();
        } catch (\Exception $e) {
            Log::error('Error redeeming reward points: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reverse points from a cancelled order
     */
    public function reverseOrderPoints(Order $order): void
    {
        try {
            DB::transaction(function () use ($order) {
                // Reverse earned points
                $earnTransaction = RewardTransaction::where('order_id', $order->id)
                    ->where('type', 'earn')
                    ->first();

                if ($earnTransaction) {
                    $balance = RewardBalance::getForCustomer($order->customer_id, $order->restaurant_id);
                    if ($balance) {
                        $balance->deductPoints($earnTransaction->points);
                    }
                    $earnTransaction->delete();
                }

                // Reverse redeemed points
                $redeemTransactions = RewardTransaction::where('order_id', $order->id)
                    ->where('type', 'redeem')
                    ->get();

                foreach ($redeemTransactions as $transaction) {
                    $balance = RewardBalance::getForCustomer($order->customer_id, $order->restaurant_id);
                    if ($balance) {
                        $balance->addPoints(abs($transaction->points));
                    }
                    $transaction->delete();
                }
            });
        } catch (\Exception $e) {
            Log::error('Error reversing reward points: ' . $e->getMessage());
        }
    }

    /**
     * Adjust points (admin manual adjustment)
     */
    public function adjustPoints(Customer $customer, int $points, string $description, $restaurantId = null): RewardTransaction
    {
        $restaurantId = $restaurantId ?? restaurant()->id;
        $balance = RewardBalance::getForCustomer($customer->id, $restaurantId);
        $settings = RewardSetting::getForRestaurant($restaurantId);

        return DB::transaction(function () use ($customer, $points, $description, $restaurantId, $balance, $settings) {
            $transaction = RewardTransaction::create([
                'customer_id' => $customer->id,
                'restaurant_id' => $restaurantId,
                'type' => 'adjust',
                'points' => $points,
                'description' => $description ?: "Manual adjustment: " . ($points > 0 ? '+' : '') . $points . " {$settings->reward_point_display_name} points",
                'created_by' => auth()->id(),
            ]);

            if ($points > 0) {
                $balance->addPoints($points);
            } else {
                $balance->deductPoints(abs($points));
            }

            return $transaction;
        });
    }
}

