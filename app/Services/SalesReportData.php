<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesReportData
{
    public static function reportOrderStatuses(): array
    {
        return ['paid', 'payment_due'];
    }

    public static function applyOrderDateTimeWindow($query, array $dateTimeData, string $dateTimeColumn = 'orders.date_time'): void
    {
        $query->whereBetween($dateTimeColumn, [$dateTimeData['startDateTime'], $dateTimeData['endDateTime']]);

        if ($dateTimeData['startTime'] < $dateTimeData['endTime']) {
            $query->whereRaw("TIME({$dateTimeColumn}) BETWEEN ? AND ?", [
                $dateTimeData['startTime'],
                $dateTimeData['endTime'],
            ]);
        } else {
            $query->where(function ($sub) use ($dateTimeData, $dateTimeColumn) {
                $sub->whereRaw("TIME({$dateTimeColumn}) >= ?", [$dateTimeData['startTime']])
                    ->orWhereRaw("TIME({$dateTimeColumn}) <= ?", [$dateTimeData['endTime']]);
            });
        }
    }

    /**
     * Align item-level reports (Item, Category export rows) with Sales Report scope:
     * branch filter, same statuses, same date/time window.
     */
    public static function applyItemReportOrderFilters($query, array $dateTimeData, ?string $branchFilter = ReportBranchScope::FILTER_CURRENT): void
    {
        ReportBranchScope::applyToColumn($query, 'orders.branch_id', $branchFilter);

        $query->whereIn('orders.status', self::reportOrderStatuses());
        self::applyOrderDateTimeWindow($query, $dateTimeData);
    }

    public static function applyItemReportSearchFilter($query, ?string $searchTerm): void
    {
        $searchTerm = trim((string) ($searchTerm ?? ''));

        if ($searchTerm === '') {
            return;
        }

        $query->where(function ($q) use ($searchTerm) {
            $q->where('menu_items.item_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('item_categories.category_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('menu_item_variations.variation', 'like', '%' . $searchTerm . '%');
        });
    }

    /**
     * Item line revenue for the same orders included in Sales Report totals.
     */
    public static function fetchItemRevenueTotal(array $dateTimeData, ?string $branchFilter = ReportBranchScope::FILTER_CURRENT, ?string $searchTerm = null): object
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->leftJoin('menu_item_variations', 'menu_item_variations.id', '=', 'order_items.menu_item_variation_id')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'menu_items.item_category_id');

        self::applyItemReportOrderFilters($query, $dateTimeData, $branchFilter);
        self::applyItemReportSearchFilter($query, $searchTerm);

        return $query->selectRaw('COALESCE(SUM(order_items.quantity), 0) as total_qty')
            ->selectRaw('COALESCE(SUM(order_items.amount), 0) as total_revenue')
            ->first();
    }

    public static function paymentsByOrderSubquery()
    {
        return DB::table('payments')
            ->select('order_id')
            ->where('payment_method', '!=', 'due')
            ->selectRaw('SUM(amount) as paid_total')
            ->selectRaw('SUM(CASE WHEN payment_method = "cash" THEN amount ELSE 0 END) as cash_raw')
            ->selectRaw('SUM(CASE WHEN payment_method = "card" THEN amount ELSE 0 END) as card_raw')
            ->selectRaw('SUM(CASE WHEN payment_method = "upi" THEN amount ELSE 0 END) as upi_raw')
            ->selectRaw('SUM(CASE WHEN payment_method = "bank_transfer" THEN amount ELSE 0 END) as bank_transfer_raw')
            ->selectRaw('SUM(CASE WHEN payment_method = "razorpay" THEN amount ELSE 0 END) as razorpay_raw')
            ->selectRaw('SUM(CASE WHEN payment_method = "stripe" THEN amount ELSE 0 END) as stripe_raw')
            ->selectRaw('SUM(CASE WHEN payment_method = "flutterwave" THEN amount ELSE 0 END) as flutterwave_raw')
            ->groupBy('order_id');
    }

    /**
     * Scale per-method payment totals when duplicate rows exceed order.total (paid orders only).
     */
    private static function scaledPaymentSql(string $rawExpression): string
    {
        return '(CASE
            WHEN orders.status = \'paid\'
                AND COALESCE(pay.paid_total, 0) > orders.total
                AND COALESCE(pay.paid_total, 0) > 0
                AND orders.total > 0
            THEN (' . $rawExpression . ') * orders.total / pay.paid_total
            ELSE COALESCE(' . $rawExpression . ', 0)
        END)';
    }

    public static function ordersBaseQuery(?string $branchFilter = ReportBranchScope::FILTER_CURRENT)
    {
        return ReportBranchScope::ordersBaseQuery($branchFilter);
    }

    public static function fetchDailyAggregates(array $dateTimeData, ?int $waiterId = null, ?string $branchFilter = ReportBranchScope::FILTER_CURRENT): Collection
    {
        $paymentsByOrder = self::paymentsByOrderSubquery();

        $query = self::ordersBaseQuery($branchFilter)
            ->leftJoinSub($paymentsByOrder, 'pay', 'orders.id', '=', 'pay.order_id');

        self::applyOrderDateTimeWindow($query, $dateTimeData);
        $query->whereIn('orders.status', self::reportOrderStatuses());

        if ($waiterId) {
            $query->where('orders.waiter_id', $waiterId);
        }

        return $query
            ->select(
                DB::raw('DATE(orders.date_time) as date'),
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.total) as orders_total'),
                DB::raw('SUM(orders.discount_amount) as discount_amount'),
                DB::raw('SUM(orders.tip_amount) as tip_amount'),
                DB::raw('SUM(orders.delivery_fee) as delivery_fee'),
                DB::raw('SUM(' . self::scaledPaymentSql('pay.cash_raw') . ') as cash_amount'),
                DB::raw('SUM(' . self::scaledPaymentSql('pay.card_raw') . ') as card_amount'),
                DB::raw('SUM(' . self::scaledPaymentSql('pay.upi_raw') . ') as upi_amount'),
                DB::raw('SUM(' . self::scaledPaymentSql('pay.bank_transfer_raw') . ') as bank_transfer_amount'),
                DB::raw('SUM(' . self::scaledPaymentSql('pay.razorpay_raw') . ') as razorpay_amount'),
                DB::raw('SUM(' . self::scaledPaymentSql('pay.stripe_raw') . ') as stripe_amount'),
                DB::raw('SUM(' . self::scaledPaymentSql('pay.flutterwave_raw') . ') as flutterwave_amount'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public static function fetchOutstandingByDate(array $dateTimeData, ?int $waiterId = null, ?string $branchFilter = ReportBranchScope::FILTER_CURRENT): Collection
    {
        $query = self::ordersBaseQuery($branchFilter);
        self::applyOrderDateTimeWindow($query, $dateTimeData, 'orders.date_time');
        $query->where('orders.status', 'payment_due');

        if ($waiterId) {
            $query->where('orders.waiter_id', $waiterId);
        }

        return $query
            ->select(
                DB::raw('DATE(orders.date_time) as date'),
                DB::raw('COUNT(*) as outstanding_orders'),
                DB::raw('SUM(orders.total) as outstanding_amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');
    }

    /**
     * Category sales keyed by item_categories.id (bypasses session BranchScope on menu_items).
     */
    public static function fetchCategorySalesAggregates(array $dateTimeData, ?string $branchFilter = ReportBranchScope::FILTER_CURRENT): Collection
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->join('item_categories', 'item_categories.id', '=', 'menu_items.item_category_id');

        self::applyItemReportOrderFilters($query, $dateTimeData, $branchFilter);
        ReportBranchScope::applyToColumn($query, 'item_categories.branch_id', $branchFilter);
        ReportBranchScope::applyToColumn($query, 'menu_items.branch_id', $branchFilter);

        return $query
            ->select('item_categories.id as category_id')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as quantity_sold')
            ->selectRaw('COALESCE(SUM(order_items.amount), 0) as total_revenue')
            ->groupBy('item_categories.id')
            ->get()
            ->keyBy('category_id');
    }

    /**
     * Category report rows: categories for the report branch scope with pre-aggregated sales.
     */
    public static function fetchCategoryReportRows(array $dateTimeData, ?string $branchFilter = ReportBranchScope::FILTER_CURRENT): Collection
    {
        $categories = ReportBranchScope::categoriesBaseQuery($branchFilter)
            ->orderBy('category_name')
            ->get();

        $aggregates = self::fetchCategorySalesAggregates($dateTimeData, $branchFilter);

        return $categories->map(function ($category) use ($aggregates) {
            $stats = $aggregates->get($category->id);
            $category->quantity_sold = (int) ($stats->quantity_sold ?? 0);
            $category->total_revenue = (float) ($stats->total_revenue ?? 0);

            return $category;
        });
    }
}
