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

    public static function fetchDailyAggregates(array $dateTimeData, ?int $waiterId = null): Collection
    {
        $paymentsByOrder = self::paymentsByOrderSubquery();

        $query = Order::query()
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

    public static function fetchOutstandingByDate(array $dateTimeData, ?int $waiterId = null): Collection
    {
        $query = Order::query();
        self::applyOrderDateTimeWindow($query, $dateTimeData, 'date_time');
        $query->where('status', 'payment_due');

        if ($waiterId) {
            $query->where('waiter_id', $waiterId);
        }

        return $query
            ->select(
                DB::raw('DATE(date_time) as date'),
                DB::raw('COUNT(*) as outstanding_orders'),
                DB::raw('SUM(total) as outstanding_amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');
    }
}
