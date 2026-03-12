<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Finds paid orders where the sum of non-due payment records exceeds orders.total
 * (caused by item deletions before scalePaymentsToNewTotal was introduced) and
 * scales the most-recent payment(s) down to eliminate the excess.
 *
 * PaymentObserver::updated() fires on each payment->update(), so AccountTransaction
 * records and PaymentAccount balances are corrected automatically.
 */
class ReconcileOrderPayments extends Command
{
    protected $signature   = 'orders:reconcile-payments {--dry-run : Preview changes without saving} {--force : Skip confirmation prompt}';
    protected $description = 'Fix paid orders where payment amounts exceed orders.total due to post-payment item deletions';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Find all paid orders where sum of non-due payments > orders.total
        $affected = DB::table('orders as o')
            ->join('payments as p', 'o.id', '=', 'p.order_id')
            ->where('o.status', 'paid')
            ->where('p.payment_method', '!=', 'due')
            ->groupBy('o.id', 'o.total', 'o.order_number')
            ->havingRaw('ROUND(SUM(p.amount), 2) > ROUND(o.total, 2)')
            ->select(
                'o.id as order_id',
                'o.order_number',
                'o.total as order_total',
                DB::raw('ROUND(SUM(p.amount), 2) as payments_sum'),
                DB::raw('ROUND(SUM(p.amount) - o.total, 2) as excess'),
            )
            ->get();

        if ($affected->isEmpty()) {
            $this->info('No mismatched orders found. Nothing to do.');
            return self::SUCCESS;
        }

        $this->table(
            ['Order #', 'Order ID', 'orders.total', 'payments.sum', 'Excess'],
            $affected->map(fn ($r) => [
                $r->order_number, $r->order_id,
                number_format($r->order_total, 2),
                number_format($r->payments_sum, 2),
                number_format($r->excess, 2),
            ])
        );

        if ($dryRun) {
            $this->warn('Dry-run mode — no changes saved.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("Fix {$affected->count()} order(s)?")) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($affected) {
            foreach ($affected as $row) {
                $this->scaleExcess((int) $row->order_id, (float) $row->order_total, (float) $row->excess);
                $this->line("  ✓ Order #{$row->order_number} — reduced payments by {$row->excess}");
            }
        });

        $this->info('Done. PaymentObserver handled AccountTransaction updates automatically.');
        return self::SUCCESS;
    }

    private function scaleExcess(int $orderId, float $newTotal, float $excess): void
    {
        // Load non-due payments newest-first; reduce from the most recent
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
            $payment->update(['amount' => round($payment->amount - $canReduce, 2)]);
            $remaining = round($remaining - $canReduce, 2);
        }

        // Sync orders.amount_paid
        $newAmountPaid = Payment::where('order_id', $orderId)
            ->where('payment_method', '!=', 'due')
            ->sum('amount');

        \App\Models\Order::where('id', $orderId)
            ->update(['amount_paid' => $newAmountPaid]);
    }
}
