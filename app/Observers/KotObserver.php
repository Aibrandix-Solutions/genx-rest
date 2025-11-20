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
        $kotSettings = KotSetting::first();

        if (branch() && $kot->branch_id == null) {
            $kot->branch_id = branch()->id;
        }

        if ($kot->order?->order_status->value === 'placed' || $kotSettings->default_status == 'pending') {
            $kot->status = 'pending_confirmation';
        } elseif ($kotSettings->default_status == 'cooking') {
            $kot->status = 'in_kitchen';
        }
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

        // Only mark the order as served when every KOT linked to it is served
        if ($kot->status === 'served') {
            $allServed = $order->kot()
                ->where('status', '!=', 'served')
                ->doesntExist();

            if ($allServed && $order->order_status?->value !== OrderStatus::SERVED->value) {
                $order->order_status = OrderStatus::SERVED;
                $order->save();
            }
        }
    }
}
