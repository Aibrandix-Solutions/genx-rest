<?php

namespace Modules\Hotel\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomCharge;

class FolioChargePresenter
{
    /**
     * @return array{
     *     room_groups: list<array{
     *         key: string,
     *         start_date: Carbon,
     *         end_date: Carbon,
     *         room_number: string,
     *         nights: int,
     *         price_per_night: float,
     *         room_total: float,
     *         charges: Collection<int, RoomCharge>,
     *     }>,
     *     type_rows: list<array{
     *         key: string,
     *         type: string,
     *         label: string,
     *         date: Carbon|null,
     *         amount: float,
     *         charge_count: int,
     *         charges: Collection<int, RoomCharge>,
     *     }>,
     *     other_charges: Collection<int, RoomCharge>,
     *     subtotal: float,
     * }
     */
    public static function summarize(Reservation $reservation, Collection $charges, ?string $roomNumberOverride = null): array
    {
        $roomNumber = $roomNumberOverride !== null ? $roomNumberOverride : (string) ($reservation->room?->room_number ?? '');

        $roomGroups = self::groupRoomNights(
            $charges->where('charge_type', RoomCharge::TYPE_ROOM_NIGHT)->sortBy('charge_date')->values(),
            $roomNumber,
        );

        $otherCharges = $charges
            ->where('charge_type', '!=', RoomCharge::TYPE_ROOM_NIGHT)
            ->sortBy('charge_date')
            ->values();

        $typeRows = self::buildTypeRows($otherCharges);

        return [
            'room_groups' => $roomGroups,
            'type_rows' => $typeRows,
            'other_charges' => $otherCharges,
            'subtotal' => round((float) $charges->sum('amount'), 2),
        ];
    }

    /**
     * @param  Collection<int, RoomCharge>  $roomCharges
     * @return list<array{
     *     key: string,
     *     start_date: Carbon,
     *     end_date: Carbon,
     *     room_number: string,
     *     nights: int,
     *     price_per_night: float,
     *     room_total: float,
     *     charges: Collection<int, RoomCharge>,
     * }>
     */
    private static function groupRoomNights(Collection $roomCharges, string $roomNumber): array
    {
        // Group charges by room_number first to avoid interleaved rooms splitting consecutive nights
        $chargesByRoom = $roomCharges->groupBy(function ($charge) use ($roomNumber) {
            if ($roomNumber !== '') {
                return $roomNumber;
            }
            if ($charge->reservation && $charge->reservation->room) {
                return $charge->reservation->room->room_number;
            }
            return self::extractRoomNumberFromDescription($charge->description) ?: 'Other';
        });

        $allGroups = [];
        $index = 0;

        foreach ($chargesByRoom as $roomNo => $charges) {
            $groups = [];
            $current = null;

            foreach ($charges->sortBy('charge_date')->values() as $charge) {
                $chargeDate = Carbon::parse($charge->charge_date)->startOfDay();
                $rate = round((float) $charge->amount, 2);

                if ($current === null) {
                    $current = self::startRoomGroup($index, $charge, $chargeDate, $rate, (string)$roomNo);
                    $index++;
                    continue;
                }

                $nextExpected = $current['end_date']->copy()->addDay()->startOfDay();
                $isConsecutive = $chargeDate->equalTo($nextExpected);
                $sameRate = $rate === $current['price_per_night'];

                if ($isConsecutive && $sameRate) {
                    $current['end_date'] = $chargeDate;
                    $current['nights']++;
                    $current['room_total'] = round($current['room_total'] + $rate, 2);
                    $current['charges']->push($charge);
                    continue;
                }

                $groups[] = $current;
                $current = self::startRoomGroup($index, $charge, $chargeDate, $rate, (string)$roomNo);
                $index++;
            }

            if ($current !== null) {
                $groups[] = $current;
            }

            $allGroups = array_merge($allGroups, $groups);
        }

        return $allGroups;
    }

    /**
     * @return array{
     *     key: string,
     *     start_date: Carbon,
     *     end_date: Carbon,
     *     room_number: string,
     *     nights: int,
     *     price_per_night: float,
     *     room_total: float,
     *     charges: Collection<int, RoomCharge>,
     * }
     */
    private static function startRoomGroup(
        int $index,
        RoomCharge $charge,
        Carbon $chargeDate,
        float $rate,
        string $roomNumber,
    ): array {
        return [
            'key' => 'room-group-' . $index,
            'start_date' => $chargeDate->copy(),
            'end_date' => $chargeDate->copy(),
            'room_number' => $roomNumber !== '' ? $roomNumber : self::extractRoomNumberFromDescription($charge->description),
            'nights' => 1,
            'price_per_night' => $rate,
            'room_total' => $rate,
            'charges' => collect([$charge]),
        ];
    }

