<?php

namespace Modules\Hrm\Support;

class Workplace
{
    public const RESTAURANT = 'restaurant';

    public const HOTEL = 'hotel';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [
            self::RESTAURANT => 'Restaurant',
        ];

        if (self::hotelAvailable()) {
            $options[self::HOTEL] = 'Hotel';
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function allowedValues(): array
    {
        return array_keys(self::options());
    }

    public static function hotelAvailable(): bool
    {
        return in_array('Hotel', restaurant_modules() ?? [], true);
    }

    public static function default(): string
    {
        $allowed = self::allowedValues();

        return $allowed[0] ?? self::RESTAURANT;
    }

    public static function normalize(?string $workplace): string
    {
        $workplace = strtolower(trim((string) $workplace));

        if (in_array($workplace, self::allowedValues(), true)) {
            return $workplace;
        }

        return self::default();
    }

    public static function label(?string $workplace): string
    {
        $workplace = strtolower(trim((string) $workplace));

        return match ($workplace) {
            self::HOTEL => 'Hotel',
            default => 'Restaurant',
        };
    }

    public static function isHotel(?string $workplace): bool
    {
        return strtolower(trim((string) $workplace)) === self::HOTEL;
    }
}
