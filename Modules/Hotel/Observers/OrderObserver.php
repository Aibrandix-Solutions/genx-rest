<?php

namespace Modules\Hotel\Observers;

use App\Models\Order;
use Modules\Hotel\Services\FolioOrderChargeSync;

class OrderObserver
{
    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if (! $order->hotel_reservation_id) {
            return;
        }

        if ($order->wasChanged('status') && $order->status === 'canceled') {
            FolioOrderChargeSync::void($order);

            return;
        }

        $statusTriggersSync = $order->wasChanged('status')
            && in_array($order->status, FolioOrderChargeSync::billableStatuses(), true);

        $totalTriggersSync = $order->wasChanged('total')
            && FolioOrderChargeSync::shouldSync($order);

        $reservationLinked = $order->wasChanged('hotel_reservation_id')
            && FolioOrderChargeSync::shouldSync($order);

        if ($statusTriggersSync || $totalTriggersSync || $reservationLinked) {
            FolioOrderChargeSync::sync($order);
        }
    }
}