    /**
     * @param  Collection<int, RoomCharge>  $otherCharges
     * @return list<array{
     *     key: string,
     *     type: string,
     *     label: string,
     *     date: Carbon|null,
     *     amount: float,
     *     charge_count: int,
     *     charges: Collection<int, RoomCharge>,
     * }>
     */
    private static function buildTypeRows(Collection $otherCharges): array
    {
        if ($otherCharges->isEmpty()) {
            return [];
        }

        $grouped = $otherCharges->groupBy(function (RoomCharge $charge) {
            if ($charge->charge_type === RoomCharge::TYPE_OTHER) {
                $label = $charge->getCustomTypeLabel();

                return $label ? 'other:' . mb_strtolower($label) : RoomCharge::TYPE_OTHER;
            }

            return $charge->charge_type;
        });

        $order = [
            RoomCharge::TYPE_RESTAURANT,
            RoomCharge::TYPE_MINIBAR,
            RoomCharge::TYPE_LAUNDRY,
            RoomCharge::TYPE_SERVICE,
            RoomCharge::TYPE_TAX,
            RoomCharge::TYPE_OTHER,
        ];

        $rows = [];

        foreach ($grouped as $typeKey => $charges) {
            $charges = $charges->sortBy('charge_date')->values();
            $type = str_starts_with((string) $typeKey, 'other:')
                ? RoomCharge::TYPE_OTHER
                : (string) $typeKey;

            $first = $charges->first();
            $label = self::typeLabel($type, $first);

            $rows[] = [
                'key' => 'type-' . $typeKey,
                'type' => $typeKey,
                'label' => $label,
                'date' => $first ? Carbon::parse($first->charge_date) : null,
                'amount' => round((float) $charges->sum('amount'), 2),
                'charge_count' => $charges->count(),
                'charges' => $charges,
            ];
        }

        usort($rows, function (array $a, array $b) use ($order) {
            $typeA = str_starts_with($a['type'], 'other:') ? RoomCharge::TYPE_OTHER : $a['type'];
            $typeB = str_starts_with($b['type'], 'other:') ? RoomCharge::TYPE_OTHER : $b['type'];
            $posA = array_search($typeA, $order, true);
            $posB = array_search($typeB, $order, true);
            $posA = $posA === false ? 99 : $posA;
            $posB = $posB === false ? 99 : $posB;

            if ($posA !== $posB) {
                return $posA <=> $posB;
            }

            return strcmp($a['label'], $b['label']);
        });

        return $rows;
    }

    public static function typeLabel(string $type, ?RoomCharge $sample = null): string
    {
        if (str_starts_with($type, 'other:')) {
            return ucfirst(substr($type, 6));
        }

        if ($type === RoomCharge::TYPE_OTHER && $sample) {
            return $sample->getCustomTypeLabel() ?? __('hotel::modules.folio.other');
        }

        return match ($type) {
            RoomCharge::TYPE_RESTAURANT => __('hotel::modules.folio.restaurant'),
            RoomCharge::TYPE_MINIBAR => __('hotel::modules.folio.minibar'),
            RoomCharge::TYPE_LAUNDRY => __('hotel::modules.folio.laundry'),
            RoomCharge::TYPE_SERVICE => __('hotel::modules.folio.service'),
            RoomCharge::TYPE_TAX => __('hotel::modules.folio.tax'),
            RoomCharge::TYPE_OTHER => __('hotel::modules.folio.other'),
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    /**
     * @param  Collection<int, RoomCharge>  $otherCharges
     * @return Collection<int, RoomCharge>
     */
    public static function filterOtherCharges(Collection $otherCharges, ?string $typeFilter): Collection
    {
        if ($typeFilter === null || $typeFilter === '') {
            return $otherCharges;
        }

        return $otherCharges->filter(function (RoomCharge $charge) use ($typeFilter) {
            if ($typeFilter === RoomCharge::TYPE_OTHER) {
                return $charge->charge_type === RoomCharge::TYPE_OTHER
                    && $charge->getCustomTypeLabel() === null;
            }

            if (str_starts_with($typeFilter, 'other:')) {
                $label = substr($typeFilter, 6);

                return $charge->charge_type === RoomCharge::TYPE_OTHER
                    && mb_strtolower((string) $charge->getCustomTypeLabel()) === $label;
            }

            return $charge->charge_type === $typeFilter;
        })->values();
    }

    private static function extractRoomNumberFromDescription(string $description): string
    {
        if (preg_match('/Room\s+(\S+)/i', $description, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
