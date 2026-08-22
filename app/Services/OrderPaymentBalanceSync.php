<?php

namespace App\Services;

use App\Models\BranchPaymentAccountSetting;
use App\Models\Order;
use App\Models\Payment;
use Modules\Hotel\Services\OrderFolioSettlement;

/**
 * Keeps payment rows and order status aligned when orders.total changes
 * after billing (Vue POS API, legacy POS, order detail).
 */
class OrderPaymentBalanceSync
{
    /**
     * After total changes on a paid/payment_due order: trim over-collection, then sync due/status.
     */
    public static function reconcileAfterTotalChange(
        Order $order,
        bool $allowImmediatePaymentWithoutCustomer = false
    ): void {
        if (! in_array($order->status, ['paid', 'payment_due'], true)) {
            return;
        }

        $newTotal = (float) $order->total;

        self::scalePaymentsToNewTotal($order, $newTotal);

        $order->refresh();
        $order->load('payments');

        self::syncPostPaymentBalance($order, $newTotal, $allowImmediatePaymentWithoutCustomer);
    }

    /**
     * Reduce overpaid non-due payment amounts so their sum does not exceed the new total.
     */
    public static function scalePaymentsToNewTotal(Order $order, float $newTotal): void
    {
        $payments = $order->payments()
            ->where('payment_method', '!=', 'due')
            ->orderBy('id')
            ->get();

        $excess = round($payments->sum('amount') - $newTotal, 2);

        if ($excess <= 0) {
            return;
        }

        foreach ($payments->sortByDesc('id') as $payment) {
            if ($excess <= 0) {
                break;
            }

            $canReduce = min((float) $payment->amount, $excess);
            $newAmount = round($payment->amount - $canReduce, 2);

            if ($newAmount <= 0) {
                $payment->delete();
            } else {
                $payment->update(['amount' => $newAmount]);
            }

            $excess = round($excess - $canReduce, 2);
        }

        $order->update([
            'amount_paid' => $order->payments()
                ->where('payment_method', '!=', 'due')
                ->sum('amount'),
        ]);
    }

    /**
     * Paid/payment_due orders edited after collection: existing payments are prepayment;
     * any shortfall is tracked as a single `due` row (replaces prior due placeholders).
     */
    public static function syncPostPaymentBalance(
        Order $order,
        float $newTotal,
        bool $allowImmediatePaymentWithoutCustomer = false
    ): void {
        if (class_exists(OrderFolioSettlement::class)
            && OrderFolioSettlement::isChargedToFolio($order)) {
            $order->update([
                'amount_paid' => 0,
                'status' => OrderFolioSettlement::isFolioSettled($order)
                    ? OrderFolioSettlement::STATUS_FOLIO_SETTLED
                    : 'payment_due',
            ]);

            return;
        }

        $amountPaid = $order->split_type === 'items'
            ? (float) $order->splitOrders()->where('status', 'paid')->sum('amount')
            : (float) $order->payments()
                ->where('payment_method', '!=', 'due')
                ->sum('amount');

        $shortfall = round(max(0, $newTotal - $amountPaid), 2);

        Payment::where('order_id', $order->id)
            ->where('payment_method', 'due')
            ->delete();

        if ($shortfall > 0) {
            if (! $order->canRecordDueBalance()) {
                if ($allowImmediatePaymentWithoutCustomer) {
                    $order->update([
                        'amount_paid' => round($amountPaid, 2),
                        'status' => 'billed',
                    ]);

                    return;
                }

                abort(422, 'Walk-in paid orders require immediate payment for additional KOT items.');
            }

            $dueAccount = $order->branch_id
                ? BranchPaymentAccountSetting::getDefaultAccount((int) $order->branch_id, 'due')
                : null;

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => 'due',
                'amount' => $shortfall,
                'payment_account_id' => $dueAccount?->id,
                'branch_id' => $order->branch_id,
                'restaurant_id' => $order->restaurant_id,
            ]);
        }

        $order->update([
            'amount_paid' => round($amountPaid, 2),
            'status' => $shortfall > 0 ? 'payment_due' : 'paid',
        ]);
    }

    /**
     * Livewire/UI wrapper: same as reconcileAfterTotalChange but turns walk-in shortfall abort into RuntimeException.
     */
    public static function reconcileAfterTotalChangeForUi(
        Order $order,
        bool $allowImmediatePaymentWithoutCustomer = false
    ): void {
        try {
            self::reconcileAfterTotalChange($order, $allowImmediatePaymentWithoutCustomer);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($e->getStatusCode() === 422) {
                throw new \RuntimeException(
                    $e->getMessage() ?: __('modules.order.customerRequiredForDuePayment'),
                    0,
                    $e
                );
            }

            throw $e;
        }
    }
}
