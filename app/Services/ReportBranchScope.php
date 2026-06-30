<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Expenses;
use App\Models\ItemCategory;
use App\Models\Order;
use App\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ReportBranchScope
{
    public const FILTER_CURRENT = 'current';

    public const FILTER_ALL = 'all';

    public static function normalizeFilter(?string $filter): string
    {
        $filter = (string) ($filter ?? self::FILTER_CURRENT);

        if ($filter === self::FILTER_ALL || $filter === self::FILTER_CURRENT) {
            return $filter;
        }

        if (ctype_digit($filter)) {
            return $filter;
        }

        return self::FILTER_CURRENT;
    }

    public static function validateFilter(?string $filter, int $restaurantId): string
    {
        $filter = self::normalizeFilter($filter);

        if ($filter === self::FILTER_ALL || $filter === self::FILTER_CURRENT) {
            return $filter;
        }

        $exists = Branch::query()
            ->where('restaurant_id', $restaurantId)
            ->where('id', (int) $filter)
            ->exists();

        return $exists ? $filter : self::FILTER_CURRENT;
    }

    public static function restaurantBranchIds(int $restaurantId): array
    {
        return Cache::remember(
            "report_branch_ids:{$restaurantId}",
            now()->addMinutes(5),
            fn () => Branch::query()
                ->where('restaurant_id', $restaurantId)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
        );
    }

    public static function clearRestaurantBranchIdsCache(int $restaurantId): void
    {
        Cache::forget("report_branch_ids:{$restaurantId}");
    }

    public static function categoriesBaseQuery(?string $filter = self::FILTER_CURRENT, ?int $restaurantId = null): Builder
    {
        $restaurantId ??= (int) (restaurant()?->id ?? 0);
        $filter = self::validateFilter($filter, $restaurantId);

        $query = ItemCategory::withoutGlobalScope(BranchScope::class);

        if ($restaurantId) {
            $query->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        }

        self::applyToColumn($query, 'item_categories.branch_id', $filter, $restaurantId);

        return $query;
    }

    public static function applyCategoryOrdersScope($query, array $dateTimeData, ?string $branchFilter): void
    {
        $query->join('orders', 'orders.id', '=', 'order_items.order_id');
        self::applyToColumn($query, 'orders.branch_id', $branchFilter);
        self::applyToColumn($query, 'menu_items.branch_id', $branchFilter);
        $query->whereIn('orders.status', SalesReportData::reportOrderStatuses())
            ->whereBetween('orders.date_time', [$dateTimeData['startDateTime'], $dateTimeData['endDateTime']]);
        SalesReportData::applyOrderDateTimeWindow($query, $dateTimeData, 'orders.date_time');
    }

    /**
     * Eager loads for order reports when branch filter may differ from session branch.
     *
     * @return array<string, callable>
     */
    public static function eagerLoadOrderItemsForReport(): array
    {
        return [
            'items' => fn ($q) => $q->withoutGlobalScope(BranchScope::class)->with([
                'menuItem' => fn ($q2) => $q2->withoutGlobalScope(BranchScope::class),
            ]),
        ];
    }

    /**
     * Eager loads for order list/export reports (items, payments, relations).
     *
     * @return array<string, callable|string>
     */
    public static function eagerLoadsForOrderReport(): array
    {
        return array_merge(
            self::eagerLoadOrderItemsForReport(),
            [
                'payments' => fn ($q) => $q
                    ->withoutGlobalScope(BranchScope::class)
                    ->where('payment_method', '!=', 'due')
                    ->orderBy('id'),
                'waiter',
                'customer',
                'branch',
            ],
        );
    }

    /**
     * Eager loads for OrderDetail opened from a report (includes nested item relations).
     *
     * @return array<string, callable|string>
     */
    public static function eagerLoadsForOrderDetailReport(): array
    {
        return array_merge(
            self::eagerLoadOrderItemsForReport(),
            [
                'payments' => fn ($q) => $q->withoutGlobalScope(BranchScope::class)->orderBy('id'),
                'waiter',
                'customer',
                'branch',
                'items.menuItemVariation' => fn ($q) => $q->withoutGlobalScope(BranchScope::class),
                'items.comboPack' => fn ($q) => $q->withoutGlobalScope(BranchScope::class),
                'cancelReason',
                'hotelReservation.room',
                'hotelReservation.guest',
            ],
        );
    }

    /**
     * Load an order for report viewing, scoped to restaurant (not session branch).
     */
    public static function findOrderForReport(mixed $identifier, ?int $restaurantId = null): ?Order
    {
        $restaurantId ??= (int) (restaurant()?->id ?? 0);

        if ($restaurantId <= 0) {
            return null;
        }

        return Order::withoutGlobalScope(BranchScope::class)
            ->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId))
            ->whereIdentifier($identifier)
            ->with(self::eagerLoadsForOrderDetailReport())
            ->first();
    }

    /**
     * Apply payment-method filter on order queries (bypasses session BranchScope on payments).
     */
    public static function applyOrderPaymentMethodFilter(Builder $query, string $paymentMethod): void
    {
        if ($paymentMethod === 'due') {
            $query->where('orders.status', 'payment_due')
                ->whereDoesntHave('payments', fn ($q) => $q->withoutGlobalScope(BranchScope::class));

            return;
        }

        $query->whereHas('payments', fn ($q) => $q
            ->withoutGlobalScope(BranchScope::class)
            ->where('payment_method', $paymentMethod));
    }

    /**
     * @return array<string, callable>
     */
    public static function eagerLoadExpenseCategoryForReport(): array
    {
        return [
            'category' => fn ($q) => $q->withoutGlobalScope(BranchScope::class),
        ];
    }

    public static function resolveBranchId(?string $filter, ?int $restaurantId = null): ?int
    {
        $filter = self::normalizeFilter($filter);

        if ($filter === self::FILTER_ALL) {
            return null;
        }

        if ($filter === self::FILTER_CURRENT) {
            return branch()?->id;
        }

        $restaurantId ??= (int) (restaurant()?->id ?? 0);

        if ($restaurantId && self::validateFilter($filter, $restaurantId) !== $filter) {
            return branch()?->id;
        }

        return (int) $filter;
    }

    public static function exportScopeLabel(?string $filter, ?int $restaurantId = null): string
    {
        $restaurantId ??= (int) (restaurant()?->id ?? 0);
        $filter = $restaurantId > 0
            ? self::validateFilter($filter, $restaurantId)
            : self::normalizeFilter($filter);

        return __('app.branch') . ': ' . self::resolveLabel($filter, $restaurantId);
    }

    public static function appendExportScope(string $title, ?string $filter, ?int $restaurantId = null): string
    {
        return $title . ' | ' . self::exportScopeLabel($filter, $restaurantId);
    }

    public static function applyToColumn($query, string $column, ?string $filter, ?int $restaurantId = null): void
    {
        $restaurantId ??= (int) (restaurant()?->id ?? 0);
        $filter = $restaurantId > 0
            ? self::validateFilter($filter, $restaurantId)
            : self::normalizeFilter($filter);

        if ($filter === self::FILTER_ALL) {
            $ids = self::restaurantBranchIds($restaurantId);

            if ($ids === []) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->whereIn($column, $ids);

            return;
        }

        $branchId = self::resolveBranchId($filter, $restaurantId);

        if ($branchId) {
            $query->where($column, $branchId);

            return;
        }

        if ($restaurantId > 0) {
            $ids = self::restaurantBranchIds($restaurantId);

            if ($ids === []) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->whereIn($column, $ids);

            return;
        }

        $query->whereRaw('1 = 0');
    }

    public static function ordersBaseQuery(?string $filter = self::FILTER_CURRENT, ?int $restaurantId = null): Builder
    {
        $restaurantId ??= (int) (restaurant()?->id ?? 0);
        $filter = self::validateFilter($filter, $restaurantId);

        $query = Order::withoutGlobalScope(BranchScope::class);

        if ($restaurantId) {
            $query->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        }

        self::applyToColumn($query, 'orders.branch_id', $filter, $restaurantId);

        return $query;
    }

    public static function applyToOrderQuery(Builder $query, ?string $filter, ?int $restaurantId = null): Builder
    {
        $restaurantId ??= (int) (restaurant()?->id ?? 0);
        $filter = self::validateFilter($filter, $restaurantId);

        if ($restaurantId) {
            $query->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        }

        self::applyToColumn($query, $query->getModel()->getTable() . '.branch_id', $filter, $restaurantId);

        return $query;
    }

    public static function expensesBaseQuery(?string $filter = self::FILTER_CURRENT): Builder
    {
        $restaurantId = (int) (restaurant()?->id ?? 0);
        $filter = self::validateFilter($filter, $restaurantId);

        $query = Expenses::withoutGlobalScope(BranchScope::class);

        if ($restaurantId) {
            $query->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        }

        self::applyToColumn($query, 'expenses.branch_id', $filter, $restaurantId);

        return $query;
    }

    public static function resolveLabel(?string $filter, ?int $restaurantId = null): string
    {
        $filter = self::normalizeFilter($filter);
        $restaurantId ??= (int) (restaurant()?->id ?? 0);

        if ($filter === self::FILTER_ALL) {
            return __('app.all_branches');
        }

        if ($filter === self::FILTER_CURRENT) {
            return branch()?->name ?? __('app.all');
        }

        return Branch::query()
            ->where('restaurant_id', $restaurantId)
            ->where('id', (int) $filter)
            ->value('name') ?? __('app.all');
    }

    public static function waiterBranchId(?string $filter): int
    {
        return self::resolveBranchId($filter) ?? (int) branch()?->id;
    }
}
