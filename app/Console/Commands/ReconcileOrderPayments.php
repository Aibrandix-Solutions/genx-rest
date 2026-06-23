<?php

namespace App\Console\Commands;

use App\Models\BranchPaymentAccountSetting;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fixes order payment rows that no longer match orders.total:
 * - Scales down excess non-due payments (duplicate / over-collection)
 * - Optionally consolidates multiple `due` placeholder rows into one
 *
 * PaymentObserver::updated() fires on each payment->update(), so AccountTransaction
 * records and PaymentAccount balances are corrected automatically.
 */
class ReconcileOrderPayments extends Command
{
    protected $signature = 'orders:reconcile-payments
                            {--dry-run : Preview changes without saving}
                            {--force : Skip confirmation prompt}
                            {--consolidate-due : Merge multiple due placeholder rows per order into one}';

    protected $description = 'Fix orders where payment rows exceed orders.total or duplicate due placeholders exist';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $consolidateDue = (bool) $this->option('consolidate-due');

        $affected = $this->ordersWithPaymentExcess();

        if ($affected->isEmpty() && ! $consolidateDue) {
            $this->info('No orders with excess non-due payments found.');

            return self::SUCCESS;
        }

        if ($affected->isNotEmpty()) {
            $this->info('Orders where SUM(non-due payments) > orders.total:');
            $this->table(
                ['Order #', 'Order ID', 'Status', 'orders.total', 'payments.sum', 'Excess'],
                $affected->map(fn ($r) => [
                    $r->order_number,
                    $r->order_id,
                    $r->status,
                    number_format($r->order_total, 2),
                    number_format($r->payments_sum, 2),
                    number_format($r->excess, 2),
                ])
            );
        }

        $dueDuplicates = $consolidateDue ? $this->ordersWithMultipleDueRows() : collect();

        if ($consolidateDue) {
            if ($dueDuplicates->isEmpty()) {
                $this->info('No orders with multiple due placeholder rows found.');
            } else {
                $this->info('Orders with multiple due placeholder rows:');
                $this->table(
                    ['Order #', 'Order ID', 'due rows', 'due sum'],
                    $dueDuplicates->map(fn ($r) => [
                        $r->order_number,
                        $r->order_id,
                        $r->due_count,
                        number_format($r->due_sum, 2),
                    ])
                );
            }
        }

        if ($affected->isEmpty() && $dueDuplicates->isEmpty()) {
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Dry-run mode — no changes saved.');

            return self::SUCCESS;
        }

        $fixCount = $affected->count() + $dueDuplicates->count();
        if (! $this->option('force') && ! $this->confirm("Apply fixes to {$fixCount} order(s)?")) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($affected, $dueDuplicates) {
            foreach ($affected as $row) {
                $this->scaleExcess((int) $row->order_id, (float) $row->excess);
                $this->line("  ✓ Order #{$row->order_number} — reduced non-due payments by {$row->excess}");
            }

            foreach ($dueDuplicates as $row) {
                $this->consolidateDueRows((int) $row->order_id);
                $this->line("  ✓ Order #{$row->order_number} — consolidated due placeholder rows");
            }
        });

        $this->info('Done. PaymentObserver handled AccountTransaction updates automatically.');

        return self::SUCCESS;
    }

    private function ordersWithPaymentExcess()
    {
        return DB::table('orders as o')
            ->join('payments as p', 'o.id', '=', 'p.order_id')
            ->whereIn('o.status', ['paid', 'payment_due'])
            ->where('p.payment_method', '!=', 'due')
            ->groupBy('o.id', 'o.total', 'o.order_number', 'o.status')
            ->havingRaw('ROUND(SUM(p.amount), 2) > ROUND(o.total, 2)')
            ->select(
                'o.id as order_id',
                'o.order_number',
                'o.status',
                'o.total as order_total',
                DB::raw('ROUND(SUM(p.amount), 2) as payments_sum'),
                DB::raw('ROUND(SUM(p.amount) - o.total, 2) as excess'),
            )
            ->get();
    }

    private function ordersWithMultipleDueRows()
    {
        return DB::table('orders as o')
            ->join('payments as p', 'o.id', '=', 'p.order_id')
            ->where('p.payment_method', 'due')
            ->groupBy('o.id', 'o.order_number')
            ->havingRaw('COUNT(p.id) > 1')
            ->select(
                'o.id as order_id',
                'o.order_number',
                DB::raw('COUNT(p.id) as due_count'),
                DB::raw('ROUND(SUM(p.amount), 2) as due_sum'),
            )
            ->get();
    }

    private function scaleExcess(int $orderId, float $excess): void
    {
        $payments = Payment::where('order_id', $orderId)
            ->where('payment_method', '!=', 'due')
            ->orderByDesc('id')
            ->get();

        $remaining = round($excess, 2);

        foreach ($payments as $payment) {
            if ($remaining <= 0) {
                break;
            }

            $canReduce = min((float) $payment->amount, $remaining);
            $newAmount = round($payment->amount - $canReduce, 2);

            if ($newAmount <= 0) {
                $payment->delete();
            } else {
                $payment->update(['amount' => $newAmount]);
            }

            $remaining = round($remaining - $canReduce, 2);
        }

        $this->syncOrderPaymentState($orderId);
    }

    private function consolidateDueRows(int $orderId): void
    {
        $order = Order::with('payments')->find($orderId);

        if (! $order) {
            return;
        }

        $outstanding = $order->outstandingAmount();
        $epsilon = 0.0001;

        Payment::where('order_id', $orderId)->where('payment_method', 'due')->delete();

        if ($outstanding > $epsilon && $order->canRecordDueBalance()) {
            $dueAccount = $order->branch_id
                ? BranchPaymentAccountSetting::getDefaultAccount((int) $order->branch_id, 'due')
                : null;

            Payment::create([
                'order_id' => $orderId,
                'payment_method' => 'due',
                'amount' => $outstanding,
                'payment_account_id' => $dueAccount?->id,
                'branch_id' => $order->branch_id,
                'restaurant_id' => $order->restaurant_id,
            ]);
        }

        $this->syncOrderPaymentState($orderId);
    }

    private function syncOrderPaymentState(int $orderId): void
    {
        $order = Order::find($orderId);

        if (! $order) {
            return;
        }

        $amountPaid = $order->nonDuePaymentsSum();
        $epsilon = 0.0001;
        $outstanding = $order->outstandingAmount();

        $status = $outstanding <= $epsilon
            ? 'paid'
            : ($order->canRecordDueBalance() ? 'payment_due' : $order->status);

        $order->update([
            'amount_paid' => round($amountPaid, 2),
            'status' => $status,
        ]);
    }
}
