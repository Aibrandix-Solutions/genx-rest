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
            ->get();

        if ($expiredTransactions->isEmpty()) {
            $this->info('No expired reward points found.');
            return 0;
        }

        $totalExpired = 0;
        $customersAffected = 0;

        foreach ($expiredTransactions as $transaction) {
            try {
                DB::transaction(function () use ($transaction, &$totalExpired, &$customersAffected) {
                    $balance = RewardBalance::getForCustomer(
                        $transaction->customer_id,
                        $transaction->restaurant_id
                    );

                    if ($balance && $balance->points_balance > 0) {
                        $pointsToExpire = min($transaction->points, $balance->points_balance);

                        if ($pointsToExpire > 0) {
                            // Create expire transaction
                            RewardTransaction::create([
                                'customer_id' => $transaction->customer_id,
                                'restaurant_id' => $transaction->restaurant_id,
                                'order_id' => $transaction->order_id,
                                'type' => 'expire',
                                'points' => -$pointsToExpire,
                                'description' => "Expired {$pointsToExpire} points from order #{$transaction->order_id}",
                            ]);

                            // Deduct from balance
                            $balance->deductPoints($pointsToExpire);
                            $totalExpired += $pointsToExpire;
                            $customersAffected++;
                        }
                    }

                    // Mark the original earn transaction as expired by zeroing its points
                    // (keeps the historical record but prevents double-expiry)
                    $transaction->update(['points' => 0]);
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
