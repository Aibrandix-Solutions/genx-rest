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
     * Resolve the restaurant ID for reward operations on an order.
     *
     * Orders are branch-scoped ({@see Order::$branch_id}, {@see HasBranch}); earn/redeem
     * uses restaurant-scoped rows ({@see RewardBalance}, {@see RewardTransaction}).
     * Prefer the order’s own restaurant_id when present, else the branch’s restaurant_id
     * (same pattern as {@see OrderObserver} loading branch.restaurant), then session.
     */
    protected function getRestaurantId(Order $order): ?int
    {
        $restaurantId = $order->restaurant_id;

        if (is_null($restaurantId) && $order->branch_id) {
            $restaurantId = $order->branch->restaurant_id;
        }

        return $restaurantId ?? restaurant()?->id;
    }
    /**
     * Calculate points earned from an order
     */
    public function calculatePointsEarned(Order $order): int
    {
        $restaurantId = $this->getRestaurantId($order);

        // If still null, return 0 as we can't find settings
        if (is_null($restaurantId)) {
            return 0;
        }

        $settings = RewardSetting::getForRestaurant($restaurantId);

        if (!$settings->enable_reward_point) {
            return 0;
        }

        // Net paid = order total (which already has reward-point discount applied)
        $netPaid = max(0, (float) $order->total);

        // Check minimum order total against net paid amount
        if ($netPaid < $settings->minimum_order_total_to_earn) {
            return 0;
        }

        // Calculate points: net paid / amount_spend_for_unit_point
        // Calculate points: net paid / amount_spend_for_unit_point
        // Earn only on what the customer actually pays after ALL discounts
        if ($settings->amount_spend_for_unit_point <= 0) {
            return 0;
        }
        $points = floor($netPaid / $settings->amount_spend_for_unit_point);        // Apply maximum points per order limit
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

        $restaurantId = $this->getRestaurantId($order);

        $settings = RewardSetting::getForRestaurant($restaurantId);

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
            DB::transaction(function () use ($order, $points, $settings, $restaurantId) {
                // Get or create balance
                $balance = RewardBalance::getForCustomer($order->customer_id, $restaurantId);

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
                // Net paid amount (after all discounts including reward point discount, which is already in total)
                $netPaid = max(0, (float) $order->total);

                $transaction = RewardTransaction::create([
                    'customer_id' => $order->customer_id,
                    'restaurant_id' => $restaurantId,
                    'order_id' => $order->id,
                    'type' => 'earn',
                    'points' => $points,
                    'amount_value' => $netPaid,
                    'description' => "Earned {$points} {$settings->reward_point_display_name} points from order #{$order->order_number}",
                    'expires_at' => $expiresAt,
                ]);

                // Update balance
                $balance->addPoints($points);

                // Store earned points on the order for display
                Order::where('id', $order->id)->update(['reward_points_earned' => $points]);

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
     * Calculate maximum redeemable points for POS (before order is persisted).
     * Used by the reward balance API endpoint.
     */
    public function calculateMaxRedeemablePoints(
        $customerOrOrder,
        $customerOrRestaurantId = null,
        ?float $orderSubtotal = null
    ): int {
        // Overloaded: supports (Customer, restaurantId, subtotal) OR (Order, Customer)
        if ($customerOrOrder instanceof Order) {
            // Original signature: (Order, Customer)
            $order = $customerOrOrder;
            $customer = $customerOrRestaurantId;
            $restaurantId = $this->getRestaurantId($order);
            $settings = RewardSetting::getForRestaurant($restaurantId);

            if (!$settings->enable_reward_point) {
                return 0;
            }

            // Cap redemption against order value before reward discount is applied
            // (stored total is net of reward_point_discount after billing).
            $grossForRedeem = (float) $order->total + (float) ($order->reward_point_discount ?? 0);

            if ($grossForRedeem < $settings->minimum_order_total_to_redeem) {
                return 0;
            }

            $balance = $customer->getRewardBalance($restaurantId);
            if (!$balance) {
                return 0;
            }

            $availablePoints = $balance->available_points;

            if ($settings->maximum_redeem_point_per_order) {
                $availablePoints = min($availablePoints, $settings->maximum_redeem_point_per_order);
            }

            if ($settings->redeem_amount_per_unit_point <= 0) {
                return (int) $availablePoints;
            }

            $maxPointsByOrderTotal = (int) floor($grossForRedeem / $settings->redeem_amount_per_unit_point);

            return (int) min($availablePoints, $maxPointsByOrderTotal);
        }

        // POS signature: (Customer, restaurantId, subtotal)
        $customer = $customerOrOrder;
        $restaurantId = (int) $customerOrRestaurantId;
        $subtotal = $orderSubtotal ?? 0;

        $settings = RewardSetting::getForRestaurant($restaurantId);

        if (!$settings->enable_reward_point) {
            return 0;
        }

        if ($subtotal < $settings->minimum_order_total_to_redeem) {
            return 0;
        }

        $balance = $customer->getRewardBalance($restaurantId);
        if (!$balance) {
            return 0;
        }

        $availablePoints = $balance->available_points;

        if ($settings->maximum_redeem_point_per_order) {
            $availablePoints = min($availablePoints, $settings->maximum_redeem_point_per_order);
        }

        if ($settings->redeem_amount_per_unit_point > 0) {
            $maxPointsBySubtotal = (int) floor($subtotal / $settings->redeem_amount_per_unit_point);
            $availablePoints = min($availablePoints, $maxPointsBySubtotal);
        }

        return max(0, $availablePoints);
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
        $restaurantId = $this->getRestaurantId($order);
        $settings = RewardSetting::getForRestaurant($restaurantId);

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
        $balance = RewardBalance::getForCustomer($customer->id, $restaurantId);
        if ($balance->available_points < $points) {
            throw new \Exception("Insufficient points. Available: {$balance->available_points}");
        }

        // Calculate discount
        $discountAmount = $this->calculateDiscountFromPoints($points, $restaurantId);

        try {
            DB::transaction(function () use ($order, $customer, $points, $discountAmount, $settings, $balance, $restaurantId) {
                // Create redemption transaction
                // Resolve currency safely (restaurant() helper may be null in API/queue context)
                $currencyId = null;
                try {
                    $currencyId = restaurant()?->currency_id;
                } catch (\Throwable $e) {
                    // Fallback: load from order's branch
                    $currencyId = $order->branch?->restaurant?->currency_id;
                }
                $formattedAmount = $currencyId ? currency_format($discountAmount, $currencyId) : number_format($discountAmount, 2);

                $transaction = RewardTransaction::create([
                    'customer_id' => $customer->id,
                    'restaurant_id' => $restaurantId,
                    'order_id' => $order->id,
                    'type' => 'redeem',
                    'points' => -$points, // Negative for redemption
                    'amount_value' => $discountAmount,
                    'description' => "Redeemed {$points} {$settings->reward_point_display_name} points for discount of {$formattedAmount}",
                ]);

                // Deduct from balance
                $balance->deductPoints($points);

                // Store redemption info on the order
                Order::where('id', $order->id)->update([
                    'reward_point_discount' => $discountAmount,
                    'reward_points_redeemed' => $points,
                ]);

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
                    $restaurantId = $this->getRestaurantId($order);
                    $balance = RewardBalance::getForCustomer($order->customer_id, $restaurantId);
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
                    $restaurantId = $this->getRestaurantId($order);
                    $balance = RewardBalance::getForCustomer($order->customer_id, $restaurantId);
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
    public function adjustPoints(Customer $customer, int $points, ?string $description = null, $restaurantId = null): RewardTransaction
    {
        $restaurantId = $restaurantId ?? restaurant()?->id;
        if (!$restaurantId) {
            throw new \InvalidArgumentException('Restaurant ID is required for point adjustment');
        }
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

