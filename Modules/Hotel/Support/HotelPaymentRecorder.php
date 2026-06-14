<?php

namespace Modules\Hotel\Support;

use Modules\Hotel\Entities\HotelPayment;
use Modules\Hotel\Entities\HotelSetting;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;

class HotelPaymentRecorder
{
    public static function appliesSurcharge(
        bool $surchargeEnabled,
        string $paymentMethod,
        string $paymentType,
        float $surchargeRate,
    ): bool {
        return $surchargeEnabled
            && $paymentType !== HotelPayment::TYPE_REFUND
            && in_array($paymentMethod, ['card', 'bank_transfer'], true)
            && $surchargeRate > 0;
    }

    public static function calculateSurcharge(float $paymentAmount, float $surchargeRate): float
    {
        if ($paymentAmount <= 0 || $surchargeRate <= 0) {
            return 0;
        }

        return round($paymentAmount * ($surchargeRate / 100), 2);
    }

    public static function surchargeDescription(string $paymentMethod, float $surchargeRate): string
    {
        $label = match ($paymentMethod) {
            'card' => 'Card',
            'bank_transfer' => 'Bank transfer',
            default => ucfirst(str_replace('_', ' ', $paymentMethod)),
        };

        return $label . ' payment surcharge (' . number_format($surchargeRate, 2, '.', '') . '%)';
    }

    /**
     * @return array{payment: HotelPayment, surcharge: float, total_collected: float}
     */
    public static function record(
        Reservation $reservation,
        float $paymentAmount,
        string $paymentMethod,
        string $paymentType,
        ?string $referenceNumber = null,
        ?string $notes = null,
        ?int $receivedByUserId = null,
        float $surchargeRate = 0,
        ?bool $surchargeEnabled = null,
    ): array {
        if ($surchargeEnabled === null) {
            $settings = HotelSetting::where('restaurant_id', $reservation->restaurant_id)->first();
            $surchargeEnabled = (bool) ($settings->enable_payment_surcharge ?? false);
        }

        $surcharge = 0.0;

        if (
            $paymentAmount > 0
            && self::appliesSurcharge($surchargeEnabled, $paymentMethod, $paymentType, $surchargeRate)
        ) {
            $surcharge = self::calculateSurcharge($paymentAmount, $surchargeRate);

            if ($surcharge > 0) {
                RoomCharge::create([
                    'reservation_id' => $reservation->id,
                    'charge_type' => RoomCharge::TYPE_SERVICE,
                    'description' => self::surchargeDescription($paymentMethod, $surchargeRate),
                    'amount' => $surcharge,
                    'charge_date' => now()->toDateString(),
                ]);
            }
        }

        $totalCollected = round($paymentAmount + $surcharge, 2);

        $payment = HotelPayment::create([
            'restaurant_id' => $reservation->restaurant_id,
            'reservation_id' => $reservation->id,
            'amount' => $totalCollected,
            'payment_method' => $paymentMethod,
            'payment_type' => $paymentType,
            'reference_number' => $referenceNumber,
            'notes' => $notes,
            'received_by_user_id' => $receivedByUserId ?? auth()->id(),
        ]);

        return [
            'payment' => $payment,
            'surcharge' => $surcharge,
            'total_collected' => $totalCollected,
        ];
    }
}
