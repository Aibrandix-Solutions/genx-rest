<?php

namespace Modules\Hotel\Services;

use App\Models\Order;
use App\Models\Payment;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;
use App\Enums\ActivityEvent;
use App\Support\ActivityLogger;

class OrderFolioSettlement
{
    public const STATUS_FOLIO_SETTLED = 'folio_settled';

    /**
     * Statuses that count as hotel-linked food revenue (accrual).
     *
     * @return list<string>
     */
    public static function hotelRevenueStatuses(): array
    {
        return ['billed', self::STATUS_FOLIO_SETTLED, 'paid', 'payment_due'];
    }

    public static function hasPosCashPayment(Order $order): bool
    {
        return (float) $order->payments()
            ->where('payment_method', '!=', 'due')
            ->sum('amount') > 0;
    }

    public static function isChargedToFolio(Order $order): bool
    {
        if (! $order->hotel_reservation_id || self::hasPosCashPayment($order)) {
            return false;
        }

        if ($order->charged_to_folio_at !== null) {
            return true;
        }

        return RoomCharge::query()
            ->where('order_id', $order->id)
            ->where('charge_type', RoomCharge::TYPE_RESTAURANT)
            ->exists();
    }

    public static function isFolioSettled(Order $order): bool
    {
        return $order->folio_settled_at !== null
            || $order->status === self::STATUS_FOLIO_SETTLED;
    }

    public static function isLockedForEditing(Order $order): bool
    {
        return self::isFolioSettled($order);
    }

    /**
     * @return array{label: string, tone: string}|null
     */
    public static function settlementBadge(Order $order): ?array
    {
        if (self::isFolioSettled($order)) {
            return [
                'label' => __('modules.order.folio_settled'),
                'tone' => 'settled',
            ];
        }

        if (self::isChargedToFolio($order) && in_array($order->status, ['billed', self::STATUS_FOLIO_SETTLED], true)) {
            return [
                'label' => __('modules.order.billed_to_room'),
                'tone' => 'folio',
            ];
        }

        return null;
    }

    /**
     * Charge a billed restaurant order to an in-house guest folio (no POS payment row).
     */
    public static function chargeToFolio(Order $order, int $reservationId): void
    {
        $reservation = Reservation::query()
            ->where('id', $reservationId)
            ->where('branch_id', branch()->id)
            ->where('status', Reservation::STATUS_CHECKED_IN)
            ->firstOrFail();

        Payment::where('order_id', $order->id)->delete();

        $order->update([
            'hotel_reservation_id' => $reservation->id,
            'charged_to_folio_at' => now(),
            'folio_settled_at' => null,
            'amount_paid' => 0,
            'status' => 'billed',
        ]);

        FolioOrderChargeSync::sync($order->fresh());

        ActivityLogger::recordEvent(
            activityEvent: ActivityEvent::FolioOrderCharged,
            description: 'Order #' . ($order->formatted_order_number ?? $order->order_number ?? $order->id) . ' charged to folio',
            subject: $reservation,
            properties: [
                'order_id' => $order->id,
                'reservation_id' => $reservation->id,
                'reservation_number' => $reservation->reservation_number ?? null,
                'order_total' => $order->total,
            ],
            restaurantId: $reservation->restaurant_id ? (int) $reservation->restaurant_id : null,
        );
    }

    /**
     * Mark folio-charged orders as settled when the guest checks out (hotel collects payment).
     */
    public static function settleReservationOrders(Reservation $reservation): void
    {
        Order::query()
            ->where('hotel_reservation_id', $reservation->id)
            ->whereNotNull('charged_to_folio_at')
            ->whereNull('folio_settled_at')
            ->whereIn('status', ['billed', self::STATUS_FOLIO_SETTLED])
            ->each(function (Order $order) use ($reservation) {
                $order->update([
                    'folio_settled_at' => now(),
                    'status' => self::STATUS_FOLIO_SETTLED,
                    'amount_paid' => 0,
                ]);
            });

        ActivityLogger::recordEvent(
            activityEvent: ActivityEvent::FolioSettled,
            description: 'Folio orders settled at checkout for reservation ' . ($reservation->reservation_number ?? $reservation->id),
            subject: $reservation,
            properties: [
                'reservation_id' => $reservation->id,
                'reservation_number' => $reservation->reservation_number ?? null,
            ],
            restaurantId: $reservation->restaurant_id ? (int) $reservation->restaurant_id : null,
        );
    }

    public static function markChargedToFolioIfNeeded(Order $order): void
    {
        if ($order->charged_to_folio_at || ! $order->hotel_reservation_id) {
            return;
        }

        if (self::hasPosCashPayment($order)) {
            return;
        }

        if (! FolioOrderChargeSync::shouldSync($order)) {
            return;
        }

        $order->forceFill(['charged_to_folio_at' => now()])->saveQuietly();
    }
}
