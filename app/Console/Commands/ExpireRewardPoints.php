<?php

namespace App\Console\Commands;

use App\Models\RewardBalance;
use App\Models\RewardTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireRewardPoints extends Command
{
    protected $signature = 'app:expire-reward-points';

    protected $description = 'Expire reward points that have passed their expiry date and deduct from customer balances';

    public function handle(): int
    {
        $expiredTransactions = RewardTransaction::where('type', 'earn')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('points', '>', 0)
            ->get()
            ->filter(function (RewardTransaction $earn) {
                $alreadyExpired = (int) RewardTransaction::query()
                    ->where('type', 'expire')
                    ->where('meta->source_reward_transaction_id', $earn->id)
                    ->get()
                    ->sum(fn (RewardTransaction $t) => abs((int) $t->points));

                return $earn->points > $alreadyExpired;
            });

        if ($expiredTransactions->isEmpty()) {
            $this->info('No expired reward points found.');
            return 0;
        }

        $totalExpired = 0;
        $customersAffected = 0;

        foreach ($expiredTransactions as $transaction) {
            try {
                DB::transaction(function () use ($transaction, &$totalExpired, &$customersAffected) {
                    $alreadyExpired = (int) RewardTransaction::query()
                        ->where('type', 'expire')
                        ->where('meta->source_reward_transaction_id', $transaction->id)
                        ->get()
                        ->sum(fn (RewardTransaction $t) => abs((int) $t->points));

                    $remainingEarnPoints = max(0, (int) $transaction->points - $alreadyExpired);
                    if ($remainingEarnPoints <= 0) {
                        return;
                    }

                    $balance = RewardBalance::getForCustomer(
                        $transaction->customer_id,
                        $transaction->restaurant_id
                    );

                    if ($balance && $balance->available_points > 0) {
                        $pointsToExpire = min($remainingEarnPoints, $balance->available_points);

                        if ($pointsToExpire > 0) {
                            $orderRef = $transaction->order_id ?: '—';
                            RewardTransaction::create([
                                'customer_id' => $transaction->customer_id,
                                'restaurant_id' => $transaction->restaurant_id,
                                'order_id' => $transaction->order_id,
                                'type' => 'expire',
                                'points' => -$pointsToExpire,
                                'description' => "Expired {$pointsToExpire} points (earn txn #{$transaction->id}, order #{$orderRef})",
                                'meta' => [
                                    'source_reward_transaction_id' => $transaction->id,
                                ],
                            ]);

                            $balance->deductPoints($pointsToExpire);
                            $totalExpired += $pointsToExpire;
                            $customersAffected++;
                        }
                    }
                });
            } catch (\Exception $e) {
                Log::error('Error expiring reward points for transaction ' . $transaction->id . ': ' . $e->getMessage());
            }
        }

        $this->info("Expired {$totalExpired} points across {$customersAffected} customers.");
        Log::info("Reward points expiry run: {$totalExpired} points expired, {$customersAffected} customers affected.");

        return 0;
    }
}
