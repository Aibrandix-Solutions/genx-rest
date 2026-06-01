<?php

namespace App\Services\Pos;

use Illuminate\Support\Collection;
use Modules\Hotel\Entities\HotelSetting;
use Modules\Hotel\Entities\Reservation;

class PosHotelSupport
{
    public static function isHotelModuleEnabled(): bool
    {
        return in_array('Hotel', restaurant_modules() ?? [], true);
    }

    public static function canManageRoomService(): bool
    {
        return self::isHotelModuleEnabled() && user_can('manage_room_service');
    }

    public static function isRoomServiceEnabled(): bool
    {
        if (! self::canManageRoomService()) {
            return false;
        }

        $restaurant = restaurant();
        if (! $restaurant?->id) {
            return false;
        }

        $settings = HotelSetting::query()
            ->where('restaurant_id', $restaurant->id)
            ->first();

        return $settings ? (bool) $settings->enable_room_service : true;
    }

    public static function showRoomChargePayment(): bool
    {
        if (! self::isHotelModuleEnabled()) {
            return false;
        }

        if (! function_exists('hotel_business_mode')) {
            return false;
        }

        return in_array(hotel_business_mode(), ['hotel_primary', 'equal'], true);
    }

    /**
     * @return Collection<int, array{id: int, label: string, room_number: string, guest_name: string, room_type_name: string|null}>
     */
    public static function checkedInReservationsForPos(): Collection
    {
        if (! class_exists(Reservation::class) || ! self::isHotelModuleEnabled()) {
            return collect();
        }

        $restaurant = restaurant();
        if (! $restaurant?->id) {
            return collect();
        }

        return Reservation::query()
            ->with(['guest', 'room.roomType'])
            ->where('restaurant_id', $restaurant->id)
            ->where('status', Reservation::STATUS_CHECKED_IN)
            ->orderBy('room_id')
            ->get()
            ->map(fn (Reservation $reservation) => self::formatReservation($reservation));
    }

    /**
     * @return array{id: int, label: string, room_number: string, guest_name: string, room_type_name: string|null}|null
     */
    public static function formatReservation(?Reservation $reservation): ?array
    {
        if (! $reservation) {
            return null;
        }

        $roomNumber = (string) ($reservation->room?->room_number ?? '');
        $guestName = (string) ($reservation->guest?->full_name ?? $reservation->guest?->name ?? 'Guest');
        $roomTypeName = $reservation->room?->roomType?->name
            ? (string) $reservation->room->roomType->name
            : null;

        return [
            'id' => (int) $reservation->id,
            'label' => 'Room '.$roomNumber.' — '.$guestName,
            'room_number' => $roomNumber,
            'guest_name' => $guestName,
            'room_type_name' => $roomTypeName,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function vueBootstrapCapabilities(): array
    {
        $reservations = self::isRoomServiceEnabled()
            ? self::checkedInReservationsForPos()->values()->all()
            : [];

        return [
            'enabled' => self::isHotelModuleEnabled(),
            'can_manage_room_service' => self::canManageRoomService(),
            'room_service_enabled' => self::isRoomServiceEnabled(),
            'show_room_charge_payment' => self::showRoomChargePayment(),
            'business_mode' => function_exists('hotel_business_mode') ? hotel_business_mode() : 'restaurant_primary',
            'checked_in_reservations' => $reservations,
        ];
    }

    public static function filterOrderTypesForPos(iterable $orderTypes): array
    {
        $types = collect($orderTypes);

        if (! self::isRoomServiceEnabled()) {
            $types = $types->reject(
                fn ($type) => strtolower((string) ($type->slug ?? '')) === 'room_service'
            );
        }

        return $types->values()->all();
    }
}
