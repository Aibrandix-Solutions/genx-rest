<?php

namespace Modules\Hotel\Services;

use App\Models\Order;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;

class FolioOrderChargeSync
{
    /**
     * Statuses where a linked order should be reflected on the guest folio.
     */
    public static function billableStatuses(): array
    {
        return ['delivered', 'completed', 'billed', 'paid', 'payment_due'];
    }

    public static function shouldSync(Order $order): bool
    {
        if (! $order->hotel_reservation_id) {
            return false;
        }

        if ($order->status === 'canceled') {
            return false;
        }

        if (OrderFolioSettlement::isFolioSettled($order)) {
            return false;
        }

        return in_array($order->status, self::billableStatuses(), true);
    }

    /**
     * Create or update the restaurant folio line for a linked order.
     */
    public static function sync(Order $order): void
    {
        if (! self::shouldSync($order)) {
            return;
        }

        $reservation = Reservation::find($order->hotel_reservation_id);

        if (! $reservation) {
            return;
        }

        $amount = round((float) $order->total, 2);
        $description = self::chargeDescription($order);

        $charge = RoomCharge::where('order_id', $order->id)->first();

        if ($charge) {
            $updates = [];

            if ((float) $charge->amount !== $amount) {
                $updates['amount'] = $amount;
            }

            if ($charge->description !== $description) {
                $updates['description'] = $description;
            }

            if ($updates !== []) {
                $charge->update($updates);
                self::ensureFolioFlags($order);
                $reservation->calculateTotal();
            } else {
                self::ensureFolioFlags($order);
            }

            return;
        }

        if ($amount <= 0) {
            return;
        }

        RoomCharge::create([
            'branch_id'      => $reservation->branch_id,
            'reservation_id' => $reservation->id,
            'charge_type' => RoomCharge::TYPE_RESTAURANT,
            'order_id' => $order->id,
            'description' => $description,
            'amount' => $amount,
            'charge_date' => now(),
        ]);

        self::ensureFolioFlags($order);

        $reservation->calculateTotal();
    }

    public static function void(Order $order): void
    {
        $charge = RoomCharge::where('order_id', $order->id)->first();

        if (! $charge) {
            return;
        }

        $reservation = $charge->reservation;
        $charge->delete();

        if ($reservation) {
            $reservation->calculateTotal();
        }
    }

    protected static function ensureFolioFlags(Order $order): void
    {
        OrderFolioSettlement::markChargedToFolioIfNeeded($order->fresh());

        if (
            $order->hotel_reservation_id
            && $order->charged_to_folio_at === null
            && RoomCharge::where('order_id', $order->id)->exists()
        ) {
            $order->forceFill(['charged_to_folio_at' => now()])->saveQuietly();
        }
    }

    protected static function chargeDescription(Order $order): string
    {
        $label = $order->order_number
            ? 'Order #' . $order->order_number
            : 'Order #' . $order->id;

        if ($order->order_type === 'room_service') {
            return 'Room Service ' . $label;
        }

        return 'Restaurant ' . $label;
    }
}
