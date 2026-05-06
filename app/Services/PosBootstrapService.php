<?php

namespace App\Services;

use App\Models\DeliveryExecutive;
use App\Models\DeliveryPlatform;
use App\Models\ItemCategory;
use App\Models\OrderType;
use App\Models\Tax;
use App\Models\User;
use App\Scopes\BranchScope;
use Illuminate\Support\Facades\Cache;

class PosBootstrapService
{
    public function resolve(): array
    {
        $cacheKey = $this->cacheKey();
        $cacheTTL = 86400;

        $cachedData = Cache::get($cacheKey);
        $cached = !is_null($cachedData);

        $stableData = $cached
            ? $cachedData
            : Cache::remember($cacheKey, $cacheTTL, function () {
                return $this->stableBuild();
            });

        $data = array_merge($stableData, [
            'delivery_executives' => $this->freshDeliveryExecutives(),
            'pos_preferences' => [
                'default_order_type_id' => auth()->user()?->default_order_type_id,
                'selected_delivery_app' => session()->get('pos.delivery_app_id', 'default'),
            ],
        ]);

        return [
            'cached' => $cached,
            'data' => $data,
        ];
    }

    public function clearCache(?int $restaurantId = null, ?int $branchId = null): void
    {
        if (!$restaurantId) {
            return;
        }

        $normalizedBranchId = $branchId ?? '0';
        Cache::forget('pos.bootstrap.v2.' . $restaurantId . '.' . $normalizedBranchId);
    }

    public function cacheKey(): string
    {
        $restaurant = $this->restaurantContext();
        $branch = $this->branchContext();

        return 'pos.bootstrap.v2.' . ($restaurant?->id ?? 0) . '.' . ($branch?->id ?? 0);
    }

    public function stableBuild(): array
    {
        $restaurant = $this->restaurantContext();
        $branch = $this->branchContext();

        return [
            'categories' => ItemCategory::query()
                ->orderBy('id')
                ->get(),

            'order_types' => OrderType::where('is_active', true)
                ->select('id', 'order_type_name', 'slug', 'type')
                ->orderBy('order_type_name')
                ->get(),

            'delivery_platforms' => DeliveryPlatform::where('is_active', true)
                ->select('id', 'name', 'commission_type', 'commission_value')
                ->orderBy('name')
                ->get(),

            'waiters' => $restaurant && $branch
                ? User::withoutGlobalScope(BranchScope::class)
                    ->where(function ($q) use ($branch) {
                        return $q->where('branch_id', $branch->id)
                            ->orWhereNull('branch_id');
                    })
                    ->role('waiter_' . $restaurant->id)
                    ->where('restaurant_id', $restaurant->id)
                    ->select('id', 'name')
                    ->get()
                : collect(),

            'taxes' => Tax::select('id', 'tax_name', 'tax_percent')
                ->get(),

            'tax_mode' => $restaurant?->tax_mode ?? 'item',
            'pickup_days_range' => $restaurant?->pickup_days_range ?? 1,
            'restaurant_id' => $restaurant?->id,
            'branch_id' => $branch?->id,

            // Reward Points settings for POS
            'reward_settings' => $this->buildRewardSettings($restaurant),
        ];
    }

    private function buildRewardSettings($restaurant): ?array
    {
        if (! $restaurant?->id) {
            return null;
        }

        $settings = \App\Models\RewardSetting::getForRestaurant($restaurant->id);

        if (!in_array('Reward Point', restaurant_modules()) || !$settings || !$settings->enable_reward_point) {
            return null;
        }

        return [
            'enabled' => true,
            'display_name' => $settings->reward_point_display_name ?? 'Reward',
            'amount_spend_for_unit_point' => (float) ($settings->amount_spend_for_unit_point ?? 1),
            'redeem_amount_per_unit_point' => (float) ($settings->redeem_amount_per_unit_point ?? 1),
            'minimum_order_total_to_redeem' => (float) ($settings->minimum_order_total_to_redeem ?? 0),
            'minimum_redeem_point' => (int) ($settings->minimum_redeem_point ?? 0),
            'maximum_redeem_point_per_order' => $settings->maximum_redeem_point_per_order ? (int) $settings->maximum_redeem_point_per_order : null,
        ];
    }

    public function freshDeliveryExecutives()
    {
        return DeliveryExecutive::where('status', 'available')
            ->select('id', 'name', 'phone', 'status')
            ->get();
    }

    private function restaurantContext(): mixed
    {
        try {
            return restaurant() ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function branchContext(): mixed
    {
        try {
            return branch() ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
