<?php

namespace App\Observers;

use App\Models\Kot;
use App\Models\KotSetting;
use App\Events\KotUpdated;
use App\Enums\OrderStatus;

class KotObserver
{
    public function creating(Kot $kot)
    {
        if (branch() && $kot->branch_id == null) {
            $kot->branch_id = branch()->id;
        }

        // All KOTs start as pending_confirmation
        // Kitchen staff must manually click "Start Cooking" to progress
        $kot->status = 'pending_confirmation';
    }

    public function saved(Kot $kot)
    {
        event(new KotUpdated($kot));

        $this->syncOrderProgress($kot);
    }

    private function syncOrderProgress(Kot $kot): void
    {
        $order = $kot->order;

        if (!$order) {
            return;
        }

        // Note: 'in_kitchen' and 'food_ready' syncs are now handled by KotCard.php
        // when kitchen staff manually changes status. This prevents auto-sync on KOT creation.
        
        // Only mark the order as served when every KOT linked to it is served
        if ($kot->status === 'served') {
            $allServed = $order->kot()
                ->where('status', '!=', 'served')
                ->where('status', '!=', 'cancelled')
                ->doesntExist();

            if ($allServed && $order->order_status?->value !== OrderStatus::SERVED->value) {
                $order->order_status = OrderStatus::SERVED;
                $order->save();
            }
        }
    }
}
