<?php

namespace Modules\Hotel\Observers;

use App\Models\Order;
use Modules\Hotel\Entities\RoomCharge;
use Modules\Hotel\Entities\Reservation;

class OrderObserver
{
    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Check if the order source is 'room_service' (or has a hotel_reservation_id)
        if ($order->hotel_reservation_id) {
            
            // Define the status that triggers a charge. 
            // Assuming 'delivered', 'billed', or 'paid' are final states.
            // But since it's room service, 'paid' might not happen immediately at POS.
            // Let's rely on 'delivered' or a specific status for Room Service completion.
            // Ideally, we check if the status CHANGED to a completed state.
            
            if ($order->isDirty('status') && in_array($order->status, ['delivered', 'completed', 'billed'])) {
                $this->createRoomCharge($order);
            }
        }
    }

    protected function createRoomCharge(Order $order)
    {
        // Prevent duplicate charges for the same order
        $existingCharge = RoomCharge::where('order_id', $order->id)->exists();

        if ($existingCharge) {
            return;
        }

        $reservation = Reservation::find($order->hotel_reservation_id);
        
        if (!$reservation) {
            return; 
        }

        RoomCharge::create([
            'reservation_id' => $reservation->id,
            'charge_type' => RoomCharge::TYPE_RESTAURANT,
            'order_id' => $order->id,
            'description' => 'Room Service Order #' . $order->order_number,
            'amount' => $order->total,
            'charge_date' => now(),
        ]);

        // Recalculate reservation totals so balance_due stays in sync
        $reservation->calculateTotal();
    }
}
