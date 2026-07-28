<?php

namespace App\Observers;

use App\Models\Kot;
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
        // Keep progress sync in-request (kitchen status changes).
        $this->syncOrderProgress($kot);

        // Defer Pusher so POS "KOT & Print" can return print URLs immediately.
        $kotId = (int) $kot->id;
        if ($kotId <= 0) {
            return;
        }

        dispatch(function () use ($kotId) {
            $fresh = Kot::query()->find($kotId);
            if ($fresh) {
                event(new KotUpdated($fresh));
            }
        })->afterResponse();
    }

    private function syncOrderProgress(Kot $kot): void
    {
        $order = $kot->order;

        if (! $order) {
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
